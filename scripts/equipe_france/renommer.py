#!/usr/bin/env python3
"""
Reporte les noms vérifiés de database/data/sources/noms_ecarts.csv (statut « vérifié ») :
- dans les feuilles JSON (côté France : compositions, points, remplacements) ;
- dans equipe_france_name_splits.csv, par slug equipe-france.fr, pour que feuilles.py ne réécrive pas l'ancien nom.
La base est renommée par php artisan xv:apply-name-corrections (lancée par deploy.sh avant l'import des feuilles).

  python3 scripts/equipe_france/renommer.py
"""
import csv, glob, html, json, os, re, sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from feuilles import CACHE, MATCHES, ROOT, SPLITS, block, dump, norm

ECARTS = os.path.join(ROOT, 'database/data/sources/noms_ecarts.csv')
# Homonymes de la même époque : le numéro d'international les distingue dans les feuilles
WITH_CAP = {'545', '552'}


def site_slugs():
    """Nom affiché sur equipe-france.fr → slug, pour les joueurs français (un nom = un slug sur le site)."""
    slugs = {}
    for path in glob.glob(os.path.join(CACHE, '*.html')):
        eff = block(open(path, encoding='utf-8').read(), 'class="matcheffectif', ['<h2>Staff', 'id="matchofficiel"'])
        for slug, name in re.findall(r'href="/rugby/([^"]+)">([^<]+)<', eff):
            slugs[norm(html.unescape(name))] = (slug, re.sub(r'\s+', ' ', html.unescape(name)).strip())
    return slugs


def main():
    slugs = site_slugs()
    changes = {}  # (prénom, nom) en base → (prénom, nom, n°)
    splits = []
    for row in csv.DictReader(open(ECARTS, encoding='utf-8')):
        if row['statut'] != 'vérifié':
            continue
        old = (row['base_first_name'], row['base_last_name'])
        new = (row['first_name'], row['last_name'])
        if old == new and row['cap_number'] not in WITH_CAP:
            continue
        cap = int(row['cap_number']) if row['cap_number'] in WITH_CAP else None
        changes[old] = (*new, cap)
        found = slugs.get(norm(f'{old[0]} {old[1]}'))
        if not found:
            print(f"  slug introuvable : {old[0]} {old[1]}")
            continue
        splits.append({'site_name': found[1], 'first_name': new[0], 'last_name': new[1], 'site_slug': found[0], 'cap_number': cap or ''})

    # Exceptions du générateur : lignes par nom conservées, lignes par slug remplacées
    existing = list(csv.DictReader(open(SPLITS, encoding='utf-8')))
    generated = {r['site_slug'] for r in splits}
    kept = [r for r in existing if not r.get('site_slug') or r['site_slug'] not in generated]
    with open(SPLITS, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['site_name', 'first_name', 'last_name', 'site_slug', 'cap_number'])
        w.writeheader()
        w.writerows({**r, 'site_slug': r.get('site_slug', ''), 'cap_number': r.get('cap_number', '')} for r in kept)
        w.writerows(splits)

    edited = 0
    for path in sorted(glob.glob(os.path.join(MATCHES, '*/*.json'))):
        sheet = json.load(open(path, encoding='utf-8'))
        before = json.dumps(sheet, ensure_ascii=False)
        for p in sheet.get('lineups', {}).get('france', []):
            c = changes.get((p['first_name'], p['last_name']))
            if c:
                p['first_name'], p['last_name'] = c[0], c[1]
                if c[2]:
                    p['cap_number'] = c[2]
        for e in sheet.get('events', []):
            c = e['team_side'] == 'france' and changes.get((e.get('player_first_name'), e.get('player_last_name')))
            if c:
                e['player_first_name'], e['player_last_name'] = c[0], c[1]
        for s in sheet.get('substitutions', []):
            if s['team_side'] != 'france':
                continue
            for k in ('off', 'on'):
                c = changes.get((s.get(f'player_{k}_first_name'), s.get(f'player_{k}_last_name')))
                if c:
                    s[f'player_{k}_first_name'], s[f'player_{k}_last_name'] = c[0], c[1]
        if json.dumps(sheet, ensure_ascii=False) != before:
            edited += 1
            with open(path, 'w', encoding='utf-8') as f:
                f.write(dump(sheet))
    print(f'{len(changes)} noms, {len(splits)} exceptions par slug, {edited} feuilles modifiées')


if __name__ == '__main__':
    main()
