# CLAUDE.md — xvfrance.fr

## Projet

Site de référence francophone sur l'histoire du XV de France de rugby depuis 1906.
Tous les matches, compositions complètes (France ET adversaires), marqueurs détaillés,
sélectionneurs et compétitions.

- **Domaine :** xvfrance.fr
- **Hébergement :** O2switch (mutualisé, PHP 8.2+, MySQL 8, SSH, Composer)
- **Repo GitHub :** github.com/jeremsrd/xvfrance (à créer)

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | Laravel 11 |
| Front-end | Blade + Livewire 3 |
| Micro-interactions | Alpine.js (livré avec Livewire) |
| CSS | Tailwind CSS via CDN |
| Base de données | MySQL 8 |
| Admin | À déterminer (Filament recommandé) |

**Pas de Node.js requis.** Tailwind via CDN Play en dev. Pas de build frontend.

---

## Commandes utiles

```bash
# Serveur de dev
php artisan serve

# Migrations
php artisan migrate
php artisan migrate:fresh --seed   # Reset complet + seeders

# Créer un model + migration + factory + seeder
php artisan make:model NomDuModel -mfs

# Créer un composant Livewire
php artisan make:livewire NomDuComposant

# Créer un enum
# Pas de commande artisan, créer manuellement dans app/Enums/

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Base de données — 11 tables

### Principe clé
Table `players` unifiée pour joueurs français ET adverses, distingués par `country_id`.
Les tables `match_lineups`, `match_events`, `match_substitutions` utilisent un champ
`team_side` (ENUM: FRANCE, ADVERSAIRE) pour distinguer les deux équipes sur chaque match.

### Schéma des relations

```
countries ──┬── venues (country_id)
            ├── players (country_id = nationalité sportive)
            ├── coaches (country_id)
            ├── matches (opponent_id, referee_country_id)

competitions ── competition_editions ── matches (edition_id)
venues ── matches (venue_id)
coaches ── coach_tenures (coach_id)

players ──┬── match_lineups (player_id)
          ├── match_events (player_id)
          └── match_substitutions (player_off_id, player_on_id)

matches ──┬── match_lineups (match_id)
          ├── match_events (match_id)
          └── match_substitutions (match_id)
```

### Détail des tables et colonnes

#### countries
- id (PK), name (VARCHAR 100), code (VARCHAR 3, code World Rugby),
  continent (ENUM), flag_emoji (VARCHAR 10)

#### venues
- id (PK), name (VARCHAR 150), city (VARCHAR 100),
  country_id (FK countries), capacity (INT NULL),
  opened_year (INT NULL), latitude (DECIMAL 10,7 NULL),
  longitude (DECIMAL 10,7 NULL)

#### competitions
- id (PK), name (VARCHAR 150), short_name (VARCHAR 50),
  type (ENUM: TOURNOI, COUPE_DU_MONDE, TEST_MATCH, TOURNEE, AUTRE)

#### competition_editions
- id (PK), competition_id (FK competitions), year (INT),
  label (VARCHAR 100), france_ranking (INT NULL)

#### coaches
- id (PK), first_name (VARCHAR 80), last_name (VARCHAR 80),
  birth_date (DATE NULL), birth_city (VARCHAR 100 NULL),
  country_id (FK countries), photo_url (VARCHAR 255 NULL)

#### coach_tenures
- id (PK), coach_id (FK coaches),
  role (ENUM: SELECTIONNEUR, ENTRAINEUR_AVANTS, ENTRAINEUR_ARRIERES,
  ENTRAINEUR_DEFENSE, ENTRAINEUR_TOUCHE, ENTRAINEUR_MELEE,
  PREPARATEUR_PHYSIQUE, ADJOINT),
  start_date (DATE), end_date (DATE NULL = en cours)

#### players
- id (PK), first_name (VARCHAR 80), last_name (VARCHAR 80),
  birth_date (DATE NULL), birth_city (VARCHAR 100 NULL),
  country_id (FK countries), height_cm (INT NULL), weight_kg (INT NULL),
  primary_position (ENUM PlayerPosition), photo_url (VARCHAR 255 NULL),
  is_active (BOOLEAN DEFAULT TRUE)

#### matches
- id (PK), match_date (DATE), kickoff_time (TIME NULL),
  venue_id (FK venues), opponent_id (FK countries),
  edition_id (FK competition_editions NULL),
  france_score (INT), opponent_score (INT),
  is_home (BOOLEAN), is_neutral (BOOLEAN DEFAULT FALSE),
  stage (ENUM MatchStage NULL), match_number (INT NULL),
  attendance (INT NULL), referee (VARCHAR 150 NULL),
  referee_country_id (FK countries NULL),
  weather (VARCHAR 100 NULL), notes (TEXT NULL)

#### match_lineups
- id (PK), match_id (FK matches), player_id (FK players),
  jersey_number (INT 1-23), is_starter (BOOLEAN),
  position_played (ENUM PlayerPosition), is_captain (BOOLEAN DEFAULT FALSE),
  team_side (ENUM: FRANCE, ADVERSAIRE)
- UNIQUE(match_id, team_side, jersey_number)
- UNIQUE(match_id, player_id)

#### match_events
- id (PK), match_id (FK matches), player_id (FK players NULL),
  event_type (ENUM EventType), minute (INT NULL),
  team_side (ENUM: FRANCE, ADVERSAIRE),
  detail (VARCHAR 255 NULL)
- player_id NULL uniquement pour ESSAI_PENALITE

#### match_substitutions
- id (PK), match_id (FK matches), player_off_id (FK players),
  player_on_id (FK players), minute (INT),
  is_tactical (BOOLEAN DEFAULT TRUE),
  team_side (ENUM: FRANCE, ADVERSAIRE)

---

## Enums PHP (app/Enums/)

```php
// app/Enums/PlayerPosition.php
enum PlayerPosition: string {
    case PILIER_GAUCHE = 'pilier_gauche';
    case TALONNEUR = 'talonneur';
    case PILIER_DROIT = 'pilier_droit';
    case DEUXIEME_LIGNE = 'deuxieme_ligne';
    case TROISIEME_LIGNE_AILE = 'troisieme_ligne_aile';
    case NUMERO_HUIT = 'numero_huit';
    case DEMI_DE_MELEE = 'demi_de_melee';
    case DEMI_OUVERTURE = 'demi_ouverture';
    case AILIER = 'ailier';
    case CENTRE = 'centre';
    case ARRIERE = 'arriere';
}

