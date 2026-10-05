#!/usr/bin/env python3
"""
Génère les feuilles de match JSON (format docs/specs/import-match-data.md) à partir des pages
equipe-france.fr en cache (storage/app/equipe-france/matches), sans réseau ni modèle de langage.

  python3 scripts/equipe_france/feuilles.py                 # écrit les feuilles absentes
  python3 scripts/equipe_france/feuilles.py --dry-run       # rapport seulement
  python3 scripts/equipe_france/feuilles.py --out DIR       # écrit ailleurs (étalonnage)
  python3 scripts/equipe_france/feuilles.py --from 1906 --to 1939

Rapprochement page ↔ match : même adversaire, date à un jour près (comme xv:apply-equipe-france).
Les scores écrits sont ceux de la base, qui fait foi. Une feuille existante avec des compositions
n'est jamais touchée ; une feuille sans composition (match créé à la main) est complétée.
Voir docs/specs/feuilles-historiques-equipe-france.md.
"""
import argparse, collections, csv, datetime, html, json, os, re, sqlite3, sys, unicodedata

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CACHE = os.path.join(ROOT, 'storage/app/equipe-france/matches')
RELEVE = os.path.join(ROOT, 'database/data/sources/equipe_france_matches.csv')
SPLITS = os.path.join(ROOT, 'database/data/sources/equipe_france_name_splits.csv')
MATCHES = os.path.join(ROOT, 'database/data/matches')
DB = os.path.join(ROOT, 'database/database.sqlite')

COUNTRY_ALIASES = {
    "Allemagne de l'Ouest": 'Allemagne',
    "Fédération de l'Armée de terre britannique": 'Armée britannique',
    'Lions britanniques et irlandais': 'Lions Britanniques',
    'Îles du Pacifique': 'Pacific Islanders',
}
POSITIONS = {1: 'pilier_gauche', 2: 'talonneur', 3: 'pilier_droit', 4: 'deuxieme_ligne', 5: 'deuxieme_ligne',
             6: 'troisieme_ligne_aile', 7: 'troisieme_ligne_aile', 8: 'numero_huit', 9: 'demi_de_melee',
             10: 'demi_ouverture', 11: 'ailier', 12: 'centre', 13: 'centre', 14: 'ailier', 15: 'arriere'}
# Libellés du site → EventType
TYPES = {'essai': 'essai', 'essais': 'essai', 'transformation': 'transformation', 'transformations': 'transformation',
         'pénalité': 'penalite', 'pénalités': 'penalite', 'drop': 'drop', 'drops': 'drop',
         'essai de pénalité': 'essai_penalite', 'essais de pénalité': 'essai_penalite'}
# Particules qui ouvrent un nom de famille (« Jean de Gregorio », « Duhan Van Der Merwe »)
PARTICLES = {'de', 'du', 'des', 'van', 'von', 'le', 'la', 'del', 'della', 'di', 'da', 'dal', 'lo', 'degli', 'ben', 'mac', 'st', 'st.', 'te', 'ten'}
SUFFIXES = {'jr', 'jr.', 'ii', 'iii', 'iv'}


def clean_name(full):
    """« Tom Palmer ★ », « Charles 'Red' Meehan », « Andy Moore (S/H) », « Dr. Carl Loos », « Damian Mckenzie » → nom seul."""
    full = re.sub(r"\s'\s*[^']+'\s", ' ', full)          # surnom entre guillemets
    full = re.sub(r'\([^)]*\)|[★*]', ' ', full)
    full = re.sub(r'^Dr\.?\s+', '', full.strip())
    full = re.sub(r"\b(Mc|O')([a-z])", lambda m: m.group(1) + m.group(2).upper(), full)
    return re.sub(r'\s+', ' ', full).strip()


