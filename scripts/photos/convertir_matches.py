#!/usr/bin/env python3
"""
Convertit les photos d'équipe et de match de l'ancien projet « This Is Rugby » (1906-1920, domaine public, surtout
des négatifs de l'Agence Rol sur Gallica) en WebP de 1 200 px de large au plus dans public/images/matches/, d'après
database/data/sources/match_photos.csv (colonne source → colonne file). La colonne file est remplie si elle est vide.
php artisan xv:import-match-photos les rattache ensuite aux matches.

  python3 scripts/photos/convertir_matches.py /chemin/vers/ThisIsRugby
"""
import csv, os, subprocess, sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CSV = os.path.join(ROOT, 'database/data/sources/match_photos.csv')
OUT = os.path.join(ROOT, 'public/images/matches')
WIDTH = 1200


def main():
    src = sys.argv[1]
    rows = list(csv.DictReader(open(CSV, encoding='utf-8')))
    os.makedirs(OUT, exist_ok=True)
    count = {}
    for r in rows:
        if not r['file']:
            key = f"{r['match_date']}-{r['opponent_code'].lower()}-{r['team_side'] or 'match'}"
            count[key] = count.get(key, 0) + 1
            r['file'] = key + (f'-{count[key]}' if count[key] > 1 else '') + '.webp'
        path = os.path.join(src, r['source'])
        width = int(subprocess.run(['sips', '-g', 'pixelWidth', path], capture_output=True, text=True).stdout.split()[-1])
        resize = ['-resize', str(WIDTH), '0'] if width > WIDTH else []  # jamais agrandie
        subprocess.run(['cwebp', '-quiet', '-q', '78', *resize, path, '-o', os.path.join(OUT, r['file'])], check=True)
    with open(CSV, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['source', 'match_date', 'opponent_code', 'team_side', 'caption', 'file'])
        w.writeheader()
        w.writerows(rows)
    print(f'{len(rows)} photos écrites dans public/images/matches')


if __name__ == '__main__':
    main()
