# Spécification : Pipeline d'import de données de matchs

## Objectif

Créer un pipeline Laravel d'import de données détaillées (lineups, événements, remplacements) pour les ~824 matchs déjà présents en base. Les données arrivent au format JSON, un fichier par match ou un fichier contenant plusieurs matchs.

---

## Format JSON d'entrée

### Fichier mono-match

```json
{
  "match_date": "2024-11-09",
  "opponent_code": "JPN",
  "france_score": 52,
  "opponent_score": 12,
  "lineups": {
    "france": [
      {
        "jersey": 1,
        "first_name": "Jean-Baptiste",
        "last_name": "Gros",
        "is_starter": true,
        "position": "pilier_gauche",
        "is_captain": false
      }
    ],
    "adversaire": [
      {
        "jersey": 1,
        "first_name": "Keita",
        "last_name": "Inagaki",
        "is_starter": true,
        "position": "pilier_gauche",
        "is_captain": false
      }
    ]
  },
  "events": [
    {
      "team_side": "france",
      "type": "essai",
      "player_last_name": "Penaud",
      "player_first_name": "Damian",
      "minute": 12
    },
    {
      "team_side": "france",
      "type": "transformation",
      "player_last_name": "Ramos",
      "player_first_name": "Thomas",
      "minute": 13
    },
    {
      "team_side": "adversaire",
      "type": "carton_jaune",
      "player_last_name": "Inagaki",
      "player_first_name": "Keita",
      "minute": 35
    },
    {
      "team_side": "france",
      "type": "essai_penalite",
      "player_last_name": null,
      "player_first_name": null,
      "minute": 36
    }
  ],
  "substitutions": [
    {
      "team_side": "france",
      "player_off_last_name": "Gros",
      "player_off_first_name": "Jean-Baptiste",
      "player_on_last_name": "Baille",
      "player_on_first_name": "Cyril",
      "minute": 50,
      "is_tactical": true
    }
  ]
}
```

### Fichier multi-matchs

```json
[
  { "match_date": "...", "opponent_code": "...", ... },
  { "match_date": "...", "opponent_code": "...", ... }
]
```

### Champs optionnels

Tous les champs dans `lineups`, `events` et `substitutions` peuvent être absents ou partiellement remplis pour les matchs historiques :

- `minute` peut être `null` (matchs anciens sans minutes détaillées)
- `events` peut être un tableau vide `[]`
- `substitutions` peut être un tableau vide `[]` (pas de remplacements avant 1968)
- `lineups.adversaire` peut être un tableau vide `[]` (composition adverse inconnue)
- `position` peut être `null` si inconnue
- `is_captain` est optionnel (défaut: `false`)
- `is_tactical` est optionnel (défaut: `true`)

---

## Commandes Artisan à créer

### 1. `import:match-data`

```bash
# Import un fichier JSON
php artisan import:match-data storage/imports/2024-11-09-JPN.json

# Import tous les fichiers d'un dossier
php artisan import:match-data storage/imports/2024/

# Options
--dry-run          # Valide sans écrire en BDD
--force            # Écrase les données existantes (lineups, events, subs)
--skip-existing    # Ne touche pas aux matchs qui ont déjà des lineups
--verbose          # Affiche le détail de chaque opération
```

### 2. `validate:match-data`

```bash
# Valide un fichier sans importer
php artisan validate:match-data storage/imports/2024-11-09-JPN.json

# Valide un dossier entier
php artisan validate:match-data storage/imports/2024/
```

---

## Logique d'import détaillée

### Étape 1 — Résolution du match

Chercher le match existant par `match_date` + `opponent_code` :

```php
$country = Country::where('code', $data['opponent_code'])->firstOrFail();
$match = RugbyMatch::where('match_date', $data['match_date'])
    ->where('opponent_id', $country->id)
    ->firstOrFail();
```

**Si le match n'existe pas** → log un warning et skip.
**Si le score diffère** → log un warning mais continuer (le score existant fait foi).

### Étape 2 — Vérification données existantes

```php
$hasLineups = $match->lineups()->count() > 0;
$hasEvents = $match->events()->count() > 0;
$hasSubs = $match->substitutions()->count() > 0;
```

- Sans `--force` : skip si données existantes, log "Match déjà rempli"
- Avec `--force` : supprimer les données existantes avant réimport
- Avec `--skip-existing` : toujours skip si données existantes

### Étape 3 — Résolution des joueurs

Pour chaque joueur dans les lineups/events/subs :

