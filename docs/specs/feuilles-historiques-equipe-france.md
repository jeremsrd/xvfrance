# Feuilles de match historiques depuis equipe-france.fr

État au 05/10/2026, spec du **prochain chantier**, pas encore commencé.

## Objectif

Passer de 23 feuilles de match détaillées (novembre 2024 → juillet 2026, plus 1906) à près de 839 :
compositions des deux équipes, capitaines, marqueurs, arbitre, et minutes des points quand le site les donne.
Le tout **par script, sans modèle de langage**, comme pour les scripts Wikipédia (voir `feuilles-de-match-suite.md`).

## La source est déjà téléchargée

Les 844 pages de match d'equipe-france.fr sont en cache dans `storage/app/equipe-france/matches/{site_id}.html`
(hors git), récupérées par `php artisan xv:fetch-equipe-france --venues`. Relancer cette commande ne retélécharge
pas ce qui est déjà en cache.

Correspondance page ↔ match : `database/data/sources/equipe_france_matches.csv`, avec les colonnes `site_id`, `date`,
`home`, `away`, les scores et `url`. Le rapprochement avec la base (même adversaire, date à un jour près, alias
de pays) est déjà codé dans `app/Console/Commands/Import/ApplyEquipeFrance.php` (`findMatch`,
`COUNTRY_ALIASES`).

Couverture mesurée sur les 844 pages :

| Bloc | Pages |
|------|-------|
| Effectifs (compositions des deux équipes) | 844, y compris 1906-1930 |
| Arbitre | 844 |
| Marqueurs (résumé par type de point) | toutes |
| Déroulement du match (points avec minute) | 840, souvent vide pour les matches anciens |

## Structure HTML d'une page de match

Attention : `dom` et `ext` désignent l'équipe **qui reçoit** et l'équipe **qui se déplace**, pas France et
adversaire. Il faut les rapporter à `home` et `away` du relevé.

**Compositions** : `div.matcheffectif`.

- Titulaires dans `div.dom` puis `div.ext`, chacun sous `div.m5 > ul > li`.
- Ensuite, `<h3>Remplaçants</h3>` suivi de nouveau de `div.dom` et `div.ext`.
- Chaque joueur : `<span class="num">N</span>` et `<span>Nom</span>`. Côté `ext`, le numéro vient **après** le nom.
- Les joueurs français ont un lien `<a href="/rugby/{slug}">`, un identifiant précieux pour éviter les doublons.
  Les joueurs adverses n'ont que leur nom.
- Le capitaine est marqué par `<strong class="capitaine">C</strong>` dans le `li`.
- Les numéros 1 à 15 suivent la numérotation moderne (1 pilier gauche … 15 arrière), même pour 1925.
  À vérifier sur quelques matches anciens.

**Marqueurs** : `div.matchbut`, avec `div.dom` et `div.ext`.

- `div.buttype` donne le type : Essai(s), Transformation(s), Pénalité(s), Drop, Essai de pénalité.
- Suit une `ul.buttypeliste` avec un `li` par joueur : `span.nomjoueur`, et `span.num` « (2) » pour le nombre.
- 19 blocs `buttype` sont vides : à ignorer.

**Arbitre** : `#matchofficiel li` contient « Arbitre : Nom ». Le pays de l'arbitre n'est pas donné.

**Déroulement** : `div.matchderoulement table tr`, avec trois cellules.

- `td.dom` ou `td.ext` contient `span.nomjoueur` et `<em>type</em>`.
- `td.temps` donne la minute (« 19' »).
- Types rencontrés : essai, pénalité, transformation, essai de pénalité. **Ni drop, ni carton, ni remplacement.**

Ce que le site ne donne pas : les cartons, les remplacements avec leur minute, et le pays de l'arbitre.
Les remplaçants sont listés, mais on ne sait pas qui est entré ni quand.

## Plan proposé

1. **Analyseur.** Un script (commande `xv:` ou script Python dans `scripts/`) lit les pages en cache et écrit
   des JSON au format de `docs/specs/import-match-data.md`. Il remplit : `lineups` des deux côtés (numéro,
   nom, titulaire ou non, capitaine, poste déduit du numéro), `events` (points ; minute si le déroulement la
   donne, sinon `null`), `referee` et `substitutions: []`.
2. **Étalonnage.** Le comparer aux 23 feuilles déjà vérifiées (2024-2026), qui servent de vérité de référence,
   avant de générer le reste.
3. **Génération** de toutes les feuilles, puis `php artisan xv:validate-match-data`. Le contrôle « points =
   score selon le barème de l'époque » (`EventType::points()`) détecte les lectures fausses. Il signale aussi
   les remplaçants sans remplacement et les noms proches d'un joueur existant.
4. **Import par décennie** avec `xv:import-match-data`, en lisant les avertissements de doublons de joueurs.
   **Ne pas écraser** les 35 JSON existants : 22 feuilles complètes (plus 1906, saisi par `xv:seed-1906`), 13 matches créés sans composition le
   05/10/2026 (1940, 1978, 1987, 1989, 1998, 2003, 2004 ×3, 2005, 2008, 2009, 2024). Pour ces 13, on peut
   ajouter des compositions.

## Points de vigilance

- **Doublons de joueurs.** C'est le risque principal : homonymes (frères, pères et fils), graphies différentes
  selon les époques, accents. `PlayerResolverService` compare les noms sans accents depuis le 05/10/2026. Pour
  les joueurs français, le slug du lien `/rugby/{slug}` est un identifiant stable à exploiter, par exemple en
  le stockant ou en le comparant au `slug` de `players`.
- **Barème.** `EventType::points()` gère déjà les époques : essai à 3, 4 puis 5 points, drop à 4 points avant
  juillet 1948, essai de pénalité à 7 points depuis 2017. Les tout premiers matches peuvent comporter des
  points rares, comme le but sur marque : un écart de points avant 1930 est à examiner avant de conclure
  à une erreur de lecture.
- **Scores divergents.** Le site s'est trompé au moins une fois (quart de finale 1987 contre les Fidji : 34-16
  au lieu de 31-16). Le score de la base fait foi (voir `docs/sources.md`). Une feuille dont les points ne
  collent pas est à vérifier à la main, jamais à « corriger » vers le site.
- **Matches absents du site.** Angleterre 1907 et Allemagne 1932-1934 n'y figurent pas : pas de feuille pour eux.
- **Économie de tokens.** Tout se fait par script local sur le cache. Pas de WebFetch, pas d'agent par match.
