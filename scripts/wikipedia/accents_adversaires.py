#!/usr/bin/env python3
"""
Compare les noms des joueurs adverses de la base aux libellés Wikidata des joueurs de leur équipe nationale
(P54 « membre de l'équipe sportive »), pour retrouver les accents et signes diacritiques manquants. Voir docs/sources.md.

  python3 scripts/wikipedia/accents_adversaires.py                # bilan par pays
  python3 scripts/wikipedia/accents_adversaires.py --write        # écrit aussi database/data/sources/noms_adversaires.csv

Puis python3 scripts/wikipedia/renommer_adversaires.py (feuilles JSON) et php artisan xv:apply-opponent-name-corrections (base).

Rapprochement base ↔ Wikidata : même pays, même nom complet sans accents, un seul joueur Wikidata de ce nom, et
une date de naissance (quand Wikidata la connaît) compatible avec les années de sélection en base (16 à 45 ans).
Le libellé retenu est celui de la langue du pays, à défaut le français ou l'anglais. Seuls sont relevés les
écarts d'accents : un nom écrit autrement (sans accents) n'est pas proposé. Les macrons des translittérations
(japonais Shūhei, maori Nēpia) sont écartés, comme dans la presse française. Réponses gardées dans storage/app/wikipedia.
"""
import sys, os, re, csv, json, sqlite3, hashlib, unicodedata, urllib.request, urllib.parse
from collections import defaultdict

DB = 'database/database.sqlite'
OUT = 'database/data/sources/noms_adversaires.csv'
MACRONS = set('āēīōūĀĒĪŌŪ')
# Libellés Wikidata écartés (prénom usuel sans accent) ou complétés (diacritiques oubliés), par libellé
KNOWN = {
    'Noel Henderson': None,
    'Mihaita Lazăr': ('Mihăiță', 'Lazăr'),
}
CACHE = 'storage/app/wikipedia'
UA = 'xvfrance.fr/1.0 (https://xvfrance.fr)'

# Code World Rugby → (équipe nationale Wikidata, langues des libellés par ordre de préférence)
TEAMS = {
    'ARG': ('Q646070', ['es']), 'URU': ('Q276760', ['es']), 'ESP': ('Q250783', ['es']), 'CHI': ('Q334657', ['es']),
    'ITA': ('Q113135', ['it']), 'ROU': ('Q967505', ['ro']), 'GER': ('Q695374', ['de']), 'POR': ('Q250778', ['pt']),
    'BRA': ('Q2721746', ['pt']), 'TCH': ('Q1816139', ['cs']), 'RSA': ('Q498478', ['af', 'en']),
    'NAM': ('Q1248558', ['de', 'af', 'en']), 'CIV': ('Q1675853', ['fr']), 'CAN': ('Q774221', ['en', 'fr']),
    'ENG': ('Q378628', ['en']), 'WAL': ('Q822877', ['en', 'cy']), 'SCO': ('Q945016', ['en']),
    'IRL': ('Q599903', ['en', 'ga']), 'NZL': ('Q55801', ['en']), 'AUS': ('Q622443', ['en']),
    'FIJ': ('Q1134669', ['en']), 'SAM': ('Q1138539', ['en']), 'TGA': ('Q1134659', ['en']),
    'JPN': ('Q1122433', ['en']), 'USA': ('Q1143805', ['en']), 'GEO': ('Q609885', ['en']),
    'ZIM': ('Q1544873', ['en']),
}


def norm(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z]+', ' ', s).strip()


def marks(s):
    """Nombre de lettres accentuées ou diacritées."""
    return sum(1 for c in unicodedata.normalize('NFC', s) if not c.isascii() and c.isalpha())


def sparql(query):
    path = os.path.join(CACHE, hashlib.md5(query.encode()).hexdigest() + '.json')
    if os.path.exists(path):
        return json.load(open(path))
    req = urllib.request.Request('https://query.wikidata.org/sparql?' + urllib.parse.urlencode({'query': query, 'format': 'json'}),
                                 headers={'User-Agent': UA})
    data = json.load(urllib.request.urlopen(req, timeout=120))['results']['bindings']
    os.makedirs(CACHE, exist_ok=True)
    json.dump(data, open(path, 'w'))
    return data


