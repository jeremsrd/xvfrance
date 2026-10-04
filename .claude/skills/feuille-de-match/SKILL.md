---
name: feuille-de-match
description: Saisir ou corriger la feuille de match JSON d'un match du XV de France (compositions, points, cartons, remplacements, arbitre, affluence), la valider et l'importer en local. Usage : /feuille-de-match <date AAAA-MM-JJ> [code adversaire].
argument-hint: <AAAA-MM-JJ> [code adversaire]
---

# Feuille de match : $ARGUMENTS

Méthode figée, la moins coûteuse en tokens. Format JSON : `docs/specs/import-match-data.md`.
Contexte et pièges : `docs/specs/feuilles-de-match-suite.md`.

Règles :
- **Pas d'agent par match** (environ 90 000 tokens chacun) ni de petit modèle : tout se fait dans la session.
- **Pas de WebFetch sur une longue page Wikipédia** : il tronque et peut inventer des marqueurs.
  Les points se prennent **uniquement** par les scripts `scripts/wikipedia/`.
- **Ne rien inventer** : un champ sans source sûre est omis (`minute: null`, `position: null`,
  pas de `kickoff_time`, pas de `weather`…), et on le signale dans le compte rendu.
- Réponses et compte rendu en français.

## 1. Le match en base

```bash
sqlite3 database/database.sqlite "select m.id, m.match_date, c.code, m.france_score, m.opponent_score, m.source_checksum is not null
  from matches m join countries c on c.id = m.opponent_id where date(m.match_date) = '<date>'"
ls database/data/matches/<année>/ | grep <date>
```

- **Match absent de la base** : il sera créé par l'import du JSON. Ajouter alors au JSON `venue`
  (nom exact d'un stade en base : `sqlite3 database/database.sqlite "select name, city from venues where name like '%<nom>%' or city like '%<ville>%'"`),
  ou un nouveau stade avec `venue_city` et `venue_country_code` ; `competition` (short_name de la table
  `competitions`, ex. « Championnat des Nations », « Tests d'automne ») ; `stage` (`journee`, `test`,
  `finale`…, comme les autres matches de la compétition). Le score vient de l'encadré Wikipédia.
- **Feuille déjà existante** : la relire et ne compléter ou corriger que ce qui manque ou est faux.
- Fichier : `database/data/matches/<année>/<date>-<CODE>.json`.

## 2. Les points, par Wikipédia (sans modèle de langage)

Trouver l'encadré de match de la France. Pages à essayer, dans l'ordre :
« <année> Six Nations Championship »,
« <année> Nations Championship Southern Hemisphere Series » (juillet) et
« <année> Nations Championship Northern Hemisphere Series » (novembre) : la page
« <année> Nations Championship » ne contient que les finales,
« <année> end-of-year rugby union internationals », « <année> mid-year rugby union internationals »,
« <année> France rugby union tour of … », « <année> Rugby World Cup Pool X ».

```bash
python3 scripts/wikipedia/rugbybox.py "<page>" "<jour> <Mois en anglais>"
```

Sans date, le script liste tous les matches de la France de la page. Il affiche le score, les marqueurs
(try/con/pen/drop avec minutes), les cartons, et quand l'encadré les donne : heure du coup d'envoi
(`time`, déjà en heure locale), stade, affluence, arbitre et son pays. Ces champs de match se prennent
donc ici. Si le score de l'encadré diffère du score en base, s'arrêter et le signaler.

## 3. Compositions, remplacements, arbitre : recherche web ciblée

C'est le poste le plus coûteux : viser 2 sources concordantes, pas plus.
- **WebSearch** d'abord (« France v <adversaire> <date> line-ups replacements referee »).
- **WebFetch** seulement sur des pages courtes et ciblées : compte rendu ou fiche de match
  (FFR, RugbyPass, ESPN, allrugby, fédération adverse, World Rugby). Demander dans le prompt une
  sortie compacte : numéro, prénom, nom, capitaine, puis remplacements « sortant → entrant, minute ».
- Récupérer : 23 + 23 joueurs, capitaines, remplacements avec minutes, et les cartons ou champs
  de match (arbitre, affluence, heure) que l'encadré Wikipédia ne donne pas.
- Orthographe des noms : reprendre celle des joueurs **déjà en base** pour éviter les doublons :

```bash
sqlite3 database/database.sqlite "select first_name, last_name from players p join countries c on c.id = p.country_id
  where c.code = '<CODE>' and last_name like '%<nom>%'"
```

## 4. Écrire le JSON

Ordre des clés (comme les feuilles existantes, une ligne par joueur) :
`match_date`, `opponent_code`, `france_score`, `opponent_score`, puis (nouveau match) `venue`,
`venue_city`, `venue_country_code`, `competition`, `stage`, puis les champs de match connus
(`referee`, `referee_country_code`, `attendance`, `kickoff_time`, `weather`), puis `lineups`
(`france`, `adversaire`), `events`, `substitutions`.

- Postes : déduits du numéro (1 pilier gauche … 15 arrière) ; remplaçants : poste réel du joueur.
- `events` : mettre au moins les cartons ; les points seront écrits à l'étape suivante.
- Remplacement temporaire (protocole commotion, saignement) : `"is_tactical": false`.

## 5. Points depuis Wikipédia, puis validation

```bash
python3 scripts/wikipedia/apply_wiki.py <fichier.json> "<page>" "<jour> <Mois>" --dry
python3 scripts/wikipedia/apply_wiki.py <fichier.json> "<page>" "<jour> <Mois>"
php artisan xv:validate-match-data <fichier.json>
```

Exiger **0 erreur**. Lire chaque avertissement : un « nom proche d'un joueur existant » est presque
toujours une faute d'orthographe à corriger dans le JSON (pas un nouveau joueur).

## 6. Import local et contrôle

```bash
php artisan xv:import-match-data <fichier.json> --changed
curl -sk https://xvfrance.test/matches/<slug> | grep -c "<nom d'un marqueur>"
```

## 7. Compte rendu, puis commit et déploiement sur accord

Résumer : match ou stade créés, sources utilisées, ce qui manque (minutes, affluence…), joueurs créés, avertissements.
Proposer ensuite de commiter le JSON (`data: feuille de match <date> <adversaire>`) et de lancer
`./deploy.sh` ; **attendre l'accord** de l'utilisateur avant de le faire.
