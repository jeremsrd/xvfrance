# État du site et pistes de reprise

État au 06/10/2026, après le chantier des feuilles historiques et la reprise des données de l'ancien projet
« This Is Rugby ». Tout est commité et déployé (dernier commit déployé : 22fc565).

## Où on en est

| Domaine | État |
|---------|------|
| Matches | 839 matches officiels (référence : liste Wikipédia, voir `docs/sources.md`) |
| Feuilles de match | 834 détaillées : compositions des deux équipes, capitaines, arbitre ; points pour 832 (2 sans points), minute connue pour 80 % des points français ; remplacements français et remplaçants restés sur le banc (finalesrugby.fr) |
| Joueurs | ~7 000, dont 1 229 Français ; 1 051 numéros d'international, 1 036 dates de naissance |
| Noms | 83 noms français corrigés d'après Wikipédia (`noms_ecarts.csv`) |
| Portraits | 116 joueurs d'avant 1930 |
| Sélectionneurs | 13 depuis 1963, avec leurs mandats (`selectionneurs.csv`) |

Chaînage des données au déploiement (`deploy.sh`) : `xv:apply-name-corrections` → `xv:import-match-data --changed`
→ `xv:compute-primary-positions` → `xv:import-internationaux` → `xv:import-player-photos` → `xv:import-coaches`. Le détail des sources
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

Aucun joueur n'a de ville de naissance, de date de décès, de taille ni de poids.
- **equipe-france.fr** : chaque fiche donne prénoms complets, date et lieu de naissance, taille, poids, club
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
