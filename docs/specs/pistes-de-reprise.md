# État du site et pistes de reprise

État au 06/10/2026, après le chantier des feuilles historiques et la reprise des données de l'ancien projet
« This Is Rugby ». Tout est commité et déployé (dernier commit déployé : 22fc565).

## Où on en est

| Domaine | État |
|---------|------|
| Matches | 839 matches officiels (référence : liste Wikipédia, voir `docs/sources.md`) |
| Feuilles de match | 834 détaillées : compositions des deux équipes, capitaines, arbitre ; points pour 805 (29 sans points) |
| Joueurs | ~7 000, dont 1 229 Français ; 1 051 numéros d'international, 1 036 dates de naissance |
| Noms | 83 noms français corrigés d'après Wikipédia (`noms_ecarts.csv`) |
| Portraits | 116 joueurs d'avant 1930 |
| Sélectionneurs | table `coaches` vide |

Chaînage des données au déploiement (`deploy.sh`) : `xv:apply-name-corrections` → `xv:import-match-data --changed`
→ `xv:compute-primary-positions` → `xv:import-internationaux` → `xv:import-player-photos`. Le détail des sources
et de leurs limites est dans `docs/sources.md`.

## Pistes

Classées de la plus utile à la plus accessoire. Chaque piste se fait par script, sans modèle de langage, sauf
mention contraire.

### 1. Points des 29 matches sans points

Les compositions sont là, mais pas les points : le site equipe-france.fr donnait un total différent du score
(comptes doublés, points manquants, Coupe du monde 2019 vide). Liste dans
`docs/specs/feuilles-historiques-equipe-france.md`, « Reste à faire ».
**Méthode** : wikitext Wikipédia (`scripts/wikipedia/rugbybox.py`, voir `feuilles-de-match-suite.md`), surtout
fiable pour 1987 et après ; pour 1925-1976, articles des matches ou ESPN. Le contrôle points = score de
`xv:validate-match-data` dit quand c'est juste.

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

### 5. Sélectionneurs

La table `coaches` / `coach_tenures` est vide alors que la page `/selectionneurs` existe. Une trentaine de
sélectionneurs depuis 1964 : saisie dans un CSV source (Wikipédia « Équipe de France de rugby à XV », section
sélectionneurs), puis une commande d'import. Permet le bilan par sélectionneur.

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
