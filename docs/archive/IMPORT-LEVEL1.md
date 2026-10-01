# IMPORT — Niveau 1 : Résultats historiques depuis CSV Kaggle

## Contexte

Un fichier CSV de résultats internationaux de rugby (1871-2024) est placé dans
`database/data/rugby_results.csv`. Il contient tous les matches internationaux
de toutes les nations. On veut filtrer et importer uniquement les matches
impliquant la France.

## Étape 1 — Analyser le CSV

Avant de coder l'import, lis les premières lignes du CSV pour identifier :
- Les noms de colonnes exacts (date, home_team, away_team, home_score, away_score, etc.)
- Le format de date utilisé
- Comment la France est nommée ("France" probablement)
- Les valeurs possibles pour competition, venue, neutral

Affiche les 5 premières lignes et les colonnes pour confirmer avant de continuer.

## Étape 2 — Créer la commande Artisan

Créer `app/Console/Commands/ImportMatchesFromCsv.php` :

```bash
php artisan make:command ImportMatchesFromCsv
```

La commande doit :

### a) Lire le CSV et filtrer les lignes France
- Garder uniquement les lignes où home_team = "France" OU away_team = "France"

### b) Pour chaque match France, déterminer :
- `opponent` : l'autre équipe (pas la France)
- `france_score` / `opponent_score` : selon si France est home ou away
- `is_home` : true si France est home_team
- `is_neutral` : selon la colonne neutral du CSV (si elle existe)
- `match_date` : parser la date du CSV

### c) Gérer les pays adverses
- Chercher le country dans la table `countries` par nom
- Si le pays n'existe pas, le CRÉER automatiquement avec :
  - name = nom du CSV
  - code = les 3 premières lettres en majuscules (placeholder, à corriger manuellement)
  - continent = 'europe' par défaut (placeholder)
  - flag_emoji = '' (vide)
- Logger les pays créés automatiquement pour review

### d) Gérer les stades/villes
- Si le CSV contient une colonne venue ou city :
  - Chercher le venue par nom dans la table `venues`
  - Si le venue n'existe pas, le CRÉER automatiquement
  - Lier le venue au pays hôte si possible
- Si pas de colonne venue, laisser venue_id à null

### e) Gérer les compétitions
- Si le CSV contient une colonne competition/tournament :
  - Mapper les noms vers les compétitions existantes :
    - Tout ce qui contient "Six Nations" ou "Five Nations" ou "4 Nations" → competition "Tournoi des 5/6 Nations"
    - "World Cup" ou "Rugby World Cup" → competition "Coupe du Monde"
    - "Autumn" ou "November" ou "End of Year" → competition "Tests d'automne"
    - "Summer" ou "June" ou "Mid-Year" ou "Tour" → competition "Tournée d'été"
    - Sinon → NULL (test match non catégorisé)
  - Créer ou trouver la CompetitionEdition correspondante (competition + année)

### f) Créer le match
- Vérifier qu'il n'existe pas déjà (match_date + opponent_id) pour éviter les doublons
- Insérer dans la table `matches`

### g) Afficher un résumé
- Nombre de matches importés
- Nombre de pays créés automatiquement
- Nombre de stades créés automatiquement
- Nombre de doublons ignorés
- Liste des pays créés automatiquement (pour review)

## Étape 3 — Options de la commande

```bash
# Import complet
php artisan import:matches database/data/rugby_results.csv

# Import dry-run (affiche ce qui serait importé sans écrire en BDD)
php artisan import:matches database/data/rugby_results.csv --dry-run

# Import d'une période spécifique
php artisan import:matches database/data/rugby_results.csv --from=1987 --to=2024
```

## Étape 4 — Compléter les données manquantes

Après l'import, créer une deuxième commande pour compléter les codes pays
et les continents des pays créés automatiquement :

```bash
php artisan fix:countries
```

Cette commande affiche les pays avec un code placeholder (3 premières lettres)
et demande confirmation pour mettre à jour avec les vrais codes World Rugby.

## Mapping des noms de pays (corrections courantes)

Le CSV peut utiliser des noms différents de notre BDD. Prévoir un mapping :

```php
$countryMapping = [
    'New Zealand' => 'Nouvelle-Zélande',
    'South Africa' => 'Afrique du Sud',
    'United States' => 'États-Unis',
    'British Isles' => 'Lions Britanniques',
    'British & Irish Lions' => 'Lions Britanniques',
    'USSR' => 'Union Soviétique',
    'Czechoslovakia' => 'Tchécoslovaquie',
    'Ivory Coast' => 'Côte d\'Ivoire',
    // Garder les noms anglais si pas de traduction évidente
    // Le CSV utilise probablement des noms anglais
];
```

Alternative : stocker les noms en anglais ET en français dans countries.
Ajouter une colonne `name_en` à la table countries si besoin.

## Notes importantes

- Le CSV contient TOUS les matches internationaux, pas seulement la France.
  On filtre côté code.
- Certains matches très anciens (1906-1930) peuvent avoir des données
  incomplètes — c'est normal.
- Le dataset va jusqu'à 2024. Les matches 2025-2026 devront être saisis
  manuellement ou importés depuis une autre source.
- Les compositions d'équipe et les marqueurs ne sont PAS dans ce CSV.
  C'est le Niveau 2 et 3 de l'import.
