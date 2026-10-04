# Feuilles de match : état et suite

État au 04/10/2026.

## Où on en est

- **23 matches détaillés** en base : 1906, puis de novembre 2024 à juillet 2026. Leurs 22 fichiers JSON
  (dans `database/data/matches/{année}/`) passent `xv:validate-match-data` sans erreur ni avertissement.
- La validation contrôle maintenant :
  - que les points des événements égalent le score, selon le barème de l'époque
    (`EventType::points()`, essai de pénalité à 7 points depuis juillet 2017) ;
  - les remplacements : les deux joueurs sont sur la feuille, l'entrant est un remplaçant,
    et un avertissement signale un sortant qui n'est plus sur le terrain ;
  - les nouveaux joueurs dont le nom est proche de celui d'un joueur existant (risque de doublon).
- 13 feuilles de 2024-2026 ont été corrigées le 04/10/2026 d'après les encadrés de match de Wikipédia.
  L'import d'origine confondait pénalités et transformations, et il manquait des essais et des essais de pénalité.

## Publier une feuille de match

1. Écrire ou modifier le JSON (format : `docs/specs/import-match-data.md`).
2. `php artisan xv:validate-match-data <fichier>` : 0 erreur exigée, avertissements à lire.
3. En local : `php artisan xv:import-match-data <fichier> --changed`.
4. Commiter le JSON, puis lancer `./deploy.sh`. Le serveur réimporte uniquement les JSON nouveaux
   ou modifiés, repérés par leur empreinte stockée dans `matches.source_checksum`.

## Méthode la moins coûteuse

Les **points** (essais, transformations, pénalités, drops, avec joueurs et minutes) se prennent dans le wikitext
brut de Wikipédia, avec un script et sans modèle de langage :

```bash
# Voir les encadrés de match de la France d'une page
python3 scripts/wikipedia/rugbybox.py "2025 Six Nations Championship" "15 March"

# Comparer une feuille à Wikipédia (--dry), puis corriger
python3 scripts/wikipedia/apply_wiki.py database/data/matches/2025/2025-03-15-SCO.json \
    "2025 Six Nations Championship" "15 March" --dry
```

Pages utiles : « 20XX Six Nations Championship », « 20XX end-of-year rugby union internationals »,
« 20XX mid-year rugby union internationals », « 20XX France rugby union tour of … ».

À éviter :
- **WebFetch** sur les longues pages : il les tronque et peut inventer des marqueurs
  (c'est arrivé pour Irlande–France 2025).
- **Un agent par match** : environ 90 000 tokens chacun.
- **Un petit modèle (Haiku)** pour les corrections : 120 000 tokens pour 5 feuilles sur 13,
  avec des modifications hors consigne.

Les **compositions et remplacements** ne figurent pas dans les encadrés Wikipédia. Il faut une recherche web
(sites FFR, RugbyPass, allrugby, fédérations), qui reste le poste le plus coûteux.

## Prochaines étapes proposées

1. **Champs de match dans le JSON** : arbitre, affluence, heure du coup d'envoi, météo. Les colonnes existent
   déjà dans `matches`, mais le format JSON ne les prévoit pas. Données déjà connues : NZL–FRA 04/07/2026,
   arbitre Luke Pearce, 29 152 spectateurs ; AUS–FRA 11/07/2026, arbitre Karl Dickson.
2. **Commande de projet `/feuille-de-match <date>`** : méthode figée, avec les points par les scripts
   Wikipédia et les compositions et remplacements par une recherche ciblée, puis validation.
3. **Matches historiques (~800)** : trouver une source structurée à récupérer automatiquement
   (ESPN Statsguru, allrugby…), car la méthode au cas par cas ne tient pas à cette échelle.
4. Points connus mais non corrigés :
   - `PlayerResolverService` cherche le nom de famille avec accents. « Jegou » créerait donc un doublon
     de « Jégou », ce que la validation signale désormais par un avertissement.
   - Pas de classement aux points dans les records individuels, en attendant d'avoir plus de feuilles complètes.