// app/Enums/EventType.php
enum EventType: string {
    case ESSAI = 'essai';
    case ESSAI_PENALITE = 'essai_penalite';
    case TRANSFORMATION = 'transformation';
    case PENALITE = 'penalite';
    case DROP = 'drop';
    case CARTON_JAUNE = 'carton_jaune';
    case CARTON_ROUGE = 'carton_rouge';
}

// app/Enums/MatchStage.php
enum MatchStage: string {
    case POULE = 'poule';
    case HUITIEME = 'huitieme';
    case QUART = 'quart';
    case DEMI = 'demi';
    case FINALE = 'finale';
    case PETITE_FINALE = 'petite_finale';
    case JOURNEE = 'journee';
    case TEST = 'test';
}

// app/Enums/CompetitionType.php
enum CompetitionType: string {
    case TOURNOI = 'tournoi';
    case COUPE_DU_MONDE = 'coupe_du_monde';
    case TEST_MATCH = 'test_match';
    case TOURNEE = 'tournee';
    case AUTRE = 'autre';
}

// app/Enums/CoachRole.php
enum CoachRole: string {
    case SELECTIONNEUR = 'selectionneur';
    case ENTRAINEUR_AVANTS = 'entraineur_avants';
    case ENTRAINEUR_ARRIERES = 'entraineur_arrieres';
    case ENTRAINEUR_DEFENSE = 'entraineur_defense';
    case ENTRAINEUR_TOUCHE = 'entraineur_touche';
    case ENTRAINEUR_MELEE = 'entraineur_melee';
    case PREPARATEUR_PHYSIQUE = 'preparateur_physique';
    case ADJOINT = 'adjoint';
}

// app/Enums/TeamSide.php
enum TeamSide: string {
    case FRANCE = 'france';
    case ADVERSAIRE = 'adversaire';
}

