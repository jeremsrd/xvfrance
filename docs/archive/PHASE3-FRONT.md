# PHASE 3 — Front public avec Livewire

## Objectif

Créer les pages publiques du site xvfrance.fr avec un design sobre et élégant,
inspiré des sites FFR et World Rugby. 4 pages prioritaires + layout global.

---

## 1. Design System

### Palette de couleurs

```css
:root {
    --bleu-france: #002395;
    --bleu-france-light: #003399;
    --bleu-france-dark: #001a6e;
    --rouge-france: #ED2939;
    --rouge-france-light: #FF3344;
    --blanc: #FFFFFF;
    --gris-50: #F8F9FA;
    --gris-100: #F1F3F5;
    --gris-200: #E9ECEF;
    --gris-300: #DEE2E6;
    --gris-400: #CED4DA;
    --gris-500: #ADB5BD;
    --gris-600: #6C757D;
    --gris-700: #495057;
    --gris-800: #343A40;
    --gris-900: #212529;
    --victoire: #198754;
    --defaite: #DC3545;
    --nul: #FFC107;
    --or: #D4AF37;
}
```

### Typographie

Utiliser Tailwind CSS via CDN Play. Polices recommandées :
- Titres : font-bold, tracking-tight
- Corps : text-gray-700, leading-relaxed
- Stats/chiffres : font-mono ou tabular-nums

### Principes visuels
- Fond blanc principal, sections alternées gris-50
- Bleu France pour les headers, la navigation, les accents
- Rouge France pour les CTA, les défaites, les alertes
- Vert pour les victoires, jaune/or pour les nuls
- Drapeaux emoji pour les pays adverses
- Ombres subtiles (shadow-sm) sur les cartes
- Coins arrondis doux (rounded-lg)
- Pas de surcharge visuelle — laisser respirer le contenu

---

## 2. Layout principal (resources/views/layouts/app.blade.php)

