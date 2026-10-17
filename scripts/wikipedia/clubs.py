#!/usr/bin/env python3
"""
Relève les clubs (seniors) des internationaux français dans l'encadré {{Infobox Rugbyman}} de leur article Wikipédia,
atteint depuis la page « Liste des sélectionnés en équipe de France de rugby à XV ». Voir docs/sources.md.

  python3 scripts/wikipedia/clubs.py    # écrit database/data/sources/wikipedia_clubs.csv

Une ligne par joueur : première sélection (date, numéro de maillot) et clubs dans l'ordre de l'encadré, séparés par
« ; ». Un club lié est noté par le titre de son article, sinon par le texte de l'encadré ; club_names.csv fait
la correspondance avec les clubs du site (xv:import-clubs, qui ne s'en sert qu'à défaut de la liste
« This Is Rugby »).
"""
import csv, re, sqlite3

from naissances import DB, liste, base_players, rapprocher, wikipedia

OUT = 'database/data/sources/wikipedia_clubs.csv'
# Intertitres de l'encadré, pas des clubs (« Rugby à XV », puis « Rugby à XIII », « Franchises »…)
HEADINGS = {'rugby à xv', 'rugby à xiii', 'rugby à sept', 'xv', 'xiii', 'à xv', 'à xiii', 'clubs', 'club',
            'franchises', 'franchise'}


def wikitexts(titles):
    """{titre de la liste: wikitexte de l'article}, redirections suivies"""
    out = {}
    for i in range(0, len(titles), 50):
        r = wikipedia({'action': 'query', 'titles': '|'.join(titles[i:i + 50]), 'redirects': 1,
                       'prop': 'revisions', 'rvprop': 'content', 'rvslots': 'main'})['query']
        alias = {x['from']: x['to'] for x in r.get('normalized', []) + r.get('redirects', [])}
        texts = {p['title']: p['revisions'][0]['slots']['main']['content'] for p in r['pages'] if 'revisions' in p}
        for t in titles[i:i + 50]:
            final = t
            while final in alias:
                final = alias[final]
            out[t] = texts.get(final, '')
    return out


def clubs(text):
    """Clubs seniors du champ « club » de l'encadré"""
    m = re.search(r'^\s*\|\s*club\s*=(.*?)(?=^\s*\||^\}\})', text, re.M | re.S)
    if not m:
        return []
    value = re.sub(r'<ref[^>]*/>|<ref.*?</ref>|<!--.*?-->', '', m.group(1), flags=re.S)
    names = []
    for part in re.split(r'<br\s*/?>|\n\*?', value):
        part = re.sub(r'\{\{[^{}]*\}\}', '', part).strip(" *'\t")
        link = re.search(r'\[\[([^\]|#]+)', part)
        name = link.group(1).strip() if link else re.sub(r'\(.*?\)', '', part).strip(' ,')
        if name and name.lower() not in HEADINGS:
            names.append(name)
    return names


def main():
    rows = liste()
    texts = wikitexts(sorted({t for t, _, _ in rows}))
    players = base_players()
    by_date = {}
    for p in players:
        by_date.setdefault(p[5][:10], []).append(p)
    jerseys = dict(sqlite3.connect(DB).execute("""
        SELECT l.player_id, l.jersey_number FROM match_lineups l JOIN matches m ON m.id = l.match_id
        WHERE l.team_side = 'france' AND l.played = 1 ORDER BY m.match_date DESC""").fetchall())

    out, empty = [], 0
    for title, label, first in rows:
        found = rapprocher(label, first, players, by_date)
        if len(found) != 1:
            continue
        p = found[0]
        names = clubs(texts.get(title, ''))
        if not names:
            empty += 1
            continue
        out.append({'match_date': p[5][:10], 'jersey': jerseys[p[0]], 'joueur': f'{p[1]} {p[2]}',
                    'article': title, 'clubs': ' ; '.join(names)})

    out.sort(key=lambda r: (r['match_date'], r['jersey']))
    with open(OUT, 'w', newline='') as f:
        w = csv.DictWriter(f, fieldnames=list(out[0]))
        w.writeheader()
        w.writerows(out)
    print(f'{len(out)} joueurs avec des clubs, {empty} articles sans champ « club » → {OUT}')


if __name__ == '__main__':
    main()
