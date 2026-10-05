#!/usr/bin/env python3
"""
Compare la base locale (SQLite) à la page « Liste des matchs de l'équipe de France de rugby à XV »
de Wikipédia, référence des matches officiels (avec sélection) du XV de France. Voir docs/sources.md.

  python3 scripts/wikipedia/liste_matchs.py                 # bilan et écarts
  python3 scripts/wikipedia/liste_matchs.py --dates          # affiche aussi les écarts de date ou de score

Les matches à venir de la page (calendrier) sont ignorés. Rapprochement : même adversaire, date la plus
proche à 40 jours près (la page comme la base ont quelques dates décalées).
"""
import sys, re, json, sqlite3, datetime, unicodedata, urllib.request, urllib.parse

PAGE = "Liste des matchs de l'équipe de France de rugby à XV"
DB = 'database/database.sqlite'
MOIS = {'janv': 1, 'jan': 1, 'janvier': 1, 'févr': 2, 'fév': 2, 'février': 2, 'mars': 3, 'avr': 4, 'avril': 4,
        'mai': 5, 'juin': 6, 'juil': 7, 'juill': 7, 'juillet': 7, 'août': 8, 'sept': 9, 'septembre': 9,
        'oct': 10, 'octobre': 10, 'nov': 11, 'novembre': 11, 'déc': 12, 'décembre': 12}
# Noms de la page → noms de la base
ADVERSAIRES = {'galles': 'pays de galles', '20px': 'australie', 'maori': 'maori de nouvelle-zelande',
               'empire britannique': 'armee britannique', 'british empire services': 'armee britannique',
               'army rugby union': 'armee britannique', 'grande-bretagne': 'armee britannique',
               'kiwis (new zealand army)': 'nouvelle-zelande', 'lions britanniques': 'lions britanniques'}


def norm(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower().strip()
    s = re.sub(r'^\{\{|\}\}$', '', s)
    return ADVERSAIRES.get(s, s)


def date_of(cell):
    """{{date|1|1|1906}}, 20 {{date||11|2022}}, 4 juill. 2026"""
    d = re.search(r'(\d{1,2})?\s*\{\{date\|(\d*|-)\|(\d+|[a-zéû]+)\|(\d{4})', cell)
    if d:
        day = d.group(2).strip('-') or d.group(1)
        month = int(d.group(3)) if d.group(3).isdigit() else MOIS[d.group(3)]
        return f'{d.group(4)}-{month:02d}-{int(day):02d}' if day else None
    d = re.search(r'(\d{1,2})(?:er)?\s+([a-zéû]+)\.?\s+(\d{4})', cell)
    if d and d.group(2) in MOIS:
        return f'{d.group(3)}-{MOIS[d.group(2)]:02d}-{int(d.group(1)):02d}'
    return None


def wikipedia_matches():
    url = 'https://fr.wikipedia.org/w/index.php?action=raw&title=' + urllib.parse.quote(PAGE.replace(' ', '_'))
    txt = urllib.request.urlopen(urllib.request.Request(url, headers={'User-Agent': 'xvfrance.fr data check'})).read().decode()
    txt = re.sub(r'<ref[^>]*/>|<ref.*?</ref>', '', txt, flags=re.S)
    # « 20 {{date||11|2022}} » : le « || » du modèle n'est pas un séparateur de cellules
    txt = txt.replace('{{date||', '{{date|-|')
    today = datetime.date.today().isoformat()
    matches = []
    # Une rangée par match : n°, date, adversaire, pour, contre, résultat, lieu, compétition
    # (une cellule par ligne ou plusieurs séparées par « || » selon les années)
    for row in re.split(r'\n\|-[^\n]*', txt):
        cells = [c.strip() for c in re.split(r'\n[|!]|\|\||!!', '\n' + row.strip())]
        cells = [re.sub(r'^(?:align|style|scope|bgcolor)=[^|]*\|\s*', '', c) for c in cells if c]
        if len(cells) < 6 or not re.fullmatch(r'\d{1,3}', cells[0]):
            continue
        date = date_of(cells[1])
        if not date or date > today:
            continue
        opp = (re.search(r'\{\{([^}|]+?) rugby', cells[2]) or re.search(r'\{\{[A-Z]{3}-d\}\}\s*(.+)', cells[2])
               or re.search(r'\[\[(?:[^]|]*\|)?([^]]+)\]\]', cells[2]) or re.search(r'\{\{([^}]+)\}\}', cells[2]))
        score = lambda s: int(re.sub(r'[^0-9]', '', s) or -1)
        matches.append({'n': int(cells[0]), 'date': date, 'opp': norm(opp.group(1) if opp else cells[2]),
                        'pour': score(cells[3]), 'contre': score(cells[4])})
    return matches


def main():
    wp = wikipedia_matches()
    db = sqlite3.connect(DB).execute(
        'select m.id, substr(m.match_date, 1, 10), c.name, m.france_score, m.opponent_score '
        'from matches m join countries c on c.id = m.opponent_id').fetchall()

    numbers = {m['n'] for m in wp}
    unread = sorted(set(range(1, max(numbers) + 1)) - numbers)
    used, wp_only, gaps = set(), [], []
    for m in sorted(wp, key=lambda m: m['date']):
        day = datetime.date.fromisoformat(m['date'])
        dist = lambda r: abs((datetime.date.fromisoformat(r[1]) - day).days)
        candidates = sorted((r for r in db if r[0] not in used and norm(r[2]) == m['opp'] and dist(r) <= 40), key=dist)
        if not candidates:
            wp_only.append(m)
            continue
        r = candidates[0]
        used.add(r[0])
        if r[1] != m['date'] or (r[3], r[4]) != (m['pour'], m['contre']):
            gaps.append(f"  n°{m['n']:>3} {m['opp']:<20} Wikipédia {m['date']} {m['pour']}-{m['contre']}"
                        f"  | base {r[1]} {r[3]}-{r[4]}")

    print(f'Wikipédia : {len(wp)} matches lus (n° 1 à {max(numbers)}), base : {len(db)}, appariés : {len(used)}')
    if unread:
        print(f'Numéros non lus sur la page (format à ajouter au script) : {unread}')
    print(f'\nSur Wikipédia, absents de la base : {len(wp_only)}')
    for m in wp_only:
        print(f"  n°{m['n']} {m['date']} {m['opp']} {m['pour']}-{m['contre']}")
    db_only = [r for r in db if r[0] not in used]
    print(f'\nEn base, absents de Wikipédia : {len(db_only)}')
    for r in db_only:
        print(f'  {r[1]} {r[2]} {r[3]}-{r[4]} (id {r[0]})')
    print(f'\nÉcarts de date ou de score : {len(gaps)} (à arbitrer : la page a aussi des erreurs)')
    if '--dates' in sys.argv:
        print('\n'.join(gaps))


if __name__ == '__main__':
    main()
