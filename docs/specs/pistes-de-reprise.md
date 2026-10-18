# État du site et pistes de reprise

État au 17/10/2026.

## Prochaine séance

Fait le 17/10/2026 (tout est commité, déployé et poussé ; dernier commit déployé : 83777e0) :
- **Fiches joueurs d'après equipe-france.fr** : lieux de naissance, décès, tailles, poids
  (`xv:fetch-equipe-france-players`, `xv:import-equipe-france-players`).
- **Dates de naissance et de décès tranchées avec Wikidata et le fichier des décès de l'INSEE** :
  `scripts/wikipedia/naissances.py`, `dates_corrections.csv` (85 naissances, 98 décès, 3 lieux de décès),
  `xv:apply-date-corrections`. Erreurs connues d'equipe-france.fr écartées au relevé (`KNOWN_ERRORS`).
- **13 noms alignés sur Wikipédia** (`noms_ecarts.csv`, Porra sans numéro retrouvé par son nom).
- **Clubs** : tables `clubs` / `player_clubs`, `xv:import-clubs`, pages `/clubs` et `/clubs/{slug}`, clubs sur la
  fiche joueur (nom de l'époque). Un club renommé reste le même club, une fusion qui crée un club en fait un nouveau
  (UBB), sauf l'US perpignanaise et l'AS perpignanaise, rattachées à l'USA Perpignan (les Arlequins perpignanais
  restent à part). Choix de l'utilisateur, voir `docs/sources.md`.
- **Clubs d'après Wikipédia** (`scripts/wikipedia/clubs.py`, `wikipedia_clubs.csv`) pour les internationaux absents
  de la liste « This Is Rugby » : 7 internationaux sans club au lieu de 97 ; filtre par club sur `/joueurs`.
- **Accents des noms** : 107 internationaux corrigés d'après la liste Wikipédia (`noms_ecarts.csv`, `renommer.py`) ;
  règle : un accent connu est toujours mis (Benoît Dauga, Raphaël Ibañez gardés malgré Wikipédia), particule en
  minuscule (de Villiers). « Louis Péllissier » de Wikipédia écarté (graphie douteuse). Adversaires non vérifiés.
- **Recordmen des sélections** sur `/records` (`RecordsService::capsRecordHistory()`) : durée des règnes à l'échelle,
  puis frise (qui prend ou égale le record, quand, contre qui, jusqu'à quand, total en carrière).
- **Naissances des sélectionnés d'après 2020** : 14 dates ajoutées ou corrigées (`dates_corrections.csv`) ; les
  6 autres écarts avec Wikidata sont des erreurs de Wikidata (mois et jour inversés), base gardée.
- **Portraits sur la feuille de match** : la photo du joueur s'affiche à côté de son nom dans la liste (le numéro reste, le terrain garde les numéros).
- **Records découpés en deux pages** : `/records` (équipe : phares, écarts, scores, séries, époques, adversaires) et
  `/records/joueurs` (classements sélections hors banc, essais, points au barème de l'époque, capitanats ; recordmen
  successifs en onglets Sélections / Essais / Points, `#recordmen-essais` ouvre l'onglet). Dernier commit déployé : 83777e0.
- **Règnes de recordman sur la fiche joueur** (18/10) : « Recordman des essais de 1989 à 2025 », lien vers l'onglet de la
  frise (`RecordsService::playerRecordReigns()`, historiques en cache tant que les feuilles ne changent pas).
- **Accents des joueurs adverses** (18/10) : 155 noms (`noms_adversaires.csv`), d'après Wikidata (123) et l'orthographe
  espagnole (32) ; `accents_adversaires.py`, `renommer_adversaires.py`, `xv:apply-opponent-name-corrections`.
  Roumains sans source laissés en l'état (voir `docs/sources.md`).

Reprendre par, dans l'ordre proposé :
1. **Matches de novembre 2026** : 07/11 Fidji, 13/11 Afrique du Sud, 21/11 Argentine, finales 27-29/11
   (`/feuille-de-match <date> <code>` une fois joués, puis commit et `./deploy.sh`).
2. **Équipes adverses et points d'Allemagne 1932-1934 et d'Armée britannique 1940** : presse d'époque sur Gallica
   (*L'Auto* du 18/04/1932 pour commencer), qui bloque les scripts (Cloudflare) : passer par Chrome. La validation
   refuse des points incomplets : il faut les marqueurs des deux équipes. 1940 : les 3 points français sont un
   « drop » de Thiers selon finalesrugby.fr, impossible (4 points en 1940), sans doute une pénalité.
3. **Reste des sélections** : 27 matches aux remplacements non appariés (`python3 scripts/finalesrugby/apply.py --dry`),
   Traille +2, Szarzewski et Michalak +1 face à la page des records ; 17 internationaux sans joueur en base
   (`php artisan xv:import-internationaux --report`) ; Arthapignet 1988 et Mignot 2016 absents de la base (Wikipédia).
4. **Numéros d'international après 2016** (piste 3) : trancher la source d'abord.
5. Plus tard :
   - décès après 1970 que ni Wikidata ni equipe-france.fr ne donnent, à chercher dans le fichier des décès de
     l'INSEE (API matchID `deces.matchid.io/deces/api/v1/search?lastName=…&firstName=…&birthDate=JJ/MM/AAAA` ;
     quota anonyme de quelques dizaines de requêtes, puis environ une heure d'attente) ;
   - 40 naissances où equipe-france.fr diffère de la base (`xv:import-equipe-france-players --report`), base gardée ;
   - 2 matches sans points (1927 GER, 1963 ROU), 58 matches sans stade, adjoints des sélectionneurs, photos
     d'équipe et stades du dossier « This Is Rugby » (Dropbox), qualité des données ;
   - **PHP 8.3 avant le 31/12/2026** ; SEO (reporté par l'utilisateur) ; session design globale à la fin.

## Où on en est

| Domaine | État |
|---------|------|
| Matches | 839 matches officiels (référence : liste Wikipédia, voir `docs/sources.md`) |
| Feuilles de match | 839 avec la composition française ; 835 avec les deux équipes, capitaines, arbitre ; points pour 832 (7 sans points), minute connue pour 80 % des points français ; remplacements français et remplaçants restés sur le banc (finalesrugby.fr) |
| Joueurs | ~7 000, dont 1 251 Français ; 1 073 numéros d'international ; 1 214 dates de naissance, 938 lieux de naissance, 460 décès (equipe-france.fr, Wikidata, INSEE) |
| Clubs | 117 clubs, 1 121 internationaux reliés à au moins un club (1 368 passages) ; 97 sans club |
| Noms | 96 noms français corrigés d'après Wikipédia (`noms_ecarts.csv`) |
| Portraits | 119 joueurs d'avant 1930 |
| Sélectionneurs | 13 depuis 1963, avec leurs mandats (`selectionneurs.csv`) |

Chaînage des données au déploiement (`deploy.sh`) : `xv:import-venue-names` → `xv:compute-venue-flags` →
`xv:apply-name-corrections` → `xv:import-match-data --changed` → `xv:compute-primary-positions` →
`xv:import-internationaux` → `xv:apply-date-corrections` → `xv:import-equipe-france-players` → `xv:import-clubs` →
`xv:import-player-photos` → `xv:import-coaches` → `xv:remove-excluded-matches` → `xv:apply-equipe-france` →
`xv:import-venue-coordinates`. Le détail des sources et de leurs limites est dans `docs/sources.md`.

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