// app/Enums/Continent.php
enum Continent: string {
    case EUROPE = 'europe';
    case OCEANIE = 'oceanie';
    case AMERIQUE_SUD = 'amerique_sud';
    case AMERIQUE_NORD = 'amerique_nord';
    case AFRIQUE = 'afrique';
    case ASIE = 'asie';
}
```

---

## Models Eloquent — Relations attendues

### Country
- hasMany: venues, players, coaches
- hasMany: matches (as opponent), matches (as referee_country)

### Venue
- belongsTo: country
- hasMany: matches

### Competition
- hasMany: editions (CompetitionEdition)

### CompetitionEdition
- belongsTo: competition
- hasMany: matches

### Coach
- belongsTo: country
- hasMany: tenures (CoachTenure)

### CoachTenure
- belongsTo: coach

### Player
- belongsTo: country
- hasMany: lineups (MatchLineup), events (MatchEvent)
- hasMany: substitutionsOff (MatchSubstitution, player_off_id)
- hasMany: substitutionsOn (MatchSubstitution, player_on_id)
- Scopes recommandés: scopeFrench(), scopeByCountry($countryId), scopeActive()

### Match (attention : "Match" est un mot réservé PHP, utiliser `RugbyMatch` comme nom de model)
- belongsTo: venue, opponent (Country), edition (CompetitionEdition), refereeCountry (Country)
- hasMany: lineups (MatchLineup), events (MatchEvent), substitutions (MatchSubstitution)
- Accessors recommandés: result (Victoire/Défaite/Nul), pointDiff, isVictory, isDefeat

### MatchLineup
- belongsTo: match (RugbyMatch), player
- Scopes: scopeFrance(), scopeAdversaire(), scopeStarters(), scopeSubstitutes()

### MatchEvent
- belongsTo: match (RugbyMatch), player
- Scopes: scopeFrance(), scopeAdversaire(), scopeEssais(), scopeCartons()

### MatchSubstitution
- belongsTo: match (RugbyMatch), playerOff (Player), playerOn (Player)
- Scopes: scopeFrance(), scopeAdversaire()

---

## Routes publiques

```
GET /                                   -- Accueil
GET /matches                            -- Tous les matches (Livewire: filtres, recherche, pagination)
GET /matches/{rugbyMatch}               -- Feuille de match complète
GET /joueurs                            -- Joueurs (Livewire: recherche par nom, pays, poste)
GET /joueurs/{player}                   -- Fiche joueur
GET /adversaires                        -- Liste des adversaires par pays
GET /adversaires/{country:code}         -- Bilan vs un pays (ex: /adversaires/NZL)
GET /competitions                       -- Liste des compétitions
GET /competitions/{competition}/editions -- Éditions
GET /competitions/editions/{edition}    -- Détail d'une édition
GET /selectionneurs                     -- Liste des sélectionneurs
GET /selectionneurs/{coach}             -- Fiche sélectionneur + bilan
GET /stades                             -- Carte interactive (Leaflet.js)
GET /records                            -- Records et statistiques
```

---

## Index BDD à créer dans les migrations

```sql
idx_matches_date            matches(match_date)
idx_matches_opponent        matches(opponent_id)
idx_matches_edition         matches(edition_id)
idx_lineups_player          match_lineups(player_id)
idx_lineups_match_team      match_lineups(match_id, team_side)
idx_events_match            match_events(match_id)
idx_events_player           match_events(player_id)
idx_events_type             match_events(event_type)
idx_subs_match              match_substitutions(match_id)
idx_players_country         players(country_id)
idx_tenures_coach           coach_tenures(coach_id)
idx_tenures_dates           coach_tenures(start_date, end_date)
idx_editions_comp_year      competition_editions(competition_id, year)
```

---

## Seeders de base à créer

### CountrySeeder — Les ~50 nations que la France a affrontées
Prioritaires : Angleterre (ENG), Écosse (SCO), Pays de Galles (WAL), Irlande (IRL),
Italie (ITA), Nouvelle-Zélande (NZL), Australie (AUS), Afrique du Sud (RSA),
Argentine (ARG), Fidji (FIJ), Samoa (SAM), Tonga (TGA), Géorgie (GEO),
Japon (JPN), Roumanie (ROU), États-Unis (USA), Canada (CAN), Namibie (NAM).

### CompetitionSeeder
- Tournoi des 5/6 Nations (TOURNOI) — short_name: "5/6 Nations"
- Coupe du Monde (COUPE_DU_MONDE) — short_name: "Coupe du Monde"
- Tests d'automne (TEST_MATCH) — short_name: "Tests d'automne"
- Tournée d'été (TEST_MATCH) — short_name: "Tournée d'été"

### VenueSeeder — Stades principaux
- Stade de France, Saint-Denis (depuis 1998)
- Parc des Princes, Paris (1972-1997)
- Stade Olympique Yves-du-Manoir, Colombes (1920-1972)
- Twickenham, Londres
- Murrayfield, Édimbourg
- Millennium Stadium / Principality Stadium, Cardiff
- Aviva Stadium / Lansdowne Road, Dublin
- Stadio Olimpico, Rome

---

## Conventions de code

- **Langue du code :** Anglais (noms de variables, classes, méthodes)
- **Langue du contenu :** Français (labels, vues, textes affichés)
- **Nommage models :** Singulier anglais (Player, Coach, Venue...)
- **Nommage tables :** Pluriel anglais (players, coaches, venues...)
- **Attention :** Le model pour les matches s'appelle `RugbyMatch` (pas `Match`, mot réservé PHP)
- **Enums :** Dans app/Enums/, backed string enums PHP 8.1+
- **Dates :** Format Y-m-d en BDD, d/m/Y en affichage (format français)
- **Scores :** Toujours france_score en premier, opponent_score en second

---

## Priorités de développement

1. Migrations + Enums + Models + Relations + Seeders de base
2. Interface admin (Filament recommandé) pour saisie de données
3. Front public : matches + joueurs + adversaires
4. Composants Livewire : recherche, filtres, tri
5. Pages stats et records
6. Carte des stades (Leaflet.js)
7. SEO + déploiement O2switch
