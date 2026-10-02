# xvfrance.fr

Site de référence francophone sur l'histoire du XV de France de rugby depuis 1906 :
tous les matches, compositions complètes (France et adversaires), marqueurs,
sélectionneurs et compétitions.

## Stack

- Laravel 13 · PHP 8.3 · MySQL 8
- Blade + Livewire · Tailwind CSS v4 (Vite)
- Admin : Filament 4 (`/admin`)

## Installation

```bash
composer install
npm install && npm run build   # compile le CSS dans public/build
cp .env.example .env
php artisan key:generate
# Renseigner DB_* dans .env
php artisan migrate --seed
php artisan serve
```

## Données

```
database/data/
├── sources/results.csv     # Résultats 1950 → 2026 (source de xv:import-csv)
├── sources/venue_coordinates.csv  # Coordonnées des stades (précision « stade » ou « ville »)
└── matches/{année}/*.json  # Feuilles de match détaillées (compos, events, remplacements)
```

Format des JSON : voir `docs/specs/import-match-data.md`.

## Commandes Artisan

Toutes les commandes du projet sont préfixées `xv:` (`php artisan list xv`).

| Commande | Rôle |
|---|---|
| `xv:import-csv {file}` | Importe les matches depuis le CSV de résultats |
| `xv:import-historical` | Importe les matches 1906-1949 depuis equipe-france.fr |
| `xv:clean-feminine` | Supprime les matches féminins importés par erreur |
| `xv:fix-venues` | Corrige les stades mal attribués |
| `xv:geocode-venues` | Complète le CSV des coordonnées via OpenStreetMap (ne touche pas la base) |
| `xv:import-venue-coordinates` | Applique le CSV des coordonnées aux stades |
| `xv:seed-1906` | Feuille de match complète du premier match (1906) |
| `xv:generate-slugs` | Génère les slugs manquants (matches, joueurs) |
| `xv:validate-match-data {path}` | Valide des JSON de feuilles de match |
| `xv:import-match-data {path}` | Importe des JSON de feuilles de match |

### Reconstruire la base de zéro

Ordre indicatif, à exécuter après `php artisan migrate:fresh --seed` :

```bash
php artisan xv:import-csv database/data/sources/results.csv
php artisan xv:import-historical
php artisan xv:clean-feminine
php artisan xv:fix-venues
php artisan xv:import-venue-coordinates
php artisan xv:seed-1906
php artisan xv:generate-slugs
php artisan xv:import-match-data database/data/matches
```

Les commandes `import-historical`, `clean-feminine` et `fix-venues` interrogent
equipe-france.fr, et `geocode-venues` interroge OpenStreetMap (option `--delay` entre les requêtes).
Les lignes de précision « ville » du CSV des coordonnées sont à affiner à la main. Les commandes d'import et de
nettoyage acceptent `--dry-run`.

## Déploiement (O2switch)

Le déploiement se lance depuis la machine locale, dont l'IP doit être autorisée en SSH
(cPanel > Autorisation SSH). Le CSS est compilé localement : `public/build` reste hors de git.

```bash
cp .deploy.env.example .deploy.env   # une seule fois : accès SSH et dossier sur le serveur
./deploy.sh --dry-run                # liste des fichiers qui seraient envoyés
./deploy.sh                          # tests, build, envoi, composer, migrations, caches
```

Seul le contenu **commité** est déployé (le script refuse si des modifications sont en cours).
Sur le serveur, `.env`, `vendor/`, `storage/` et `bootstrap/cache/` ne sont jamais écrasés.
Si une étape échoue côté serveur, le site reste en maintenance : corriger, puis relancer.

Premier déploiement : créer le `.env` de production sur le serveur (`APP_ENV=production`,
`APP_DEBUG=false`, accès MySQL, `APP_KEY` via `php artisan key:generate`) et faire pointer la
racine web du domaine sur le dossier `public/` de l'application.

## Documentation

- `CLAUDE.md` — schéma BDD, conventions, routes
- `docs/specs/` — spécifications en vigueur
- `docs/archive/` — specs des phases terminées