```
1. Chercher par (last_name, first_name, country_id) → exact match
2. Si pas trouvé et team_side = FRANCE :
   → Chercher par (last_name, country_id = France) → match fuzzy
   → Si un seul résultat → utiliser (log info)
   → Si plusieurs résultats → log warning "joueur ambigu"
3. Si pas trouvé du tout :
   → Créer le joueur avec les données disponibles
   → Log info "joueur créé : {nom} ({pays})"
```

**Normalisation des noms :**
- Trim whitespace
- Normaliser les accents pour la comparaison (mais garder les accents en BDD)
- Gérer les cas courants : "De" vs "de", "Van" vs "van", tirets

**Gestion des homonymes :** Si plusieurs joueurs matchent et qu'on ne peut pas départager → log un warning et NE PAS importer cette ligne (pas de création de doublon).

### Étape 4 — Import des lineups

Pour chaque joueur dans `lineups.france` et `lineups.adversaire` :

```php
MatchLineup::create([
    'match_id' => $match->id,
    'player_id' => $resolvedPlayer->id,
    'jersey_number' => $entry['jersey'],
    'is_starter' => $entry['is_starter'],
    'position_played' => $entry['position'] ? PlayerPosition::from($entry['position']) : null,
    'is_captain' => $entry['is_captain'] ?? false,
    'team_side' => $teamSide, // TeamSide::FRANCE ou TeamSide::ADVERSAIRE
]);
```

### Étape 5 — Import des événements

Pour chaque événement dans `events` :

```php
MatchEvent::create([
    'match_id' => $match->id,
    'player_id' => $resolvedPlayer?->id, // null pour ESSAI_PENALITE
    'event_type' => EventType::from($event['type']),
    'minute' => $event['minute'],
    'team_side' => TeamSide::from($event['team_side']),
]);
```

### Étape 6 — Import des remplacements

Pour chaque remplacement dans `substitutions` :

```php
MatchSubstitution::create([
    'match_id' => $match->id,
    'player_off_id' => $resolvedPlayerOff->id,
    'player_on_id' => $resolvedPlayerOn->id,
    'minute' => $sub['minute'],
    'is_tactical' => $sub['is_tactical'] ?? true,
    'team_side' => TeamSide::from($sub['team_side']),
]);
```

---

## Validation

### Règles de validation JSON (validate:match-data)

```
match_date          → required, date format Y-m-d
opponent_code       → required, string 3 chars, exists in countries.code
france_score        → optionnel, integer >= 0
opponent_score      → optionnel, integer >= 0

lineups.france      → array
lineups.adversaire  → array
lineups.*.jersey    → required, integer 1-23
lineups.*.last_name → required, string
lineups.*.first_name → required, string
lineups.*.is_starter → required, boolean
lineups.*.position  → nullable, in PlayerPosition values

events              → array
events.*.team_side  → required, in: france, adversaire
events.*.type       → required, in EventType values
events.*.player_last_name → nullable (required sauf essai_penalite)
events.*.minute     → nullable, integer 0-120

substitutions            → array
substitutions.*.team_side → required
substitutions.*.player_off_last_name → required
substitutions.*.player_on_last_name  → required
substitutions.*.minute    → nullable, integer 0-120
```

### Vérifications de cohérence

1. **Nombre de titulaires** : exactement 15 starters par équipe (si lineup complète)
2. **Numéros uniques** : pas de doublon jersey par équipe
3. **Remplaçants cohérents** : player_off doit être un starter ou déjà entré, player_on doit être un remplaçant
4. **Marqueurs dans la composition** : tout joueur dans events doit être dans lineups
5. **Types d'événements logiques** : une transformation doit suivre un essai

---

## Logging et rapport

### Format de sortie console

```
=== Import match 2024-11-09 vs JPN ===
[OK]  Match trouvé : France 52-12 Japon (id: 423)
[OK]  16 lineups France importés (15 titulaires, 1 remplaçant)
[OK]  15 lineups Japon importés (15 titulaires, 0 remplaçant)
[WARN] Joueur non trouvé, créé : Keita Inagaki (JPN)
[OK]  8 événements importés
[OK]  6 remplacements importés

=== Résumé ===
Matchs traités : 12
Matchs importés : 10
Matchs skippés (déjà remplis) : 2
Joueurs créés : 34
Warnings : 5
Erreurs : 0
```

### Fichier de log

Écrire aussi dans `storage/logs/import-YYYY-MM-DD.log` avec le détail complet.

---

## Structure de fichiers à créer

