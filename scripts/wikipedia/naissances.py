#!/usr/bin/env python3
"""
Compare les dates de naissance et de décès des internationaux français de la base à celles de Wikidata,
atteintes par les articles liés depuis la page « Liste des sélectionnés en équipe de France de rugby à XV ».
Voir docs/sources.md.

  python3 scripts/wikipedia/naissances.py                # bilan
  python3 scripts/wikipedia/naissances.py --csv FICHIER  # écrit aussi le détail joueur par joueur

Rapprochement liste ↔ base : même date de première sélection et même nom (sans accents), à défaut même nom
de famille ce jour-là. Les réponses des API sont gardées dans storage/app/wikipedia.
"""
import sys, os, re, csv, json, sqlite3, difflib, hashlib, unicodedata, urllib.request, urllib.parse

PAGE = "Liste des sélectionnés en équipe de France de rugby à XV"
DB = 'database/database.sqlite'
SITE_CSV = 'database/data/sources/equipe_france_players.csv'
CACHE = 'storage/app/wikipedia'
UA = 'xvfrance.fr/1.0 (https://xvfrance.fr)'
MOIS = {'janvier': 1, 'février': 2, 'mars': 3, 'avril': 4, 'mai': 5, 'juin': 6, 'juillet': 7, 'août': 8,
        'septembre': 9, 'octobre': 10, 'novembre': 11, 'décembre': 12}


def norm(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z]+', ' ', s).strip()


def api(url, params):
    query = urllib.parse.urlencode(params)
    path = os.path.join(CACHE, hashlib.md5((url + query).encode()).hexdigest() + '.json')
    if os.path.exists(path):
        return json.load(open(path))
    req = urllib.request.Request(url + '?' + query, headers={'User-Agent': UA})
    data = json.load(urllib.request.urlopen(req, timeout=60))
    os.makedirs(CACHE, exist_ok=True)
    json.dump(data, open(path, 'w'))
    return data


def wikipedia(params):
    return api('https://fr.wikipedia.org/w/api.php', {**params, 'format': 'json', 'formatversion': 2})


def liste():
    """[(titre de l'article, libellé, date de première sélection)]"""
    text = wikipedia({'action': 'parse', 'page': PAGE, 'prop': 'wikitext'})['parse']['wikitext']
    rows = []
    for row in re.split(r'\n\|-+', text):
        m = re.match(r'\s*\|\s*\[\[([^\]|]+)(?:\|([^\]]+))?\]\]', row)
        # Date de première sélection, avec ou sans {{date}} (la liste s'en passe à partir de février 2020)
        d = re.search(r'(?:\{\{date\||\ble )(\d+)(?:er)?\s+(\w+)\s+(\d{4})', row)
        if m and d and d.group(2) in MOIS:
            first = f'{d.group(3)}-{MOIS[d.group(2)]:02d}-{int(d.group(1)):02d}'
            rows.append((m.group(1).strip(), (m.group(2) or m.group(1)).strip(), first))
    return rows


def wikidata_ids(titles):
    ids = {}
    for i in range(0, len(titles), 50):
        r = wikipedia({'action': 'query', 'titles': '|'.join(titles[i:i + 50]), 'redirects': 1,
                       'prop': 'pageprops', 'ppprop': 'wikibase_item'})['query']
        alias = {x['from']: x['to'] for x in r.get('normalized', []) + r.get('redirects', [])}
        items = {p['title']: p.get('pageprops', {}).get('wikibase_item') for p in r['pages']}
        for t in titles[i:i + 50]:
            final = t
            while final in alias:
                final = alias[final]
            ids[t] = items.get(final)
    return ids


def wikidata_dates(qids):
    """{Q: (naissance, décès)} ; une date au mois ou à l'année près est tronquée (1885-03, 1885)."""
    def date(claims, prop):
        values = []
        for c in claims.get(prop, []):
            v = c['mainsnak'].get('datavalue', {}).get('value')
            if v and c.get('rank') != 'deprecated':
                t = v['time'].lstrip('+')[:10]
                values.append(t[:{11: 10, 10: 7}.get(v['precision'], 4)])
        return ' / '.join(dict.fromkeys(values))

    out = {}
    for i in range(0, len(qids), 50):
        r = api('https://www.wikidata.org/w/api.php', {'action': 'wbgetentities', 'ids': '|'.join(qids[i:i + 50]),
                                                       'props': 'claims', 'format': 'json'})
        for q, e in r['entities'].items():
            out[q] = (date(e.get('claims', {}), 'P569'), date(e.get('claims', {}), 'P570'))
    return out


