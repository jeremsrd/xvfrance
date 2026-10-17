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

- `xv:fetch-equipe-france-players` : fiches des internationaux dans `equipe_france_players.csv` (prénoms, date et
  lieu de naissance, date et lieu de décès, taille, poids, club), appliquées par `xv:import-equipe-france-players`.

Les pages téléchargées sont gardées en cache dans `storage/app/equipe-france/` (hors git). Les pages de match
donnent aussi les compositions des deux équipes, l'arbitre et les marqueurs : c'est la source du prochain chantier
(`docs/specs/feuilles-historiques-equipe-france.md`).

Limites constatées : la liste du site n'est **pas** la liste officielle.

- Il compte des matches sans sélection : FIRA 1935, 1937, 1954, 1975, 1976 et 1978, et Uruguay 2024.
- Il a des doublons : Nouvelle-Zélande 1906 et Roumanie 1938.
- Il liste un match qui n'a pas eu lieu : Angleterre–France du 21/06/2025.
- Il oublie des matches officiels : Angleterre 1907 et Allemagne 1932, 1933 et 1934.
- Il donne un score faux pour le quart de finale de 1987 contre les Fidji (34-16 au lieu de 31-16).

### Fiches des joueurs (relevé du 17/10/2026)

- **Rattachement** : seuls les joueurs français ont un lien dans les compositions des pages de match. Chaque fiche
  est rattachée par sa première apparition (date du match, numéro de maillot, nom de famille proche pour les noms
  corrigés d'après Wikipédia). 1 230 fiches sur 1 269 ; les 39 autres sont des remplaçants restés sur le banc en
  1975-1978 (absents de la base) ou des matches sans sélection officielle (FIRA 1935 et 1937, Uruguay 2024, 21/06/2025).
- **Contenu** : 1 150 dates de naissance, 943 lieux, 376 décès, 602 tailles, 587 poids, 63 clubs (joueurs récents).
  Lieu en France : « Pau, Pyrénées-Atlantiques » (Paris seul) ; à l'étranger : « Dakar Sénégal ». Un pays absent de
  la table `countries` reste dans la ville (« Dakar (Sénégal) ») ; Royaume-Uni = Angleterre (Londres, Croydon,
  Warrington).
- **Dates bouche-trou** écartées au relevé : naissance au 13/12/1901 (33 joueurs d'avant 1914), décès au 01/01/2000 (4).
- **Homonymes** : le site mélange parfois deux joueurs du même nom. L'import écarte toute la fiche quand l'âge au
  premier match sort de 16-42 ans (Laurent Pardo, Jean-Louis Martin, Thierry Picard) ou quand la naissance s'écarte
  de 2 ans ou plus de la base avec un autre jour et un autre mois (6 fiches depuis les corrections de dates du
  17/10/2026, dont André Abadie ; Sagot, Legrain, Tales, Lamaison et Fargues n'étaient pas des homonymes).
- **Dates de naissance en désaccord** : 111 avec la liste des internationaux, tranchées avec Wikidata le 17/10/2026
  (section suivante). Restent 40 (`--report`) où la base est gardée : Wikidata la confirme, ou rien ne tranche
  (Duval, Duffour, Milliand, Failliot, Theuriet, Besson).
- **Erreurs connues** du site, laissées vides au relevé (`KNOWN_ERRORS` de `xv:fetch-equipe-france-players`) :
  décès de Yoann Huget (vivant), naissance, taille et poids de Christophe Lamaison.

## Wikidata et fichier des décès de l'INSEE : dates de naissance et de décès

`scripts/wikipedia/naissances.py` part de la page [Liste des sélectionnés en équipe de France de rugby à XV](https://fr.wikipedia.org/wiki/Liste_des_s%C3%A9lectionn%C3%A9s_en_%C3%A9quipe_de_France_de_rugby_%C3%A0_XV)
(1 120 internationaux, jusqu'à 2020 environ), suit le lien de chaque joueur jusqu'à son élément Wikidata et compare
naissance et décès à la base et à equipe-france.fr (rapprochement par date de première sélection et nom ;
1 114 joueurs, 1 005 avec une date de naissance sur Wikidata). Réponses gardées dans `storage/app/wikipedia`.

Bilan du 17/10/2026 : 100 naissances en désaccord avec la base, 96 décès absents de la base. Règles de décision,
consignées dans `dates_corrections.csv` (colonne `source`) :
- equipe-france.fr et Wikidata d'accord contre la base → Wikidata (68 cas : la liste « This Is Rugby » inverse
  souvent jour et mois ou se trompe d'année) ;
- base et equipe-france.fr d'accord contre Wikidata → base (15 cas) ;
- sinon, l'article Wikipédia (fr, et en s'il existe) et le **fichier des décès de l'INSEE**
  ([matchID](https://deces.matchid.io), décès depuis 1970, naissance d'après l'état civil) tranchent : Wikipédia a
  eu raison à chaque vérification (Constant, Llari, Chaud). Wikidata se trompe pour Georges Jérome et Hugues Miorin
  (l'article fr donne la date de la base).
- décès : date complète de Wikidata quand la base n'en a pas (93). Les 5 cas douteux sont tranchés par l'INSEE
  (avec le lieu de décès) : Gonnet 26/09/1985, Desclaux 26/10/1981 (2023 sur equipe-france.fr), Perrier 22/07/2015,
  Herrero 23/10/2025, Gruarin 29/01/2025. L'API matchID limite les requêtes anonymes (quelques dizaines, puis une
  attente d'environ une heure ; compte gratuit avec e-mail au-delà) : une requête par joueur, filtrée par
  `birthDate=JJ/MM/AAAA`, suffit.

`xv:apply-date-corrections` applique le fichier (ne change une date que si la base porte encore l'ancienne valeur),
avant `xv:import-equipe-france-players` dans `deploy.sh`.

Le rapprochement a aussi révélé des **noms différents de Wikipédia**, tranchés le 17/10/2026 selon la règle du
06/10 (nom de Wikipédia, nom d'usage compris) : 13 renommages ajoutés à `noms_ecarts.csv` (Lucien Thévenot,
André Francquenelle, Pierre Mounicq, Bernard Lavigne, Fernand Taillantou, Maurice Porra, André Rochon, Félix Bergèse,
René Séguier, Christian Cœurveillé, Jean-Michel Gonzalez, Thibaut Privat, Mickaël Forest). Gardés : Jacques Dufourcq,
Léon Larribau et Hector Fargues (l'article confirme la base, même si la liste Wikipédia dit autrement), Hourdebaigt,
Iguiniz et Poirier (pas d'article), Augras-Fabre (graphie de la liste et du site), de Malmann et Tilh (tranchés le
06/10). Hector Fargues est né le 9 juillet 1894 (article et equipe-france.fr), pas en 1892 : ce n'était pas un
homonyme. Maurice Porra, « 255 bis » de la liste (sa ligne était collée aux clubs de Charles Bigot, corrigé), n'a pas
de numéro : `xv:apply-name-corrections` le retrouve par son nom avant correction.
Non rapprochés : Beaurin-Gressier, Géo André, Augras-Fabre et Henri Poirier (Henri-Auguste Poirrier sur Wikipédia),
noms trop différents, et 2 internationaux absents de la base : Pierre Arthapignet (1988) et Xavier Mignot (2016).

**Accents** (règle de l'utilisateur, 17/10/2026 : un nom ou un prénom qui porte un accent doit l'avoir) : 107 noms
d'internationaux accentués d'après la liste Wikipédia (Ribère, Domenech, Castaignède, Cécillon, Éric Champ, Iraçabal…),
lignes « Wikipédia : accents et particule » de `noms_ecarts.csv`, reportées dans les feuilles JSON par
`scripts/equipe_france/renommer.py`. Un accent présent dans la base et absent de Wikipédia est gardé (Benoît Dauga,
Raphaël Ibañez, Kévin Gourdon, Étienne Piquiral, Émile Doussau, François Sangalli) ; particules en minuscule
(de Villiers, de Jouvencel, de Laborderie, de Bésombes). « Louis Péllissier » (Wikipédia) écarté, graphie douteuse.
Les noms des joueurs adverses ne sont pas vérifiés.

Les sélectionnés d'après février 2020 n'ont plus le modèle `{{date}}` dans la liste Wikipédia : `naissances.py` lit
aussi les dates en texte. 14 naissances ajoutées ou corrigées (Haouas, Forletta, Roumat — 27/06/1997 selon les
articles français et anglais, 24/06 sur Wikidata —, Beria…) ; les autres écarts avec Wikidata sont des erreurs de
Wikidata (jour et mois inversés), base gardée.

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

Le même cache complète la **minute des points français** : `python3 scripts/finalesrugby/points.py [--dry]`. Une minute
n'est remplie que si le site donne, pour le même joueur et le même type, autant de points que la feuille ; les points
ne sont jamais ajoutés ni retirés. Le 06/10/2026 : 1 323 minutes ajoutées sur 281 feuilles (2 337 points sans minute
avant, 1014 après, surtout avant 1980, où le site écrit « 2e mi-temps » ou rien). Les blocs « cartons » du site sont
toujours vides.

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
| `clubs.csv` | Clubs des internationaux : slug, nom usuel (graphie de Wikipédia), ville, pays | `xv:import-clubs` |
| `club_names.csv` | Nom de club des sources → club et nom à l'époque (vide : écarté) | `xv:import-clubs` |
| `wikipedia_clubs.csv` | Clubs de l'encadré Wikipédia, par première sélection (date, maillot) — généré par `scripts/wikipedia/clubs.py` | `xv:import-clubs` |
| `dates_corrections.csv` | Dates de naissance et de décès vérifiées (Wikidata, INSEE), avec leur source | `xv:apply-date-corrections` |

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

## Clubs des internationaux

Importés par `xv:import-clubs` (tables `clubs` et `player_clubs`, reconstruites à chaque import) :
- **Sources** : la colonne `clubs` de la liste « This Is Rugby » (clubs de la carrière internationale, dans
  l'ordre, sans dates, jusqu'à 2016), par numéro d'international ; à défaut, le champ « club » (seniors) de
  l'encadré de l'article Wikipédia du joueur (`wikipedia_clubs.csv`, relevé par `python3 scripts/wikipedia/clubs.py`
  via la liste des sélectionnés : titre de l'article du club, intertitres « Rugby à XV / à XIII » écartés) ;
  à défaut, le club actuel donné par equipe-france.fr. Le 17/10/2026 : 143 clubs, 1 211 internationaux,
  1 652 passages (1 065 par la liste, 146 par Wikipédia) ; 7 sans club (3 joueurs de 1971 sans prénom, 4 articles
  sans encadré). Les clubs de rugby à XIII sont écartés (vides dans `club_names.csv`) ; un club étranger est gardé
  (Brumbies, Munster…). Stado Tarbes Pyrénées rugby continue le Stadoceste tarbais (redirection sur Wikipédia).
- **Un club au sens de Wikipédia** (choix du 17/10/2026) : un club renommé reste le même (Racing Club de France →
  Racing Métro 92 → Racing 92, AS Montferrand → ASM Clermont Auvergne) ; une fusion qui crée un club en fait un
  nouveau (Union Bordeaux Bègles en 2006, après le CA Bègles et le Stade bordelais ; le CASG reste distinct du Stade
  français). Exception voulue : l'US perpignanaise et l'AS perpignanaise sont rattachées à l'USA Perpignan (même
  club pour l'utilisateur) ; les Arlequins perpignanais restent à part.
- **Nom affiché** : celui de l'époque sur la fiche du joueur (`player_clubs.name`, « Racing Club de France » pour
  Gaston Lane), le nom actuel sur la page du club, avec les anciens noms en sous-titre.
- `club_names.csv` corrige au passage les coquilles de la liste (« Stade Toyulousain », « Bitteroise »…) ; la
  « Fédération Française de Rugby » (contrat fédéral de Vakatawa, rugby à 7) est écartée.

## Portraits d'avant 1930 (ancien projet « This Is Rugby »)

119 portraits de joueurs ayant joué avant 1930 (42 Français, 31 Anglais, 12 Néo-Zélandais de 1906, 8 Écossais,
9 Sud-Africains de 1913, 13 Gallois, 4 Irlandais), tirés des archives de l'ancien projet (Agence Rol / Gallica,
cartes postales et photos de presse de l'époque). Prises avant 1930, ces photos sont **présumées dans le domaine
public** ; les photos récentes de ce même dossier (AFP, sites de presse) ne sont **pas** publiées.

- `database/data/sources/player_photos.csv` : fichier d'origine → joueur (pays, prénom, nom tels qu'en base), vérifié
  à la main (surnoms d'époque : Darkie Peters, Lump Cartwright, Cherry Pillman, Patsy Boylen, Dicky Lloyd).
- `scripts/photos/convertir.py` : WebP 400 px dans `public/images/joueurs/` (versionné, 2 Mo).
- `xv:import-player-photos` (lancée par `deploy.sh`) : renseigne `players.photo_path`, seulement pour un joueur
  ayant un match avant 1930, sans remplacer une photo posée autrement.
- Écartés faute de certitude : Bancroft (Billy ou Jack ?), Harper et Hunter de 1906 (prénoms différents sur le
  site), « AlexTrew », et les joueurs sans match en base (Louis Dedet, Halford, Mackrell…).
