#!/usr/bin/env python3
"""
Reporte les noms vérifiés de database/data/sources/noms_adversaires.csv (statut « vérifié ») dans les feuilles JSON,
côté adversaire (compositions, points, remplacements). Un joueur y est reconnu à son nom sans accents, dans les
feuilles de son pays et de ses années de sélection (pas un homonyme d'une autre époque).
La base est renommée par php artisan xv:apply-opponent-name-corrections (lancée par deploy.sh avant l'import des feuilles).

  python3 scripts/wikipedia/renommer_adversaires.py
"""
import csv, glob, json, os, sys

sys.path.insert(0, os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'equipe_france'))
from feuilles import MATCHES, ROOT, dump, norm

CORRECTIONS = os.path.join(ROOT, 'database/data/sources/noms_adversaires.csv')


def main():
    changes = {}  # (pays, nom sans accents) → [(première année, dernière année, prénom, nom)]
    for row in csv.DictReader(open(CORRECTIONS, encoding='utf-8')):
        if row['statut'] != 'vérifié':
            continue
        years = [int(y) for y in row['annees'].split('-')] if row['annees'] else [0, 9999]
        key = (row['country'], norm(f"{row['base_first_name']} {row['base_last_name']}"))
        changes.setdefault(key, []).append((*years, row['first_name'], row['last_name']))

    def rename(code, year, first, last):
        for y1, y2, new_first, new_last in changes.get((code, norm(f'{first or ""} {last or ""}')), []):
            if y1 <= year <= y2:
                return new_first, new_last
        return None

    edited = 0
    for path in sorted(glob.glob(os.path.join(MATCHES, '*/*.json'))):
        sheet = json.load(open(path, encoding='utf-8'))
        code, year = sheet['opponent_code'], int(sheet['match_date'][:4])
        before = json.dumps(sheet, ensure_ascii=False)
        for p in sheet.get('lineups', {}).get('adversaire', []):
            if c := rename(code, year, p['first_name'], p['last_name']):
                p['first_name'], p['last_name'] = c
        for e in sheet.get('events', []):
            if e['team_side'] == 'adversaire' and (c := rename(code, year, e.get('player_first_name'), e.get('player_last_name'))):
                e['player_first_name'], e['player_last_name'] = c
        for s in sheet.get('substitutions', []):
            if s['team_side'] != 'adversaire':
                continue
            for k in ('off', 'on'):
                if c := rename(code, year, s.get(f'player_{k}_first_name'), s.get(f'player_{k}_last_name')):
                    s[f'player_{k}_first_name'], s[f'player_{k}_last_name'] = c
        if json.dumps(sheet, ensure_ascii=False) != before:
            edited += 1
            with open(path, 'w', encoding='utf-8') as f:
                f.write(dump(sheet))
    print(f'{sum(map(len, changes.values()))} noms, {edited} feuilles modifiées')


if __name__ == '__main__':
    main()