def team_players(team, langs):
    """{Q: {'labels': [libellés par ordre de préférence], 'birth': année ou None}}"""
    wanted = list(dict.fromkeys(langs + ['fr', 'en']))
    rows = sparql(f'''SELECT ?p ?label ?lang (YEAR(?birth) AS ?year) WHERE {{
        ?p wdt:P54 wd:{team}.
        ?p rdfs:label ?label. BIND(LANG(?label) AS ?lang) FILTER(?lang IN ({', '.join(f'"{l}"' for l in wanted)}))
        OPTIONAL {{ ?p wdt:P569 ?birth }} }}''')
    people = defaultdict(lambda: {'labels': {}, 'birth': None})
    for r in rows:
        q = r['p']['value'].rsplit('/', 1)[1]
        people[q]['labels'][r['lang']['value']] = r['label']['value']
        if 'year' in r:
            people[q]['birth'] = int(r['year']['value'])
    return {q: {'labels': [p['labels'][l] for l in wanted if l in p['labels']], 'birth': p['birth']}
            for q, p in people.items()}


def split_like(label, first, last):
    """Découpe un libellé comme le nom de la base (même nombre de mots dans le prénom), sinon None."""
    words, n = label.split(), len(first.split())
    if norm(label) != norm(f'{first} {last}') or len(words) != n + len(last.split()):
        return None
    return ' '.join(words[:n]), ' '.join(words[n:])


def main():
    db = sqlite3.connect(DB)
    rows = db.execute('''SELECT p.id, c.code, p.first_name, p.last_name, p.slug,
            MIN(strftime('%Y', m.match_date)), MAX(strftime('%Y', m.match_date))
        FROM players p JOIN countries c ON c.id = p.country_id
        LEFT JOIN match_lineups l ON l.player_id = p.id LEFT JOIN matches m ON m.id = l.match_id
        WHERE c.code != 'FRA' GROUP BY p.id''').fetchall()

    found, stats = [], defaultdict(lambda: [0, 0, 0])
    for code, (team, langs) in TEAMS.items():
        people = team_players(team, langs)
        by_name = defaultdict(list)
        for q, p in people.items():
            for key in {norm(l) for l in p['labels']}:
                by_name[key].append(q)
        for pid, c, first, last, slug, y1, y2 in rows:
            if c != code:
                continue
            stats[code][0] += 1
            qs = by_name.get(norm(f'{first} {last}'), [])
            if y1:
                qs = [q for q in qs if not people[q]['birth'] or 16 <= int(y1) - people[q]['birth'] and int(y2) - people[q]['birth'] <= 45]
            if len(qs) != 1:
                continue
            stats[code][1] += 1
            labels = [l for l in people[qs[0]]['labels'] if split_like(l, first, last) and not MACRONS & set(l)]
            if not labels:
                continue
            best = labels[0]
            if marks(best) <= marks(f'{first} {last}') or best == f'{first} {last}':
                continue
            new_first, new_last = split_like(best, first, last)
            if f'{first} {last}' in KNOWN or best in KNOWN:
                fix = KNOWN.get(best, KNOWN.get(f'{first} {last}'))
                if not fix:
                    continue
                new_first, new_last = fix
            stats[code][2] += 1
            found.append({'country': code, 'slug': slug, 'base_first_name': first, 'base_last_name': last,
                          'first_name': new_first, 'last_name': new_last, 'wikidata': qs[0],
                          'annees': f'{y1}-{y2}' if y1 else '', 'statut': 'vérifié'})

    print(f"{'Pays':5} {'Base':>5} {'Wikidata':>9} {'Accents':>8}")
    for code, (n, matched, fixed) in sorted(stats.items(), key=lambda x: -x[1][2]):
        print(f'{code:5} {n:5} {matched:9} {fixed:8}')
    print(f'Total : {len(found)} noms à accentuer')
    if '--write' in sys.argv:
        with open(OUT, 'w', encoding='utf-8', newline='') as f:
            w = csv.DictWriter(f, fieldnames=list(found[0].keys()))
            w.writeheader()
            w.writerows(sorted(found, key=lambda r: (r['country'], r['base_last_name'], r['base_first_name'])))


if __name__ == '__main__':
    main()
