# PHASE 2 — Interface Admin Filament

## Objectif
Mettre en place une interface d'administration complète avec Laravel Filament v3
pour saisir et gérer toutes les données du site xvfrance.fr.

---

## Installation

```bash
composer require filament/filament:"^3.0"
php artisan filament:install --panels
```

Créer un utilisateur admin :
```bash
php artisan make:filament-user
```
- Email : admin@xvfrance.fr
- Mot de passe : au choix (dev local)

L'admin sera accessible sur `/admin`.

---

## Resources Filament à créer (11)

Créer une Resource Filament pour chaque Model :
```bash
php artisan make:filament-resource Country --generate
php artisan make:filament-resource Venue --generate
php artisan make:filament-resource Competition --generate
php artisan make:filament-resource CompetitionEdition --generate
php artisan make:filament-resource Coach --generate
php artisan make:filament-resource CoachTenure --generate
php artisan make:filament-resource Player --generate
php artisan make:filament-resource RugbyMatch --generate
php artisan make:filament-resource MatchLineup --generate
php artisan make:filament-resource MatchEvent --generate
php artisan make:filament-resource MatchSubstitution --generate
```

Le flag `--generate` auto-génère formulaires et tables à partir des colonnes,
mais il faudra les personnaliser ensuite.

---

## Personnalisation attendue par Resource

### CountryResource
- **Table** : name, code, continent (badge coloré), flag_emoji
- **Form** : name (required), code (required, max:3), continent (select enum), flag_emoji
- **Tri par défaut** : name ASC
- **Recherche** : name, code

### VenueResource
- **Table** : name, city, country (relation), capacity, opened_year
- **Form** : name, city, country_id (select searchable), capacity, opened_year, latitude, longitude
- **Recherche** : name, city

### CompetitionResource
- **Table** : name, short_name, type (badge)
- **Form** : name, short_name, type (select enum)
- **RelationManager** : editions (liste des CompetitionEdition inline)

### CompetitionEditionResource
- **Table** : label, competition (relation), year, france_ranking
- **Form** : competition_id (select), year, label, france_ranking
- **Recherche** : label
- **Tri** : year DESC

### CoachResource
- **Table** : last_name, first_name, country (flag), birth_date
- **Form** : first_name, last_name, birth_date, birth_city, country_id (select), photo_url
- **RelationManager** : tenures (CoachTenure inline)

### CoachTenureResource
- **Table** : coach (relation), role (badge), start_date, end_date
- **Form** : coach_id (select searchable), role (select enum), start_date, end_date

### PlayerResource ⭐ (la plus importante)
- **Table** : last_name, first_name, country (flag), primary_position (badge), is_active (icon)
- **Form** : first_name, last_name, birth_date, birth_city, country_id (select searchable),
  height_cm, weight_kg, primary_position (select enum), photo_url, is_active (toggle)
- **Filtres** : country_id, primary_position, is_active
- **Recherche** : first_name, last_name
- **Tri par défaut** : last_name ASC

### RugbyMatchResource ⭐⭐ (la plus complexe)
- **Table** : match_date, opponent (flag + name), france_score, opponent_score,
  result (badge Victoire/Défaite/Nul), venue, edition, stage (badge)
- **Form** :
  - Section "Informations générales" :
    match_date, kickoff_time, venue_id (select searchable), opponent_id (select searchable),
    edition_id (select searchable), is_home (toggle), is_neutral (toggle)
  - Section "Score" :
    france_score, opponent_score
  - Section "Détails" :
    stage (select enum), match_number, attendance, referee, referee_country_id (select),
    weather, notes (textarea)
- **RelationManagers** (affichés en onglets sous le formulaire) :
  - LineupsRelationManager : compositions des deux équipes
  - EventsRelationManager : faits de jeu
  - SubstitutionsRelationManager : remplacements
- **Filtres** : opponent_id, stage, edition_id, is_home
- **Recherche** : opponent.name, venue.name
- **Tri par défaut** : match_date DESC

### MatchLineupResource
- **Table** : match (date + adversaire), player (nom), jersey_number, position_played (badge),
  is_starter (icon), is_captain (icon), team_side (badge)
- **Form** : match_id (select), player_id (select searchable), jersey_number,
  is_starter (toggle), position_played (select enum), is_captain (toggle),
  team_side (select enum)

### MatchEventResource
- **Table** : match (date + adversaire), player (nom), event_type (badge coloré),
  minute, team_side (badge)
- **Form** : match_id (select), player_id (select searchable nullable),
  event_type (select enum), minute, team_side (select enum), detail
- **Badges colorés pour event_type** :
  - ESSAI → vert (success)
  - ESSAI_PENALITE → vert clair
  - TRANSFORMATION → bleu (info)
  - PENALITE → bleu (info)
  - DROP → violet (primary)
  - CARTON_JAUNE → jaune (warning)
  - CARTON_ROUGE → rouge (danger)

### MatchSubstitutionResource
- **Table** : match (date + adversaire), player_off, player_on, minute, team_side (badge)
- **Form** : match_id (select), player_off_id (select searchable),
  player_on_id (select searchable), minute, is_tactical (toggle),
  team_side (select enum)

---

## Navigation admin (sidebar)

Organiser les Resources en groupes dans la sidebar :

```php
// Dans chaque Resource, ajouter :
protected static ?string $navigationGroup = 'Nom du groupe';
protected static ?int $navigationSort = 1; // ordre dans le groupe
```

Groupes :
1. **Matches** : RugbyMatch (sort 1)
2. **Joueurs & Staff** : Player (sort 1), Coach (sort 2), CoachTenure (sort 3)
3. **Compétitions** : Competition (sort 1), CompetitionEdition (sort 2)
4. **Référentiel** : Country (sort 1), Venue (sort 2)
5. **Détails match** : MatchLineup (sort 1), MatchEvent (sort 2), MatchSubstitution (sort 3)

Les Resources MatchLineup, MatchEvent, MatchSubstitution sont aussi accessibles
en tant que RelationManagers depuis RugbyMatchResource (double accès).

---

## Labels en français

Tous les labels de l'admin doivent être en français :
- Navigation, titres de pages, labels de formulaires, colonnes de tableaux
- Utiliser les propriétés Filament :
  - `$modelLabel` et `$pluralModelLabel` sur chaque Resource
  - `->label()` sur chaque champ de formulaire
  - `->label()` sur chaque colonne de table

Exemples :
```php
protected static ?string $modelLabel = 'Match';
protected static ?string $pluralModelLabel = 'Matches';
protected static ?string $navigationLabel = 'Matches';
```

---

## Widgets Dashboard (optionnel mais recommandé)

Sur la page d'accueil admin `/admin` :
- Nombre total de matches
- Nombre total de joueurs (FR vs total)
- Dernier match saisi
- Bilan global (victoires / défaites / nuls)

---

## Ordre de création recommandé

1. Installer Filament + créer l'utilisateur admin
2. Resources simples : Country, Venue, Competition, CompetitionEdition
3. Resources staff : Coach, CoachTenure
4. Resource Player
5. Resource RugbyMatch (la plus complexe, avec RelationManagers)
6. Resources détails : MatchLineup, MatchEvent, MatchSubstitution
7. Navigation groupée + labels français
8. Widgets dashboard