def norm(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', ' ', s).strip()


def text(fragment):
    return html.unescape(re.sub(r'<[^>]+>', '', fragment)).strip()


def block(h, start, end_markers):
    """Contenu HTML de la première occurrence de `start` jusqu'au premier marqueur de fin trouvé après."""
    i = h.find(start)
    if i < 0:
        return ''
    ends = [j for j in (h.find(m, i + len(start)) for m in end_markers) if j > 0]
    return h[i:min(ends)] if ends else h[i:]


def side_parts(fragment):
    """Sépare un bloc en parties dom / ext d'après les div de premier niveau."""
    parts = {'dom': '', 'ext': ''}
    for m in re.finditer(r'<div class="(dom|ext)">', fragment):
        nxt = re.search(r'<div class="(?:dom|ext)">|<h3>', fragment[m.end():])
        parts[m.group(1)] += fragment[m.end(): m.end() + nxt.start()] if nxt else fragment[m.end():]
    return parts


def parse_players(fragment):
    players = []
    for li in re.findall(r'<li>(.*?)</li>', fragment, re.S):
        captain = 'class="capitaine"' in li
        li = re.sub(r'<strong class="capitaine">.*?</strong>', '', li)
        num = re.search(r'<span class="num">\s*(\d+)\s*</span>', li)
        slug = re.search(r'href="/rugby/([^"]+)"', li)
        name = text(re.sub(r'<span class="num">.*?</span>', '', li))
        if not name:
            continue
        players.append({'num': int(num.group(1)) if num else None, 'name': re.sub(r'\s+', ' ', name),
                        'slug': slug.group(1) if slug else None, 'captain': captain})
    return players


def parse_page(h):
    eff = block(h, 'class="matcheffectif', ['<h2>Staff', 'id="matchofficiel"'])
    starters_html, _, subs_html = eff.partition('<h3>Remplaçants</h3>')
    lineups = {}
    for side in ('dom', 'ext'):
        lineups[side] = parse_players(side_parts(starters_html)[side]) + \
            [dict(p, sub=True) for p in parse_players(side_parts(subs_html)[side])]

    scorers = {'dom': collections.Counter(), 'ext': collections.Counter()}
    but = block(h, '<div class="matchbut">', ['boxchiantefix', 'matcheffectif'])
    for side, frag in side_parts(but).items():
        for label, ul in re.findall(r'class="buttype">([^<]*)</div>\s*<ul class="buttypeliste">(.*?)</ul>', frag, re.S):
            etype = TYPES.get(label.strip().lower())
            if not etype:
                continue
            for li in re.findall(r'<li>(.*?)</li>', ul, re.S):
                name = text(re.search(r'class="nomjoueur">(.*?)</span>', li, re.S).group(1)) if 'nomjoueur' in li else ''
                n = re.search(r'class="num">\s*\((\d+)\)', li)
                if etype == 'essai_penalite' or name in ('', '-'):
                    name = None
                scorers[side][(name, etype)] += int(n.group(1)) if n else 1

    timeline = []
    der = block(h, 'class="matchderoulement"', ['</table>'])
    for tr in re.findall(r'<tr>(.*?)</tr>', der, re.S):
        minute = re.search(r'class="temps">\s*(\d+)', tr)
        for side in ('dom', 'ext'):
            cell = re.search(rf'<td class="{side}">(.*?)</td>', tr, re.S)
            if not cell or 'nomjoueur' not in cell.group(1):
                continue
            em = re.search(r'<em>([^<]*)</em>', cell.group(1))
            etype = TYPES.get(em.group(1).strip().lower()) if em else None
            name = text(re.sub(r'<em>.*?</em>', '', cell.group(1))) or None
            if etype == 'essai_penalite' or name == '-':
                name = None
            if etype:
                timeline.append({'side': side, 'name': name, 'type': etype,
                                 'minute': int(minute.group(1)) if minute else None})

    off = block(h, 'id="matchofficiel"', ['</ul>'])
    ref = re.search(r'class="type">\s*Arbitre\s*:\s*</span>([^<]*)<', off)
    return lineups, scorers, timeline, (text(ref.group(1)) if ref else None)


class Names:
    """Découpe « Prénom Nom » : nom en base s'il existe, sinon exceptions du CSV, sinon particules puis dernier mot."""

    def __init__(self, db):
        self.known = collections.defaultdict(dict)
        for code, first, last in db.execute('select c.code, p.first_name, p.last_name from players p join countries c on c.id = p.country_id'):
            self.known[code][norm(f'{first} {last}')] = (first, last)
        self.overrides, self.by_slug = {}, {}
        if os.path.exists(SPLITS):
            with open(SPLITS, encoding='utf-8') as f:
                for row in csv.DictReader(f):
                    if row.get('site_slug'):  # joueur français : le slug du site l'identifie, homonymes compris
                        self.by_slug[row['site_slug']] = (row['first_name'], row['last_name'], row.get('cap_number') or None)
                    else:
                        self.overrides[norm(row['site_name'])] = (row['first_name'], row['last_name'])
        self.guessed = collections.Counter()  # noms de 3 mots ou plus découpés par défaut, pour relecture

    def split(self, full, code):
        key = norm(full)
        if key in self.known[code]:
            return self.known[code][key]
        if key in self.overrides:
            return self.overrides[key]
        tokens = clean_name(full).split()
        suffix = ''
        if len(tokens) > 2 and tokens[-1].lower() in SUFFIXES:
            last = tokens.pop()
            suffix = ' ' + (last.upper() if last.lower().startswith('i') else last)
        first, last = self._split_tokens(tokens, code, full)
        return first, last + suffix

    def _split_tokens(self, tokens, code, full):
        if len(tokens) == 1:
            return '', tokens[0]
        for i in range(1, len(tokens) - 1):
            if tokens[i].lower() in PARTICLES:
                return ' '.join(tokens[:i]), ' '.join(tokens[i:])
        if len(tokens) > 2:
            self.guessed[(code, full)] += 1
        return ' '.join(tokens[:-1]), tokens[-1]


def point_value(etype, date):
    """Barème de EventType::points() : drop à 4 points avant juillet 1948, essai à 3, 4 puis 5, essai de pénalité à 7 depuis 2017."""
    if etype == 'transformation':
        return 2
    if etype == 'penalite':
        return 3
    if etype == 'drop':
        return 4 if date < '1948-07-01' else 3
    if etype == 'essai_penalite' and date >= '2017-07-01':
        return 7
    return 3 if date < '1971-07-01' else 4 if date < '1992-07-01' else 5


def dedupe(rows):
    """Retire une ligne qui répète la précédente du même joueur et du même type à une minute près."""
    kept = []
    for e in rows:
        if e['minute'] is not None and any(k['name'] == e['name'] and k['type'] == e['type'] and k['minute'] is not None
                                           and abs(k['minute'] - e['minute']) <= 1 for k in kept):
            continue
        kept.append(e)
    return kept


def build_sheet(match, page, names, report, mismatches):
    lineups_raw, scorers, timeline, referee = page
    fr_side = 'dom' if match['site_home'] == 'France' else 'ext'
    sides = {fr_side: ('france', 'FRA'), ('ext' if fr_side == 'dom' else 'dom'): ('adversaire', match['code'])}
    sheet = {'match_date': match['date'], 'opponent_code': match['code'],
             'france_score': match['france_score'], 'opponent_score': match['opponent_score']}
    if referee and referee != '-':
        sheet['referee'] = referee

    lineups, by_name = {}, {}
    for side, (team, code) in sides.items():
        players = lineups_raw[side]
        # Avant 1920 environ, le site ne numérote pas les avants : un bloc de titulaires de 15 joueurs ou
        # moins est entièrement titulaire. Au-delà, les joueurs sans numéro du bloc sont des remplaçants.
        all_starters = sum(1 for p in players if not p.get('sub')) <= 15
        used, numbered = set(), []
        for p in players:
            starter = not p.get('sub') and (all_starters or (p['num'] or 99) <= 15)
            jersey = p['num'] if p['num'] and p['num'] <= 23 and p['num'] not in used and (p['num'] <= 15) == starter else None
            if jersey:
                used.add(jersey)
            numbered.append((p, starter, jersey))
        rows = []
        for p, starter, jersey in numbered:
            known = jersey is not None
            if not known:  # numéro absent, en double ou au-delà de 23 : premier numéro libre, sans poste déduit
                jersey = next((n for n in range(1 if starter else 16, 24) if n not in used), None)
                if jersey is None:  # joueur de trop (doublon d'orthographe sur le site)
                    report['joueurs écartés (aucun numéro libre)'] += 1
                    continue
                used.add(jersey)
                report['numéros attribués'] += 1
            first, last = names.split(p['name'], code)
            cap = None
            if p['slug'] in names.by_slug:  # nom vérifié (noms_ecarts.csv)
                first, last, cap = names.by_slug[p['slug']]
            row = {'jersey': jersey, 'first_name': first, 'last_name': last, 'is_starter': starter,
                   'position': POSITIONS.get(jersey) if starter and known else None, 'is_captain': p['captain']}
            if cap:
                row['cap_number'] = int(cap)
            rows.append(row)
            by_name[(side, norm(p['name']))] = (first, last)
        rows.sort(key=lambda r: r['jersey'])
        lineups[team] = rows
    sheet['lineups'] = lineups

    def who(side, name):
        if name is None:
            return None, None
        if (side, norm(name)) in by_name:
            return by_name[(side, norm(name))]
        report['marqueurs hors composition'] += 1
        return names.split(name, sides[side][1])

    def points(rows):
        return sum(point_value(e['type'], match['date']) for e in rows)

    events = []
    for side in sides:
        target = match['france_score'] if sides[side][0] == 'france' else match['opponent_score']
        summary = [{'side': side, 'name': n, 'type': t, 'minute': None} for (n, t), c in scorers[side].items() for _ in range(c)]
        drops = [e for e in summary if e['type'] == 'drop']
        timed = [e for e in timeline if e['side'] == side]
        # Le site répète parfois une ligne du déroulement (même joueur, même type, à une minute près) :
        # on retient la première version dont les points égalent le score de la base, sinon le résumé.
        candidates = [('déroulement', timed + drops), ('déroulement dédoublonné', dedupe(timed) + drops), ('résumé', summary)]
        if not timed:
            candidates = candidates[2:]
        label, rows = next(((l, r) for l, r in candidates if points(r) == target), (None, None))
        report[f'points : {label or "≠ score"}'] += 1
        if rows is None:  # le validateur refuserait la feuille : pas de points pour ce match, à reprendre à la main
            mismatches.append(f"{match['date']} {match['code']} {sides[side][0]} : score {target}, site {points(summary)}")
            events = None
            break
        for e in rows:
            first, last = who(side, e['name'])
            events.append({'team_side': sides[side][0], 'type': e['type'], 'player_last_name': last,
                           'player_first_name': first, 'minute': e['minute']})
    events = events or []
    events.sort(key=lambda e: (e['minute'] is None, e['minute'] or 0))
    sheet['events'] = events
    sheet['substitutions'] = []
    return sheet


def dump(sheet):
    """Même mise en forme que les feuilles saisies : une ligne par joueur, événement ou remplacement."""
    one = lambda o: json.dumps(o, ensure_ascii=False)
    lines = ['{']
    items = list(sheet.items())
    for i, (k, v) in enumerate(items):
        comma = ',' if i < len(items) - 1 else ''
        if k == 'lineups':
            lines.append('  "lineups": {')
            teams = list(v.items())
            for j, (team, rows) in enumerate(teams):
                body = ',\n'.join(f'      {one(r)}' for r in rows)
                lines.append(f'    "{team}": [\n{body}\n    ]' + (',' if j < len(teams) - 1 else '') if rows else f'    "{team}": []' + (',' if j < len(teams) - 1 else ''))
            lines.append('  }' + comma)
        elif isinstance(v, list):
            lines.append(f'  "{k}": [\n' + ',\n'.join(f'    {one(r)}' for r in v) + '\n  ]' + comma if v else f'  "{k}": []{comma}')
        else:
            lines.append(f'  "{k}": {one(v)}{comma}')
    return '\n'.join(lines) + '\n}\n'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--dry-run', action='store_true')
    ap.add_argument('--out', default=MATCHES)
    ap.add_argument('--from', dest='year_from', type=int, default=1906)
    ap.add_argument('--to', dest='year_to', type=int, default=9999)
    ap.add_argument('--all', action='store_true', help='générer aussi les matches déjà détaillés (étalonnage)')
    args = ap.parse_args()

    db = sqlite3.connect(DB)
    countries = {norm(n): (cid, code) for cid, n, code in db.execute('select id, name, code from countries')}
    detailed = {mid for (mid,) in db.execute('select distinct match_id from match_lineups')}
    names = Names(db)
    report = collections.Counter()
    unmatched, mismatches, written = [], [], 0

    with open(RELEVE, encoding='utf-8') as f:
        rows = list(csv.DictReader(f))
    for row in rows:
        year = int(row['date'][:4])
        if not args.year_from <= year <= args.year_to:
            continue
        opponent = row['away'] if row['home'] == 'France' else row['home']
        country = countries.get(norm(COUNTRY_ALIASES.get(opponent, opponent)))
        day = datetime.date.fromisoformat(row['date'])
        found = country and db.execute(
            'select id, match_date, france_score, opponent_score from matches where opponent_id = ? and date(match_date) between ? and ? '
            'order by abs(julianday(match_date) - julianday(?)) limit 1',
            (country[0], str(day - datetime.timedelta(1)), str(day + datetime.timedelta(1)), str(day))).fetchone()
        if not found:
            unmatched.append(f"{row['date']} {opponent}")
            continue
        mid, mdate, fs, os_ = found
        if mid in detailed and not args.all:
            report['déjà détaillés en base'] += 1
            continue
        cache = os.path.join(CACHE, f"{row['site_id']}.html")
        if not os.path.exists(cache):
            report['page absente du cache'] += 1
            continue
        match = {'date': mdate[:10], 'code': country[1], 'france_score': fs, 'opponent_score': os_, 'site_home': row['home']}
        sheet = build_sheet(match, parse_page(open(cache, encoding='utf-8').read()), names, report, mismatches)

        path = os.path.join(args.out, mdate[:4], f"{mdate[:10]}-{country[1]}.json")
        if os.path.exists(path) and args.out == MATCHES:
            with open(path, encoding='utf-8') as f:
                existing = json.load(f)
            if existing.get('lineups', {}).get('france'):
                report['feuilles existantes conservées'] += 1
                continue
            # feuille créée sans composition : on garde ses champs et on ajoute le reste
            sheet = {**existing, **{k: v for k, v in sheet.items() if k not in existing}}
            report['feuilles complétées'] += 1
        written += 1
        if not args.dry_run:
            os.makedirs(os.path.dirname(path), exist_ok=True)
            with open(path, 'w', encoding='utf-8') as f:
                f.write(dump(sheet))

    print(f'{written} feuilles ' + ('à écrire' if args.dry_run else 'écrites') + f' dans {os.path.relpath(args.out, ROOT)}')
    for k, v in sorted(report.items()):
        print(f'  {k} : {v}')
    if unmatched:
        print(f'{len(unmatched)} pages sans match en base : ' + ', '.join(unmatched))
    if mismatches:
        print(f'{len(mismatches)} matches sans points (points du site ≠ score de la base) :')
        for m in mismatches:
            print(f'  {m}')
    if names.guessed:
        print(f'{len(names.guessed)} noms de 3 mots ou plus découpés par défaut (prénom = tout sauf le dernier mot) :')
        for (code, full), _ in sorted(names.guessed.items()):
            print(f'  {code} {full}')


if __name__ == '__main__':
    main()
