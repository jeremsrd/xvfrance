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
donnent aussi l'arbitre et les marqueurs, ce qui est une piste pour les feuilles de match historiques.

Limites constatées : la liste du site n'est **pas** la liste officielle.

- Il compte des matches sans sélection : FIRA 1935, 1937, 1954, 1975, 1976 et 1978, et Uruguay 2024.
- Il a des doublons : Nouvelle-Zélande 1906 et Roumanie 1938.
- Il liste un match qui n'a pas eu lieu : Angleterre–France du 21/06/2025.
- Il oublie des matches officiels : Angleterre 1907 et Allemagne 1932, 1933 et 1934.
- Il donne un score faux pour le quart de finale de 1987 contre les Fidji (34-16 au lieu de 31-16).

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
