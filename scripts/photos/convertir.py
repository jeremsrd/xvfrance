#!/usr/bin/env python3
"""
Convertit les portraits de l'ancien projet « This Is Rugby » (joueurs d'avant 1930, domaine public) en WebP 400 px
dans public/images/joueurs/, d'après database/data/sources/player_photos.csv (colonne source → colonne file).
La colonne file est remplie si elle est vide. php artisan xv:import-player-photos renseigne ensuite players.photo_path.

  python3 scripts/photos/convertir.py /chemin/vers/ThisIsRugby/Joueurs
"""
import csv, os, re, subprocess, sys, unicodedata

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CSV = os.path.join(ROOT, 'database/data/sources/player_photos.csv')
OUT = os.path.join(ROOT, 'public/images/joueurs')


def slug(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')


def main():
    src = sys.argv[1]
    rows = list(csv.DictReader(open(CSV, encoding='utf-8')))
    os.makedirs(OUT, exist_ok=True)
    for r in rows:
        r['file'] = r.get('file') or slug(f"{r['country_code']} {r['first_name']} {r['last_name']}") + '.webp'
        subprocess.run(['cwebp', '-quiet', '-q', '80', '-resize', '400', '0', os.path.join(src, r['source']),
                        '-o', os.path.join(OUT, r['file'])], check=True)
    with open(CSV, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['source', 'country_code', 'first_name', 'last_name', 'file'])
        w.writeheader()
        w.writerows(rows)
    print(f'{len(rows)} portraits écrits dans public/images/joueurs')


if __name__ == '__main__':
    main()