def base_players():
    db = sqlite3.connect(DB)
    return db.execute("""
        SELECT p.id, p.first_name, p.last_name, substr(p.birth_date, 1, 10), substr(p.death_date, 1, 10),
               MIN(m.match_date)
        FROM players p
        JOIN match_lineups l ON l.player_id = p.id AND l.team_side = 'france' AND l.played = 1
        JOIN matches m ON m.id = l.match_id
        GROUP BY p.id""").fetchall()


def site_births():
    births = {}
    for r in csv.DictReader(open(SITE_CSV)):
        births[(norm(r['first_name'] + ' ' + r['last_name']), r['match_date'][:10])] = r['birth_date'][:10]
    return births


def rapprocher(label, first, players, by_date):
    """Joueurs de la base qui correspondent à une ligne de la liste (un seul attendu)."""
    candidates = by_date.get(first, [])
    found = [p for p in candidates if norm(p[1] + ' ' + p[2]) == norm(label)]
    if not found:
        found = [p for p in candidates if norm(p[2]) and norm(label).endswith(norm(p[2]))]
    if not found:
        # Date de première sélection décalée d'un côté ou de l'autre : même nom, moins d'un an d'écart
        found = [p for p in players if norm(p[1] + ' ' + p[2]) == norm(label)
                 and abs(int(p[5][:4]) - int(first[:4])) <= 1]
    if not found:
        # Graphies différentes (de Malmann / de Mallemann, Iguiniz / Iguinitz) : nom proche, même époque
        found = [p for p in players if abs(int(p[5][:4]) - int(first[:4])) <= 1
                 and difflib.SequenceMatcher(None, norm(p[1] + ' ' + p[2]), norm(label)).ratio() >= 0.85]
    return found


def main():
    rows = liste()
    ids = wikidata_ids(sorted({t for t, _, _ in rows}))
    dates = wikidata_dates(sorted({q for q in ids.values() if q}))
    site = site_births()

    players = base_players()
    by_date = {}
    for p in players:
        by_date.setdefault(p[5][:10], []).append(p)

    result, unmatched = [], []
    for title, label, first in rows:
        found = rapprocher(label, first, players, by_date)
        if len(found) != 1:
            unmatched.append((label, first, len(found)))
            continue
        p = found[0]
        q = ids.get(title)
        wbirth, wdeath = dates.get(q, ('', ''))
        result.append({'player_id': p[0], 'joueur': f'{p[1]} {p[2]}', 'premier_match': p[5][:10],
                       'naissance_base': p[3] or '',
                       'naissance_site': site.get((norm(p[1] + ' ' + p[2]), p[5][:10]), ''),
                       'naissance_wikidata': wbirth, 'deces_base': p[4] or '', 'deces_wikidata': wdeath,
                       'article': title, 'wikidata': q or ''})

    def differs(base, wiki):
        # Une date Wikidata tronquée (année ou mois seuls) s'accorde avec une date complète qui commence pareil
        return bool(base and wiki) and not any(base.startswith(w) for w in wiki.split(' / '))

    print(f'{len(rows)} lignes dans la liste, {len(result)} rapprochées de la base, {len(unmatched)} non rapprochées')
    print(f"{sum(1 for r in result if r['wikidata'])} avec un élément Wikidata, "
          f"{sum(1 for r in result if r['naissance_wikidata'])} avec une date de naissance")
    print(f"Naissance : {sum(1 for r in result if differs(r['naissance_base'], r['naissance_wikidata']))} "
          f"désaccords base/Wikidata, {sum(1 for r in result if not r['naissance_base'] and r['naissance_wikidata'])} "
          f"absentes de la base mais sur Wikidata")
    print(f"Décès : {sum(1 for r in result if differs(r['deces_base'], r['deces_wikidata']))} désaccords, "
          f"{sum(1 for r in result if not r['deces_base'] and r['deces_wikidata'])} absents de la base mais sur Wikidata")
    for u in unmatched:
        print('  non rapproché :', *u)

    if '--csv' in sys.argv:
        with open(sys.argv[sys.argv.index('--csv') + 1], 'w', newline='') as f:
            w = csv.DictWriter(f, fieldnames=list(result[0]))
            w.writeheader()
            w.writerows(result)


if __name__ == '__main__':
    main()
