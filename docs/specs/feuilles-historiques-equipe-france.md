# Feuilles de match historiques depuis equipe-france.fr

État au 06/10/2026 : **feuilles générées, importées et déployées** (834 matches détaillés sur 839).
Reste à reprendre à la main les points de 29 matches (voir « Reste à faire »).

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

## Réalisé (05/10/2026)

- `scripts/equipe_france/feuilles.py` lit le cache et le relevé, rapproche chaque page d'un match de la base
  (même adversaire, date à un jour près) et écrit `database/data/matches/{année}/{date}-{code}.json`. Il ne touche
  pas une feuille qui a déjà des compositions. Une feuille créée sans composition est complétée en gardant ses
  champs. `--out DIR --all` régénère tout ailleurs, pour l'étalonnage.
- **811 feuilles** écrites (12 complétées), 0 erreur à `xv:validate-match-data`, importées en local :
  6 501 joueurs créés, 1 229 joueurs français. Aucun slug du site n'a été fusionné avec un autre.
- **Compositions.** Les numéros 1 à 15 sont titulaires, avec le poste déduit du numéro. Avant 1920 environ, le site
  ne numérote pas les avants : un bloc de 15 joueurs ou moins est entièrement titulaire. Un joueur sans numéro, ou
  dont le numéro est en double ou dépasse 23, reçoit le premier numéro libre, **sans poste**. Le schéma du terrain
  est alors masqué. Les postes vides sont possibles depuis la migration `2026_10_05_200000` (`position_played` et
  `primary_position` nullables). `xv:compute-primary-positions` (lancée par `deploy.sh`) donne à chaque joueur
  son poste le plus joué.
- **Points.** Le script retient la première version dont le total égale le score de la base, dans cet ordre :
  déroulement avec minutes (+ drops du résumé), déroulement dédoublonné (le site répète parfois une ligne à une
  minute d'écart), puis résumé sans minutes. Si aucune version ne colle, la feuille n'a **aucun point** :
  le validateur refuserait une feuille fausse.
- **Noms.** Le découpage prénom / nom reprend le nom en base s'il existe, puis les exceptions de
  `database/data/sources/equipe_france_name_splits.csv` (noms composés argentins, noms à trait d'union), puis les
  particules (de, van, du…), et sinon le dernier mot. Le script retire aussi « ★ », les surnoms entre guillemets,
  « Dr » et les parenthèses, et corrige la casse « Mckenzie » / « O'brien ».
- **Homonymes de générations différentes.** `PlayerResolverService` ne rattache plus un joueur existant qui n'a
  pas joué à moins de 14 ans du match (Tom Voyce 1921 et 2006, Noel Murphy père et fils, Gareth Davies 1979 et
  2024).
- **Arbitre** repris du site (sans pays).

Étalonnage sur les 23 feuilles vérifiées (2024-2026) : compositions justes à quelques inversions près, points
exacts pour 51 côtés sur 58. Le site se trompe parfois sur le capitaine ou l'ordre des remplaçants.

## Reste à faire

- **29 matches sans points** : les points du site ne donnent pas le score de la base (comptes doublés, points
  manquants, Coupe du monde 2019 vide). À reprendre par les scripts Wikipédia ou à la main :
  1925-04-13 ENG, 1927-05-15 GER, 1936-05-17 GER, 1954-04-24 ITA, 1963-12-15 ROU, 1976-06-12 USA,
  1987-06-07 FIJ, 1990-06-24 AUS, 1991-07-13 USA, 1991-07-20 USA, 1995-04-08 ROU, 1996-02-17 IRL,
  1997-03-22 ITA, 1997-10-22 ROU, 2002-04-06 IRL, 2003-02-23 SCO, 2017-06-10 RSA, 2018-02-23 ITA,
  2019-02-10 ENG, 2019-08-30 ITA, 2019-09-21 ARG, 2019-10-02 USA, 2019-10-06 TGA, 2019-10-20 WAL,
  2020-10-31 IRL, 2021-11-14 GEO, 2023-09-08 NZL, 2024-02-02 IRL, 2024-07-13 ARG.
- **Numéros attribués** (722 joueurs) : ils apparaissent dans le tableau des compositions alors que le site
  n'en donne pas. On pourrait les masquer quand le poste est inconnu.
- **Erreurs du site repérées** : Eugène Billac (1920-1923) cité en 1977, Laurent Pardo (1980-1985) cité en
  1924 (le joueur ancien est donc créé à part, sous le même nom) ; « Roques » remplaçant en 1973 sans prénom
  (ignoré, ambigu) ; les deux Andy Moore gallois (« (S/H) ») sont confondus.
- Noms de 3 mots découpés par défaut (prénom = tout sauf le dernier mot) : la liste est affichée par le script ;
  ajouter une ligne au CSV d'exceptions si besoin.

## Plan initial

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
