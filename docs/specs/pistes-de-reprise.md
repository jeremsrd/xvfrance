# État du site et pistes de reprise

État au 17/10/2026.

## Prochaine séance

Fait le 17/10/2026 : **fiches joueurs enrichies d'après equipe-france.fr** (étape a) :
`xv:fetch-equipe-france-players` puis `xv:import-equipe-france-players` (dans `deploy.sh`). 933 lieux de naissance,
158 dates de naissance de plus, 367 décès (date), 332 lieux de décès, 598 tailles, 583 poids. Voir `docs/sources.md`.

Fait le 17/10/2026 aussi : **dates de naissance et de décès tranchées avec Wikidata** (étape b) :
`scripts/wikipedia/naissances.py`, 84 naissances corrigées et 93 décès ajoutés dans `dates_corrections.csv`,
appliqués par `xv:apply-date-corrections` (dans `deploy.sh`). Voir `docs/sources.md`.

Reprendre par, dans l'ordre proposé :
1. **Fiches joueurs plus riches** (piste 4), suite :
   b. Reste : vérifier dans le fichier des décès de l'INSEE (matchID) les 5 décès en suspens (Gonnet, Desclaux,
      Perrier, Herrero, Gruarin) et, plus largement, les décès après 1970 que ni Wikidata ni equipe-france.fr ne
      donnent. Noms différents de Wikipédia : faits (13 renommages, voir `docs/sources.md`).
   c. Clubs (1h-2h) : décider d'abord colonne ou table (clubs successifs) ; source « This Is Rugby »
      (`internationaux_encyclo.csv`, 1 090 internationaux). equipe-france.fr ne donne le club que des 63 joueurs récents.
2. **Matches de novembre 2026** (piste 2) : 07/11 Fidji, 13/11 Afrique du Sud, 21/11 Argentine, finales 27-29/11
   (`/feuille-de-match <date> <code>` une fois joués).