```
app/Console/Commands/
├── ImportMatchData.php        # Commande principale
├── ValidateMatchData.php      # Commande de validation

app/Services/
├── MatchImportService.php     # Logique d'import
├── PlayerResolverService.php  # Résolution et création de joueurs
├── MatchDataValidator.php     # Validation des données JSON

storage/imports/               # Dossier pour les fichiers JSON à importer
├── 2024/                      # Organisés par année
│   ├── 2024-02-02-IRL.json
│   ├── 2024-02-10-ITA.json
│   └── ...
├── 2023/
└── ...
```

---

## Mapping des positions ESPN → PlayerPosition

Les pages ESPN utilisent des abréviations qu'il faudra mapper :

```php
$espnPositionMap = [
    'P'  => null,            // Prop → pilier_gauche ou pilier_droit (selon numéro)
    'H'  => 'talonneur',     // Hooker
    'L'  => 'deuxieme_ligne', // Lock
    'FL' => 'troisieme_ligne_aile', // Flanker
    'N8' => 'numero_huit',   // Number 8
    'SH' => 'demi_de_melee', // Scrum-half
    'FH' => 'demi_ouverture', // Fly-half
    'C'  => 'centre',        // Centre
    'W'  => 'ailier',        // Wing
    'FB' => 'arriere',       // Fullback
    'R'  => null,             // Replacement → déduire du numéro
];

// Déduction par numéro de maillot
$jerseyPositionMap = [
    1  => 'pilier_gauche',
    2  => 'talonneur',
    3  => 'pilier_droit',
    4  => 'deuxieme_ligne',
    5  => 'deuxieme_ligne',
    6  => 'troisieme_ligne_aile',
    7  => 'troisieme_ligne_aile',
    8  => 'numero_huit',
    9  => 'demi_de_melee',
    10 => 'demi_ouverture',
    11 => 'ailier',
    12 => 'centre',
    13 => 'centre',
    14 => 'ailier',
    15 => 'arriere',
    // 16-23 : déduire du poste du joueur remplacé ou de primary_position
];
```

---

## Mapping des événements ESPN

Sur ESPN, les marqueurs apparaissent avec des icônes :
- `T` = Try (essai)
- Ballon de rugby = Conversion (transformation)
- `P` avec ballon = Penalty (pénalité)
- `DG` = Drop Goal (drop)
- Carré jaune = Yellow Card (carton_jaune)
- Carré rouge = Red Card (carton_rouge)

La page commentary donne les détails avec minutes :
```
66' | 21-43 Conversion - Felipe Etcheverry, Uruguay
65' | 19-43 Try - Guillermo Pujadas, Uruguay
```

Format à parser : `{minute}' | {score} {EventType} - {PlayerName}, {Team}`

---

## Sources de données — URLs ESPN

### IDs ESPN clés

- **France team** : id = `9`
- **Leagues** :
  - Six Nations : `244293`
  - Autumn Internationals / Test Matches : `289234`
  - Rugby World Cup 2023 : `164205`

### Pattern d'URLs

```
# Page lineup d'un match
https://www.espn.com/rugby/lineups/_/gameId/{gameId}/league/{leagueId}

# Page résumé d'un match (marqueurs avec minutes)
https://www.espn.com/rugby/match/_/gameId/{gameId}/league/{leagueId}

# Page commentary (substitutions avec minutes)
https://www.espn.com/rugby/commentary/_/gameId/{gameId}/league/{leagueId}
```

### Données disponibles sur ESPN par page

| Page | Lineups 23+23 | Positions | Marqueurs | Minutes | Subs | Venue |
|------|:---:|:---:|:---:|:---:|:---:|:---:|
| **match** (summary) | ✅ | ✅ | ✅ | ✅ | Partielles | ✅ |
| **lineups** | ✅ | ✅ | Icônes | ❌ | ❌ | ❌ |
| **commentary** | ❌ | ❌ | ✅ | ✅ | ✅ | ❌ |

→ **Stratégie optimale** : scraper la page `match` (summary) pour lineups + marqueurs, puis `commentary` pour les substitutions détaillées.

---

## Notes pour le scraper (phase 2, à développer ensuite)

Le scraper sera un script Python séparé (pas dans Laravel) qui :
1. Récupère la liste des gameId ESPN pour les matchs de France
2. Pour chaque match, scrape `match` + `commentary`
3. Génère un fichier JSON par match dans le format ci-dessus
4. Le pipeline Laravel importe ensuite ces fichiers

Cela découple le scraping de l'import, permettant aussi l'import de données d'autres sources (Wikipedia, saisie manuelle exportée de Filament, etc.).
