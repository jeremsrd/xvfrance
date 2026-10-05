# Sources des données

État au 05/10/2026 : **839 matches officiels** en base, de 1906 à juillet 2026.

## Référence des matches officiels : Wikipédia

**[Liste des matchs de l'équipe de France de rugby à XV](https://fr.wikipedia.org/wiki/Liste_des_matchs_de_l%27%C3%A9quipe_de_France_de_rugby_%C3%A0_XV)**
(Wikipédia en français).

C'est la référence pour savoir **quels matches comptent** : la page liste les matches donnant lieu à une
sélection officielle (une cape) par la FFR. Chaque match y est numéroté (n° 1 le 01/01/1906 contre la
Nouvelle-Zélande), avec date, adversaire, score, lieu et compétition. La page cite ses propres sources :
ffr.fr, finalesrugby.fr, equipe-france.fr et rugbydatabase.co.nz. Elle liste aussi les matches à venir.

**La base doit contenir exactement les matches de cette liste.** Contrôle :

```bash
python3 scripts/wikipedia/liste_matchs.py           # bilan : matches en trop ou manquants
python3 scripts/wikipedia/liste_matchs.py --dates   # et les écarts de date ou de score
```

Le 05/10/2026 : 839 matches de chaque côté, aucun en trop ni en moins.

Les 29 écarts de date ou de score restants ont été arbitrés en faveur de la base, qui concorde avec
equipe-france.fr. La page a ses propres erreurs, par exemple :

- Angleterre–France 2003 : score inversé (25-17 au lieu de 17-25), d'où une victoire de trop dans son bilan ;
- Galles–France 1911 daté du 11/02 (28/02), Afrique du Sud–France 1961 du 18/01 (18/02),
  Roumanie–France 1984 du 23/11 (10/11), Galles–France 1996 du 30/09 (25/09) ;
- Coupe du Monde 2007 contre la Namibie et la Géorgie, et quart de finale 2011 contre l'Angleterre,
  datés de deux ou trois jours trop tôt.

Un écart signalé par le script n'est donc pas une erreur de la base : le vérifier (Wikipédia anglaise,
equipe-france.fr) avant de corriger.

## equipe-france.fr

