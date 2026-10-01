# PHASE 3b — Pages Joueurs, Compétitions, Sélectionneurs

## Design

Même charte graphique que les pages existantes :
- Bleu France (#002395), blanc, rouge (#ED2939)
- Victoire = vert (#198754), Défaite = rouge (#DC3545), Nul = jaune (#FFC107)
- Réutiliser les composants Blade existants (result-badge, country-flag, stat-card, match-score-card)
- Tailwind CSS via CDN

---

## 1. Pages Joueurs

### 1a. Liste des joueurs (route: /joueurs) — Composant Livewire

**Composant : app/Livewire/PlayerList.php**

Fonctionnalités Livewire (réactives) :
- **Recherche** par nom (first_name ou last_name)
- **Filtre par nationalité** (select : Toutes, France, puis les autres pays)
- **Filtre par poste** (select : Tous, puis chaque valeur de l'enum PlayerPosition avec label français)
- **Filtre actif/retraité** (select : Tous, En activité, Retraité)
- **Tri** par nom (A-Z), par nombre de sélections (calculé), par cap_number
- **Pagination** : 30 joueurs par page

Affichage : Grille de cartes (3 colonnes desktop, 1 mobile)
Chaque carte joueur :
- Photo miniature ronde (ou placeholder avec initiales si pas de photo)
- Nom complet en gras
- Drapeau + pays
- Poste (badge coloré)
- Nombre de sélections (calculé depuis match_lineups)
- Symbole † si décédé

En-tête de page :
- Titre "Joueurs" + compteur "X joueurs référencés"
- Barre de filtres

**Note importante :** Pour l'instant la table players est quasiment vide
(aucun joueur n'a été importé, seulement la structure existe).
Prévoir un message "Aucun joueur trouvé — les données arrivent bientôt"
si la table est vide. La page doit quand même fonctionner et être prête
pour quand les joueurs seront saisis.

### 1b. Fiche joueur (route: /joueurs/{player})

**Controller : PlayerController@show**

Structure de la page :

#### a) En-tête joueur
- Photo à gauche (grande, ronde, ou placeholder)
- Nom complet en grand + surnom entre guillemets si renseigné
- Drapeau + nationalité
- Poste principal (badge)
- Infos bio : date de naissance (+ âge ou † date décès), ville de naissance,
  taille, poids
- N° d'international (cap_number) si renseigné

#### b) Statistiques du joueur (cartes)
- Nombre de sélections (COUNT match_lineups WHERE player_id)
- Nombre de titularisations (COUNT match_lineups WHERE is_starter = true)
- Nombre d'essais (COUNT match_events WHERE event_type = ESSAI)
- Nombre de fois capitaine (COUNT match_lineups WHERE is_captain = true)
- Points marqués (calculé : essais × 5 + transformations × 2 + pénalités × 3 + drops × 3)
  Note : le barème a changé historiquement, mais on simplifie avec le barème actuel

#### c) Liste des matches joués
- Tableau : date | adversaire | score | résultat | poste | titulaire/remplaçant | capitaine
- Tri par date DESC
- Clic sur le match → fiche match
- Si aucun match → "Aucune sélection enregistrée pour l'instant"

#### d) Faits de jeu du joueur
- Liste des essais, pénalités, drops, cartons
- Format : date | adversaire | type d'événement | minute
- Si aucun événement → section masquée

### Routes joueurs
```php
Route::get('/joueurs', PlayerList::class)->name('players.index');
Route::get('/joueurs/{player}', [PlayerController::class, 'show'])->name('players.show');
```

---

## 2. Pages Compétitions

### 2a. Liste des compétitions (route: /competitions)

**Controller : CompetitionController@index**

Affichage simple :
- Une carte par compétition avec :
  - Nom de la compétition
  - Type (badge : Tournoi, Coupe du Monde, Tests, Tournée)
  - Nombre d'éditions
  - Nombre total de matches
- Clic → liste des éditions

### 2b. Éditions d'une compétition (route: /competitions/{competition})

**Controller : CompetitionController@show**

- Titre : nom de la compétition
- Liste des éditions (tableau ou grille) triées par année DESC :
  - Label (ex: "6 Nations 2024")
  - Année
  - Classement France si renseigné
  - Nombre de matches
  - Bilan V-D-N dans cette édition
- Clic sur une édition → détail

### 2c. Détail d'une édition (route: /competitions/editions/{competitionEdition})

**Controller : CompetitionEditionController@show**

- Titre : label de l'édition (ex: "Coupe du Monde 2023")
- Classement final de la France si renseigné
- Bilan : V-D-N dans cette édition
- Liste de tous les matches de l'édition :
  - Réutiliser le format existant (date, adversaire, score, résultat, stade, phase)
  - Triés par date ASC (chronologique dans le tournoi)

### Routes compétitions
```php
Route::get('/competitions', [CompetitionController::class, 'index'])->name('competitions.index');
Route::get('/competitions/{competition}', [CompetitionController::class, 'show'])->name('competitions.show');
Route::get('/competitions/editions/{competitionEdition}', [CompetitionEditionController::class, 'show'])->name('editions.show');
```

---

## 3. Pages Sélectionneurs

### 3a. Liste des sélectionneurs (route: /selectionneurs)

**Controller : CoachController@index**

Affichage : liste des coaches ayant eu le rôle SELECTIONNEUR
(filtrer via coach_tenures WHERE role = 'selectionneur')

Pour chaque sélectionneur, afficher :
- Photo (ou placeholder)
- Nom complet
- Période : "de [start_date] à [end_date]" (ou "depuis [start_date]" si en cours)
- Bilan : X matches, Y victoires, Z défaites, W nuls (calculé depuis les matches
  dans la période du mandat)
- % de victoires
- Trié par start_date DESC (le plus récent en premier)

**Note :** Comme les joueurs, la table coaches est probablement vide.
Prévoir un message "Données à venir" si vide.

### 3b. Fiche sélectionneur (route: /selectionneurs/{coach})

**Controller : CoachController@show**

#### a) En-tête
- Photo + nom complet
- Nationalité (drapeau)
- Date de naissance
- Rôle(s) occupé(s) avec les périodes (via coach_tenures)

#### b) Bilan en tant que sélectionneur
- 4 stat-cards : Matches, Victoires, Défaites, Nuls
- % de victoires (barre de progression)
- Calculé : matches WHERE match_date BETWEEN tenure.start_date AND tenure.end_date

#### c) Liste des matches sous son mandat
- Même format tableau que partout ailleurs
- Filtré sur la période du mandat de sélectionneur
- Tri date DESC

### Routes sélectionneurs
```php
Route::get('/selectionneurs', [CoachController::class, 'index'])->name('coaches.index');
Route::get('/selectionneurs/{coach}', [CoachController::class, 'show'])->name('coaches.show');
```

---

## 4. Mise à jour de la navigation

Ajouter dans le header les liens manquants :
- Accueil | Matches | **Joueurs** | Adversaires | **Compétitions** | **Sélectionneurs**

---

## 5. Ordre de développement

1. Mise à jour navigation (ajouter les 3 nouveaux liens)
2. Pages Joueurs (Livewire PlayerList + PlayerController@show)
3. Pages Compétitions (3 controllers + vues)
4. Pages Sélectionneurs (CoachController + vues)
5. Vérifier que toutes les routes retournent 200