### Header / Navigation
- Logo "XV FRANCE" ou texte stylisé à gauche
- Fond bleu France (#002395), texte blanc
- Navigation horizontale : Accueil, Matches, Joueurs, Adversaires, Compétitions, Sélectionneurs
- Responsive : hamburger menu sur mobile
- Sous le header : fil d'Ariane discret sur fond gris-100

### Footer
- Fond gris-900, texte gris-400
- 3 colonnes : Navigation rapide, À propos, Contact
- Mention "xvfrance.fr — L'histoire du XV de France depuis 1906"
- Lien vers le GitHub (optionnel)

### Structure de page type
```html
<x-app-layout>
    <!-- Fil d'Ariane -->
    <!-- Titre de page avec fond bleu ou bannière -->
    <!-- Contenu principal -->
    <!-- Sidebar optionnelle pour stats contextuelles -->
</x-app-layout>
```

---

## 3. Page d'accueil (route: /)

### Controller : HomeController@index

### Sections de la page d'accueil :

#### a) Hero / Bannière
- Fond bleu France avec texte blanc
- Titre : "XV de France" en grand
- Sous-titre : "L'histoire complète depuis 1906"
- Stats rapides en ligne : X matches | X victoires | X% de réussite

#### b) Dernier match
- Grande carte avec le résultat du match le plus récent
- Drapeaux des deux équipes, score bien visible
- Date, stade, compétition
- Badge Victoire/Défaite/Nul coloré

#### c) Derniers résultats (5 derniers matches)
- Liste compacte : date | adversaire (drapeau + nom) | score | résultat (badge)
- Lien "Voir tous les matches →"

#### d) Statistiques globales
- 4 cartes en grille :
  - Total matches joués
  - Victoires (nombre + pourcentage)
  - Plus gros score (ex: France 87 - 10 Namibie)
  - Adversaire le plus affronté (ex: Angleterre, X matches)

#### e) Bilan par décennie (optionnel)
- Petit tableau : décennie | matches | V | D | N | %
- De 1900s à 2020s

### Données nécessaires (HomeController) :
```php
$latestMatch = RugbyMatch::with(['opponent', 'venue', 'edition.competition'])
    ->orderBy('match_date', 'desc')->first();

$recentMatches = RugbyMatch::with(['opponent'])
    ->orderBy('match_date', 'desc')->take(5)->get();

$stats = [
    'total' => RugbyMatch::count(),
    'victories' => RugbyMatch::whereColumn('france_score', '>', 'opponent_score')->count(),
    'defeats' => RugbyMatch::whereColumn('france_score', '<', 'opponent_score')->count(),
    'draws' => RugbyMatch::whereColumn('france_score', '=', 'opponent_score')->count(),
];

$biggestWin = RugbyMatch::orderByRaw('(france_score - opponent_score) DESC')->first();

$mostFaced = Country::withCount('matchesAsOpponent')
    ->orderBy('matches_as_opponent_count', 'desc')->first();
```

---

## 4. Liste des matches (route: /matches) — Composant Livewire

### Composant : app/Livewire/MatchList.php

### Fonctionnalités Livewire (réactives, sans rechargement) :
- **Recherche** par adversaire (texte libre)
- **Filtre par compétition** (select : Toutes, 6 Nations, Coupe du Monde, Tests...)
- **Filtre par résultat** (select : Tous, Victoires, Défaites, Nuls)
- **Filtre par décennie** (select : Toutes, 1900s, 1910s... 2020s)
- **Filtre par lieu** (select : Domicile, Extérieur, Tous)
- **Tri** par date (ASC/DESC), par score
- **Pagination** : 20 matches par page

### Affichage : Tableau responsive
```
Date        | Adversaire       | Score     | Résultat  | Compétition      | Stade
01/01/1906  | 🇳🇿 N-Zélande   | 8 - 38    | Défaite   | Test match       | Parc des Princes
...
```

- Badge coloré pour le résultat (vert/rouge/jaune)
- Clic sur la ligne → page fiche match
- Score en gras, france_score toujours à gauche
- Sur mobile : affichage carte empilée au lieu du tableau

### Compteur de résultats
- "X matches trouvés" au-dessus du tableau
- Se met à jour en temps réel avec les filtres

### En-tête de page
- Titre "Tous les matches" + sous-titre "900 matches depuis 1906"
- Barre de filtres bien visible sous le titre

---

## 5. Fiche match (route: /matches/{rugbyMatch})

### Controller : MatchController@show

### Structure de la page :

#### a) En-tête du match
- Grande bannière avec fond bleu France
- Au centre : drapeaux des deux équipes + score en très grand
- "FRANCE  43 — 31  🇳🇿 Nouvelle-Zélande"
- Badge résultat (VICTOIRE en vert)
- Date complète : "Samedi 31 octobre 1999"
- Compétition + phase : "Coupe du Monde — Demi-finale"
- Stade + ville : "Twickenham, Londres"
- Affluence si disponible

#### b) Détails du match (sous la bannière)
- Carte avec infos complémentaires :
  - Arbitre (+ nationalité)
  - Conditions météo (si renseigné)
  - Numéro de test (ex: "Test #XXX")
  - Notes contextuelles

#### c) Composition de l'équipe France (si disponible — Niveau 2)
- Deux colonnes : XV de départ | Remplaçants
- Numéro + Nom + Poste
- Capitaine marqué avec un (C)
- Lien vers la fiche joueur

#### d) Composition adverse (si disponible — Niveau 2)
- Même format

#### e) Faits de jeu (si disponibles — Niveau 3)
- Timeline chronologique : minute | événement | joueur
- Icônes : 🏉 essai, 🥾 pénalité, 🦶 drop, 🔄 transformation, 🟨 carton jaune, 🟥 carton rouge

#### f) Remplacements (si disponibles)

**Note** : Pour l'instant (Niveau 1), seuls l'en-tête et les détails seront remplis.
Les compositions et faits de jeu s'afficheront quand les données seront saisies.
Prévoir les sections avec un message "Données à venir" si vides.

---

## 6. Bilan par adversaire (route: /adversaires/{country:code})

### Controller : OpponentController@show

### Structure de la page :

#### a) En-tête
- Drapeau géant de l'adversaire + nom
- "France vs Nouvelle-Zélande"
- Bilan résumé : "X matches — Y victoires — Z défaites — W nuls"

#### b) Statistiques du bilan
- 4 cartes : Victoires, Défaites, Nuls, % de victoires
- Barre de progression visuelle (vert victoires / rouge défaites / jaune nuls)
- Points marqués par la France (total) vs points encaissés (total)

#### c) Graphique d'évolution (optionnel, en Niveau 2+)
- Courbe des résultats dans le temps

#### d) Liste de tous les matches
- Même format que la liste globale mais filtrée sur cet adversaire
- Réutiliser le composant Livewire MatchList avec un filtre pré-appliqué

#### e) Records du duel
- Plus grosse victoire française
- Plus grosse défaite française
- Plus gros score cumulé
- Dernier match joué

---

## 7. Page liste des adversaires (route: /adversaires)

### Controller : OpponentController@index

- Liste de tous les pays que la France a affrontés
- Classés par nombre de matches (du plus affronté au moins)
- Format carte : drapeau | nom | nb matches | bilan (V-D-N) | % victoires
- Clic → page bilan détaillé
- Regroupement possible par continent

---

## 8. Composants Blade réutilisables (resources/views/components/)

### match-score-card.blade.php
- Carte compacte affichant un match : date, adversaire, score, résultat badge
- Réutilisé sur l'accueil, la liste, la fiche adversaire

### result-badge.blade.php
- Badge coloré : "Victoire" (vert), "Défaite" (rouge), "Nul" (jaune)
- Accepte le résultat en prop

### country-flag.blade.php
- Affiche le drapeau emoji + nom du pays
- Accepte le Country en prop

### stat-card.blade.php
- Carte de statistique : icône + valeur + label
- Réutilisée sur l'accueil et la fiche adversaire

---

## 9. Routes (routes/web.php)

```php
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/matches', MatchList::class)->name('matches.index');
Route::get('/matches/{rugbyMatch}', [MatchController::class, 'show'])->name('matches.show');
Route::get('/adversaires', [OpponentController::class, 'index'])->name('opponents.index');
Route::get('/adversaires/{country:code}', [OpponentController::class, 'show'])->name('opponents.show');
```

---

## 10. SEO de base

- Balise `<title>` dynamique par page :
  - Accueil : "XV de France — L'histoire complète depuis 1906"
  - Matches : "Tous les matches du XV de France"
  - Fiche match : "France X - Y Adversaire — Date"
  - Adversaire : "France vs Adversaire — Bilan complet"
- Meta description dynamique
- URL propres et lisibles

---

## 11. Ordre de développement recommandé

1. Layout principal (header, footer, navigation)
2. Composants Blade réutilisables (badges, cartes, drapeaux)
3. Page d'accueil (HomeController + vue)
4. Liste des matches (Livewire MatchList)
5. Fiche match (MatchController + vue)
6. Page adversaires (liste + bilan détaillé)
7. Responsive mobile
8. SEO (titles, meta)