3. **Équipes adverses et points d'Allemagne 1932-1934 et d'Armée britannique 1940** : presse d'époque sur Gallica
   (*L'Auto* du 18/04/1932 pour commencer), qui bloque les scripts (Cloudflare) : passer par Chrome. La validation
   refuse des points incomplets : il faut les marqueurs des deux équipes. 1940 : les 3 points français sont un
   « drop » de Thiers selon finalesrugby.fr, impossible (4 points en 1940), sans doute une pénalité.
4. **Reste des sélections** : 27 matches aux remplacements non appariés (`python3 scripts/finalesrugby/apply.py --dry`),
   Traille +2, Szarzewski et Michalak +1 face à la page des records ; 17 internationaux sans joueur en base
   (`php artisan xv:import-internationaux --report`) ; Arthapignet 1988 et Mignot 2016 absents de la base (Wikipédia).
5. **Numéros d'international après 2016** (piste 3) : trancher la source d'abord.
6. Plus tard : 2 matches sans points (1927 GER, 1963 ROU), 58 matches sans stade (le JSON ne fixe le stade qu'à la
   création), adjoints des sélectionneurs, photos d'équipe et stades du dossier « This Is Rugby » (Dropbox),
   qualité des données, **PHP 8.3 avant le 31/12/2026**, session design.
7. **Feuille de match** (demande du 17/10/2026) : afficher la photo du joueur, quand elle est connue
   (`players.photo_path`), à la place de son numéro de maillot dans la composition.

## Où on en est

| Domaine | État |
|---------|------|
| Matches | 839 matches officiels (référence : liste Wikipédia, voir `docs/sources.md`) |
| Feuilles de match | 839 avec la composition française ; 835 avec les deux équipes, capitaines, arbitre ; points pour 832 (7 sans points), minute connue pour 80 % des points français ; remplacements français et remplaçants restés sur le banc (finalesrugby.fr) |
| Joueurs | ~7 000, dont 1 251 Français ; 1 073 numéros d'international ; 1 213 dates de naissance ; d'après equipe-france.fr, 933 lieux de naissance, 367 décès, 598 tailles et 583 poids |
| Noms | 83 noms français corrigés d'après Wikipédia (`noms_ecarts.csv`) |
| Portraits | 119 joueurs d'avant 1930 |
| Sélectionneurs | 13 depuis 1963, avec leurs mandats (`selectionneurs.csv`) |

Chaînage des données au déploiement (`deploy.sh`) : `xv:apply-name-corrections` → `xv:import-match-data --changed`
→ `xv:compute-primary-positions` → `xv:import-internationaux` → `xv:import-equipe-france-players` → `xv:import-player-photos` → `xv:import-coaches`. Le détail des sources
et de leurs limites est dans `docs/sources.md`.

## Pistes

Classées de la plus utile à la plus accessoire. Chaque piste se fait par script, sans modèle de langage, sauf
mention contraire.

### 1. Points des 2 derniers matches sans points

27 des 29 matches sans points ont été repris les 05 et 06/10/2026. Sources, de la plus utile à la moins utile :
- **Wikipédia anglaise**, encadrés de match (`scripts/wikipedia/apply_wiki.py`, qui lit aussi les encadrés
  `home`/`away`, `{{#invoke:rugby box}}` et `homescore`/`awayscore`) : 2002-2024, Irlande 1996 ;
- **finalesrugby.fr** : marqueurs français avec minutes et remplacements, mais pas les marqueurs adverses
  (`/xv-de-france/{ville}-{domicile}-{extérieur}-{année}`, ex. `chicago-etats-unis-france-1976`,
  `bucarest-roumanie-france-1995`, `francfort-sur-le-main-allemagne-france-1927`) ;
- **Wikipédias française, allemande et italienne** : encadrés `{{match rugby}}` (Coupe du monde 1987, Tournoi 1925),
  page allemande « FIRA-Turnier 1936 », page italienne « Coppa Europa di rugby 1954 », récit du match
  dans « Australie-France en rugby à XV » (1990) ;
- equipe-france.fr pour le reste, après avoir divisé par deux les comptes doublés (USA 1991).

Barème : l'essai de pénalité vaut 7 points dès juin 2017 (tournée en Afrique du Sud, où les essais de règles
de World Rugby s'appliquaient), et non plus juillet (`EventType::points()`).

Restent 2 matches :

| Match | Ce qui manque |
|-------|---------------|
| 1927-05-15 GER 16-17 | Allemagne : aucun marqueur connu (FR : Ribère, Vellat ×2, Behoteguy, Destarac ×2 transf.) |
| 1963-12-15 ROU 6-6 | Roumanie : essai de Dragomirescu ; 3 points sans auteur (pénalité ou drop ?) |

Sources essayées sans succès : ESPN (pages, API et archives Wayback vides), rugbydatabase.co.nz (Cloudflare),
rugby.ro et rugbyromania.ro (la pénalité de Penciu qu'ils citent est celle de 1962). Pistes : Gallica (presse
de 1927), Le Monde ou L'Équipe de décembre 1963.

### 2. Matches à venir de novembre 2026

07/11 Fidji, 13/11 Afrique du Sud, 21/11 Argentine, puis les finales du Championnat des Nations (27-29/11).
**Méthode** : `/feuille-de-match <date> <code>` après chaque match, commit, `./deploy.sh`.

### 3. Numéros d'international après 2016

178 joueurs français n'ont pas de numéro : les sélectionnés depuis 2017 (la liste s'arrête au n° 1098) et
quelques anciens non rapprochés. **Attention** : la page Wikipédia numérote autrement que la FFR (date de première
sélection, titulaire puis remplaçant, ordre alphabétique). Il faut une source de numéros FFR (fiches ffr.fr ?) ou
prolonger la liste à la main avec la même règle. À trancher avant d'écrire quoi que ce soit.

### 4. Fiches joueurs plus riches