[equipe-france.fr/rugby/masculin](https://www.equipe-france.fr/rugby/masculin) : une page par année,
une page par match.

Utilisé par :

- `xv:import-historical` : matches de 1906 à 1949 ;
- `xv:clean-feminine` et `xv:fix-venues` : nettoyage de l'import de `results.csv` ;
- `xv:fetch-equipe-france` : relevé de tous les matches dans `equipe_france_matches.csv` (compétition, journée
  ou phase, lieu), appliqué par `xv:apply-equipe-france` (phase, numéro de journée, stade quand ils manquent).

Les pages téléchargées sont gardées en cache dans `storage/app/equipe-france/` (hors git). Les pages de match
donnent aussi les compositions des deux équipes, l'arbitre et les marqueurs : c'est la source du prochain chantier
(`docs/specs/feuilles-historiques-equipe-france.md`).

Limites constatées : la liste du site n'est **pas** la liste officielle.

- Il compte des matches sans sélection : FIRA 1935, 1937, 1954, 1975, 1976 et 1978, et Uruguay 2024.
- Il a des doublons : Nouvelle-Zélande 1906 et Roumanie 1938.
- Il liste un match qui n'a pas eu lieu : Angleterre–France du 21/06/2025.
- Il oublie des matches officiels : Angleterre 1907 et Allemagne 1932, 1933 et 1934.
- Il donne un score faux pour le quart de finale de 1987 contre les Fidji (34-16 au lieu de 31-16).

## finalesrugby.fr : remplacements et sélections

[finalesrugby.fr/xv-de-france](https://finalesrugby.fr/xv-de-france/) : une page par match (803 en cache dans
`storage/app/finalesrugby/`, hors git), adresse `/{ville}-{domicile}-{extérieur}-{année}[-n]`.
Elle donne la composition française, les points français avec minute, et surtout :

- les **remplaçants restés sur le banc**, marqués « * » ;
- les **remplacements**, définitifs (D) ou temporaires (T), avec la minute quand elle est connue.

Une **sélection** ne compte que si le joueur est entré en jeu. equipe-france.fr liste les remplaçants sans dire qui
est entré : avant cette source, chaque passage sur le banc comptait comme une sélection (102 pour Ibañez au lieu
de 98). Le champ `played: false` d'une composition JSON marque un remplaçant resté sur le banc.

```bash
python3 scripts/finalesrugby/fetch.py          # met les pages en cache (≈ 25 min la première fois)
python3 scripts/finalesrugby/apply.py --dry    # bilan, puis sans --dry pour réécrire les JSON
```

Règles de `apply.py` : le match est reconnu par la date (à 3 jours près) et vérifié sur les titulaires ;
un remplaçant marqué « * », ou que le site ne cite pas alors qu'il liste un banc, reçoit `played: false` ;
les remplacements définitifs sont ajoutés s'il n'y a encore aucun remplacement français (27 matches non
appariés, ignorés). Les réservistes « * » d'avant 1969, absents des JSON, sont sans effet.

Contrôle le 06/10/2026 sur les 975 internationaux de la liste « This Is Rugby » dont la carrière finit avant
2015 : 825 ont exactement leur nombre de sélections (646 avant). Les 14 recordmen de la page
[Records en équipe de France](https://fr.wikipedia.org/wiki/Records_en_%C3%A9quipe_de_France_masculine_de_rugby_%C3%A0_XV)
sont justes, sauf Traille (+2), Szarzewski (+1) et Michalak (+1). Les écarts restants sont souvent des désaccords
entre sources (Berbizier : 56 selon Wikipédia et nous, 60 selon la liste) ou des erreurs du site (Brouzet en 1994).
Pas de remplacements adverses sur ce site.

## results.csv

`database/data/sources/results.csv` est un CSV de résultats internationaux de 1950 à 2026, lu par
`xv:import-csv`. Il mélange les matches masculins et féminins, et les matches avec et sans sélection.

Pièges connus :

- **Matches féminins.** Un match féminin joué le même jour contre le même adversaire faisait écarter le
  match masculin à l'import. `xv:clean-feminine` retire les matches féminins. Les 9 matches masculins perdus
  ont été recréés par des feuilles JSON.
- **Colonne `neutral`.** Elle est fausse, y compris pour les Coupes du Monde : le terrain neutre se déduit
  du pays du stade (`xv:compute-venue-flags`).
- **Nom de stade « Stadium ».** Le stade de Toulouse s'y appelle seulement « Stadium », ce qui rattachait
  tout stade au nom contenant « stadium » à Twickenham avant la correction de l'import.
- **Variantes de noms.** Les noms de stades y sont souvent des noms de sponsor ou des graphies différentes,
  reconnus grâce à `venue_names.csv`.

## Autres sources

- **Wikipédia anglaise, encadrés `{{rugbybox}}`** : points, marqueurs, arbitre, affluence, heure d'une feuille
  de match (`scripts/wikipedia/rugbybox.py`, `apply_wiki.py`). Méthode dans `docs/specs/feuilles-de-match-suite.md`.
- **OpenStreetMap (Nominatim)** : coordonnées des stades (`xv:geocode-venues`).

## Fichiers de `database/data/sources/`

| Fichier | Contenu | Utilisé par |
|---------|---------|-------------|
| `results.csv` | Résultats 1950 → 2026 (source brute, voir plus haut) | `xv:import-csv` |
| `equipe_france_matches.csv` | Relevé d'equipe-france.fr : compétition, journée ou phase, lieu | `xv:apply-equipe-france` |
| `excluded_matches.csv` | Matches sans sélection officielle, avec la raison | imports, `xv:remove-excluded-matches` |
| `venue_names.csv` | Anciens noms datés, variantes et noms erronés des stades | `xv:import-venue-names` |
| `venue_home_grounds.csv` | Stades de repli (Galles à Wembley en 1998) | `xv:compute-venue-flags` |
| `venue_coordinates.csv` | Coordonnées des stades (précision « stade » ou « ville ») | `xv:import-venue-coordinates` |

Les feuilles de match détaillées sont dans `database/data/matches/{année}/*.json`
(format : `docs/specs/import-match-data.md`). Une feuille peut aussi créer un match absent de la base.

## Liste des internationaux (ancien projet « This Is Rugby »)

`database/data/sources/internationaux_encyclo.csv` : liste chronologique des internationaux français saisie vers
2015 pour un ancien projet, **arrêtée au 31/12/2016** (n° 1 Henri Amand → n° 1098 Cyril Baille). Pour chaque
joueur : numéro d'international, NOM et prénom, date de naissance, postes, nombre de sélections, années, clubs.
Convertie du document Word d'origine par `scripts/encyclo/convertir.py`, qui la contrôle avec l'index alphabétique
du même projet.

- **Numérotation** : n° 1 à 3 pour Amand, Reichel et Dedet (1893-1900), puis les joueurs de 1906 dans l'ordre de la
  feuille de match. Elle fait foi pour `players.cap_number` (elle a remplacé les numéros de `xv:seed-1906`).
- **Manques** : 8 numéros absents (423, 498, 593, 596, 608, 788, 880, 961, sans doute une page de noms en T) et
  7 autres connus seulement par l'index (Triviaux, Clarac, Tourte, Tucoo-Chala, Trillo, Tournaire, Traille). Le
  n° 571 de Marc Pujolle est corrigé en 771.
- **Rapprochement** (`xv:import-internationaux`) : 1 051 joueurs sur 1 090, 1 036 dates de naissance. Les non
  rapprochés ont joué des matches sans composition en base (Allemagne 1932-1934, Armée britannique 1940) ou
  manquent sur equipe-france.fr (Serin, Vidal et Bianco en 1961, Halçarren 1964, Cardebat 1967…).
  `internationaux_correspondances.csv` tranche les cas que le nom ne résout pas.
- **Noms** : `--report` liste 108 écarts entre la base (noms d'equipe-france.fr) et la liste. Ce sont surtout des
  fautes du site (« Kail » Janik, « Yres » Brunet, prénoms composés abrégés), mais la liste a aussi ses coquilles
  (« Merqury », « Vahaamina »). **Appliqués le 06/10/2026** (83 joueurs renommés) : en base par
  `xv:apply-name-corrections`, dans les feuilles JSON et les exceptions du générateur (par slug equipe-france.fr)
  par `scripts/equipe_france/renommer.py`. Les deux André Abadie (n° 545 et 552) portent leur numéro
  (`cap_number`) dans les feuilles, ce qui les départage à l'import. Décisions dans `noms_ecarts.csv`, vérifiées le
  06/10/2026 sur Wikipédia ([Liste des sélectionnés en équipe de France de rugby à XV](https://fr.wikipedia.org/wiki/Liste_des_s%C3%A9lectionn%C3%A9s_en_%C3%A9quipe_de_France_de_rugby_%C3%A0_XV)
  et articles des joueurs) : le nom retenu est celui de Wikipédia, sauf Géo André (nom d'usage) et René de Malmann
  (graphie FFR). Cette page Wikipédia numérote autrement (date de première sélection, titulaire puis remplaçant,
  ordre alphabétique : Communeau n° 4) et précise que ses numéros ne sont pas ceux de la FFR.

## Portraits d'avant 1930 (ancien projet « This Is Rugby »)

116 portraits de joueurs ayant joué avant 1930 (41 Français, 29 Anglais, 12 Néo-Zélandais de 1906, 8 Écossais,
9 Sud-Africains de 1913, 13 Gallois, 4 Irlandais), tirés des archives de l'ancien projet (Agence Rol / Gallica,
cartes postales et photos de presse de l'époque). Prises avant 1930, ces photos sont **présumées dans le domaine
public** ; les photos récentes de ce même dossier (AFP, sites de presse) ne sont **pas** publiées.

- `database/data/sources/player_photos.csv` : fichier d'origine → joueur (pays, prénom, nom tels qu'en base), vérifié
  à la main (surnoms d'époque : Darkie Peters, Lump Cartwright, Cherry Pillman, Patsy Boylen, Dicky Lloyd).
- `scripts/photos/convertir.py` : WebP 400 px dans `public/images/joueurs/` (versionné, 2 Mo).
- `xv:import-player-photos` (lancée par `deploy.sh`) : renseigne `players.photo_path`, seulement pour un joueur
  ayant un match avant 1930, sans remplacer une photo posée autrement.
- Écartés faute de certitude : Bancroft (Billy ou Jack ?), Harper et Hunter de 1906 (prénoms différents sur le
  site), « AlexTrew », et les joueurs sans match en base (Louis Dedet, Giacardy, Halford, Wedge, Nanson, Mackrell…).
