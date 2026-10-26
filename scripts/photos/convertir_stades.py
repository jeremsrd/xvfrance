#!/usr/bin/env python3
"""
Télécharge les photos de stades retenues dans database/data/sources/venue_photos.csv (Wikimedia Commons, relevé par
scripts/wikipedia/stades.py) et les convertit en WebP de 1 200 px de large au plus dans public/images/stades/{slug}.webp, plus une vignette de 240 px
({slug}-vignette.webp) pour la liste des stades.
Les téléchargements sont gardés en cache (storage/app/commons) ; une image déjà convertie n'est pas refaite.
php artisan xv:import-venue-photos les rattache ensuite aux stades.

  python3 scripts/photos/convertir_stades.py
"""
import csv, os, subprocess, time, urllib.parse, urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CSV = os.path.join(ROOT, 'database/data/sources/venue_photos.csv')
CACHE = os.path.join(ROOT, 'storage/app/commons')
OUT = os.path.join(ROOT, 'public/images/stades')
WIDTH = 1200
THUMB = 240
UA = 'xvfrance.fr/1.0 (https://xvfrance.fr)'


def main():
    os.makedirs(CACHE, exist_ok=True)
    os.makedirs(OUT, exist_ok=True)
    rows = list(csv.DictReader(open(CSV, encoding='utf-8')))
    for r in rows:
        out = os.path.join(OUT, r['venue_slug'] + '.webp')
        thumb = os.path.join(OUT, r['venue_slug'] + '-vignette.webp')
        if os.path.exists(out) and os.path.exists(thumb):
            continue
        raw = os.path.join(CACHE, r['venue_slug'] + os.path.splitext(r['commons_file'])[1].lower())
        if not os.path.exists(raw):
            url = ('https://commons.wikimedia.org/wiki/Special:FilePath/' + urllib.parse.quote(r['commons_file'])
                   + f'?width={WIDTH}')
            req = urllib.request.Request(url, headers={'User-Agent': UA})
            open(raw, 'wb').write(urllib.request.urlopen(req, timeout=120).read())
            time.sleep(1)
        width = int(subprocess.run(['sips', '-g', 'pixelWidth', raw], capture_output=True, text=True).stdout.split()[-1])
        resize = ['-resize', str(WIDTH), '0'] if width > WIDTH else []  # jamais agrandie
        src = raw
        if raw.endswith(('.tif', '.tiff', '.gif')):  # formats que cwebp ne lit pas
            src = raw + '.png'
            subprocess.run(['sips', '-s', 'format', 'png', raw, '--out', src], capture_output=True, check=True)
        subprocess.run(['cwebp', '-quiet', '-q', '78', *resize, src, '-o', out], check=True)
        subprocess.run(['cwebp', '-quiet', '-q', '75', '-resize', str(THUMB), '0', src, '-o', thumb], check=True)
    print(f'{len(rows)} photos de stades dans public/images/stades')


if __name__ == '__main__':
    main()