- **equipe-france.fr** (fait le 17/10/2026) : chaque fiche donne prénoms complets, date et lieu de naissance, taille, poids, club
  (vérifié sur Cyril Baille). 1 269 pages à mettre en cache comme les pages de match (`xv:fetch-equipe-france`),
  puis à lire par script. Rattachement sûr par le slug du site.
- **Wikipédia** : la première phrase des articles donne naissance et décès (« né le … à …, mort le … à … »).
  L'API (`prop=extracts`) le fournit pour 50 joueurs par requête. Utile pour les dates de décès des anciens.
- **Clubs** : la liste « This Is Rugby » donne les clubs de 1 090 internationaux (colonne `clubs` de
  `internationaux_encyclo.csv`). Il faudrait une colonne ou une table des clubs.

### 5. Sélectionneurs (fait le 06/10/2026)

13 sélectionneurs depuis 1963 dans `database/data/sources/selectionneurs.csv`, importés par `xv:import-coaches`.
Les mandats sont bornés pour reproduire exactement le nombre de matches du tableau « Entraîneurs » de l'article
Wikipédia « Équipe de France de rugby à XV » (Fouroux jusqu'à Roumanie–France du 24/05/1990, Dubroca dès la
tournée de juin 1990 en Australie). Victoires, nuls et défaites concordent aussi, sauf Desclaux : 35-4-21 chez
nous, 36-4-20 dans le tableau, alors que nos scores 1973-1980 sont ceux de la liste des matches de Wikipédia.
Reste possible : les adjoints (entraîneurs des avants, des arrières…), et les comités de sélection d'avant 1963.

### 6. Photos restantes de l'ancien projet

Dans le zip « This Is Rugby » (à retélécharger, lien Dropbox de l'utilisateur) :
- **photos d'équipe** de 1906 à 1920 (25 de la France, une vingtaine d'adversaires) et 2 photos de match
  (France–Écosse 1920) : à afficher sur la feuille de match, ce qui demande un champ (`matches.photo_path`) ;
- **12 stades anciens** (Colombes 1924, Parc des Princes, Arms Park, Twickenham…) : à rattacher à `venues` ;
- les portraits écartés faute de certitude (Bancroft, Harper, Hunter, Mackrell, Halford…).
Mêmes règles : photos d'avant 1930 seulement, pas de photos de presse récentes.

### 7. Qualité des données

- **Matches sans composition** (5) : Angleterre 1907, Allemagne 1932, 1933 et 1934, Armée britannique 1940
  (absents d'equipe-france.fr). Leurs joueurs français (René Finat, Antonin Barbazanges…) ne sont pas en base.
- **Internationaux introuvables sur le site** : Serin, Vidal et Bianco (1961), Halçarren (1964), Cardebat (1967)…
  (`xv:import-internationaux --report`).
- **Numéros de maillot attribués** par le générateur (722 joueurs sans numéro sur le site) : affichés comme s'ils
  étaient connus ; à masquer quand le poste est inconnu.
- **Erreurs du site repérées** : Eugène Billac cité en 1977, Laurent Pardo en 1924, « Roques » sans prénom en
  1973, les deux Andy Moore gallois confondus.
- **Joueurs adverses** : mêmes fautes que côté français (noms abrégés, homonymes) mais sans liste de référence ;
  à traiter seulement si une source fiable existe (ESPN, Wikipédia anglaise).
- **Stades** : erreurs listées dans la mémoire du projet (Carisbrook 2018, Lancaster Park 2013, SCG…) et 63
  matches sans stade.

### 8. Technique

- **PHP 8.2** n'a plus de correctifs de sécurité après le 31/12/2026 : passer le compte O2switch en 8.3+ (en
  vérifiant les ~40 autres sites), puis revenir à Laravel 13 (voir `CLAUDE.md`).
- **Session design** globale prévue en fin de projet (page `/records` à reprendre, fiches joueurs avec portrait).
