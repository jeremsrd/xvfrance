#!/usr/bin/env python3
"""
Relève une photo libre pour chaque stade de la base : l'élément Wikidata du stade (lieu sportif à moins de 2 km de nos
coordonnées, 15 km si elles ne sont précises qu'à la ville, nom le plus proche), son image principale (P18), puis
l'auteur et la licence du fichier sur Wikimedia Commons.

Écrit database/data/sources/venue_photos.csv (stades avec une image ; images vérifiées une à une le 26/10/2026,
exclusions dans OVERRIDES et EXCLUDED). scripts/photos/convertir_stades.py télécharge ensuite les images.

  python3 scripts/wikipedia/stades.py
"""
import csv, difflib, hashlib, html, json, math, os, re, sqlite3, time, unicodedata, urllib.parse, urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CACHE = os.path.join(ROOT, 'storage/app/wikipedia')
OUT = os.path.join(ROOT, 'database/data/sources/venue_photos.csv')
COORDS = os.path.join(ROOT, 'database/data/sources/venue_coordinates.csv')
UA = 'xvfrance.fr/1.0 (https://xvfrance.fr)'

# Rapprochements faux corrigés à la main (26/10/2026) : nom du stade en base → élément Wikidata, ou None (pas de bon
# candidat). Les stades renommés trouvés par leur nouveau nom (Waikato = FMG Stadium, Etihad = Marvel…) sont justes.
OVERRIDES = {
    'Wembley Stadium': 'Q43279',            # l'ancien Wembley (1923-2000), pas Wembley Arena
    'London Stadium': 'Q172190',
    'WIN Stadium': 'Q8030374',
    'StubHub Center': 'Q200684',
    'Groupama Stadium': 'Q8507',
    'Campo de Vallejo': 'Q4890854',
    'Stade Moulias': 'Q3495635',
    'Ciudad Universitaria': None,
    'Estadio Ferro Carril Oeste': None,
    'Gimnasia y Esgrima de Buenos Aires': None,
    'Stade Français': None,
    'Stade Pierre de Coubertin': None,
    'Stadionul 23 August': None,
    'Stadionul Republicii': None,
    'Valle Hermoso': None,
    'York Stadium': None,
}

# Lieu sportif (Q1076486) et ses sous-classes : stade, terrain de rugby, vélodrome, hippodrome…
SPARQL = """
SELECT ?item ?itemLabel ?image ?dist WHERE {
  SERVICE wikibase:around { ?item wdt:P625 ?loc . bd:serviceParam wikibase:center "Point(%s %s)"^^geo:wktLiteral .
                            bd:serviceParam wikibase:radius "%s" . bd:serviceParam wikibase:distance ?dist . }
  ?item wdt:P31/wdt:P279* wd:Q1076486 .
  OPTIONAL { ?item wdt:P18 ?image . }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en" . }
} ORDER BY ?dist LIMIT 30
"""


# Images écartées après vérification : Ballymore (des joueurs, pas le stade), Giulești (image du stade Regie)
EXCLUDED = {'Ballymore', 'Stadionul Giulesti'}


def clean_author(author):
    """« Zyonig (talk)Zyonig » → « Zyonig » ; mentions automatiques de Commons retirées."""
    a = re.sub(r'\s*\(talk\)', ' ', author)
    a = re.sub(r'^User:', '', a)
    a = re.sub(r"^No machine-readable author provided\. (.+?) assumed.*$", r'\1', a)
    a = re.sub(r"^This image's author is (.+?) Attribution.*$", r'\1', a)
    a = re.sub(r'^(Photo taken by|Originally uploaded by|Photograph by) ', '', a)
    a = re.sub(r'^The original uploader was (.+?) at (\w+) Wikipedia\.?$', r'\1', a)
    a = re.sub(r';? original uploader was .*$', '', a)
    a = re.sub(r'\s+', ' ', a).strip()
    half = len(a) // 2
    if len(a) % 2 == 0 and a[:half] == a[half:]:  # « Unknown authorUnknown author »
        a = a[:half]
    words = a.split(' ')
    if len(words) % 2 == 0 and words[:len(words) // 2] == words[len(words) // 2:]:  # « Zyonig Zyonig »
        a = ' '.join(words[:len(words) // 2])
    return {'Unknown author': '', 'Own work': ''}.get(a, a)


def norm(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', ' ', s).strip()


def get(url, params):
    query = urllib.parse.urlencode(params)
    path = os.path.join(CACHE, hashlib.md5((url + query).encode()).hexdigest() + '.json')
    if os.path.exists(path):
        return json.load(open(path))
    req = urllib.request.Request(url + '?' + query, headers={'User-Agent': UA, 'Accept': 'application/sparql-results+json'})
    data = json.load(urllib.request.urlopen(req, timeout=90))
    time.sleep(1)  # une requête par seconde
    os.makedirs(CACHE, exist_ok=True)
    json.dump(data, open(path, 'w'))
    return data


def nearby(lat, lon, radius):
    rows = get('https://query.wikidata.org/sparql', {'query': SPARQL % (lon, lat, radius), 'format': 'json'})
    out = []
    for b in rows['results']['bindings']:
        out.append({'qid': b['item']['value'].rsplit('/', 1)[1], 'label': b.get('itemLabel', {}).get('value', ''),
                    'image': urllib.parse.unquote(b['image']['value'].rsplit('/', 1)[1]) if 'image' in b else '',
                    'dist': float(b['dist']['value'])})
    return out


def entity(qid):
    data = get('https://www.wikidata.org/w/api.php', {'action': 'wbgetentities', 'ids': qid, 'props': 'labels|claims',
                                                      'languages': 'fr|en', 'format': 'json'})
    e = data['entities'][qid]
    p18 = e['claims'].get('P18', [])
    return {'qid': qid, 'label': (e['labels'].get('fr') or e['labels'].get('en'))['value'], 'dist': 0, 'score': 1,
            'image': p18[0]['mainsnak']['datavalue']['value'].replace(' ', '_') if p18 else ''}


def commons(file):
    data = get('https://commons.wikimedia.org/w/api.php', {
        'action': 'query', 'titles': 'File:' + file, 'prop': 'imageinfo', 'iiprop': 'extmetadata|url|size',
        'format': 'json', 'formatversion': 2})
    info = data['query']['pages'][0]['imageinfo'][0]
    meta = info['extmetadata']
    text = lambda k: html.unescape(re.sub(r'<[^>]+>', '', meta.get(k, {}).get('value', ''))).strip()
    return {'author': re.sub(r'\s+', ' ', text('Artist')), 'license': text('LicenseShortName'),
            'license_url': text('LicenseUrl'), 'date': text('DateTimeOriginal')[:10],
            'page': info['descriptionurl'], 'width': info['width'], 'height': info['height']}


def main():
    precision = {(r['name'], r['city']): r['precision'] for r in csv.DictReader(open(COORDS, encoding='utf-8'))}
    db = sqlite3.connect(os.path.join(ROOT, 'database/database.sqlite'))
    venues = db.execute('select slug, name, city, latitude, longitude from venues where latitude is not null order by name').fetchall()
    rows = []
    for slug, name, city, lat, lon in venues:
        city_only = precision.get((name, city)) == 'ville'
        found = nearby(lat, lon, 15 if city_only else 2)
        # Nom le plus proche ; à égalité, le plus près
        for f in found:
            f['score'] = difflib.SequenceMatcher(None, norm(name), norm(f['label'])).ratio()
        found.sort(key=lambda f: (-round(f['score'], 1), f['dist']))
        best = next((f for f in found if f['score'] >= (0.6 if city_only else 0.35)), None)
        if name in OVERRIDES:
            best = OVERRIDES[name] and entity(OVERRIDES[name])
        row = {'venue_slug': slug, 'venue_name': name, 'city': city, 'wikidata': '', 'label': '', 'distance_km': '',
               'score': '', 'commons_file': '', 'author': '', 'license': '', 'license_url': '', 'date': '', 'page': ''}
        if best:
            row.update(wikidata=best['qid'], label=best['label'], distance_km=round(best['dist'], 2),
                       score=round(best['score'], 2), commons_file=best['image'])
            if best['image']:
                c = commons(best['image'])
                row.update(author=clean_author(c['author']), license=c['license'], license_url=c['license_url'], date=c['date'], page=c['page'])
        rows.append(row)
        print(f"{name[:40]:40} → {row['label'][:35]:35} {row['license'] or ('sans image' if best else 'introuvable')}")
    kept = [r for r in rows if r['commons_file'] and r['venue_name'] not in EXCLUDED]
    with open(OUT, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['venue_slug', 'venue_name', 'wikidata', 'commons_file', 'author', 'license',
                                          'license_url', 'page'], extrasaction='ignore')
        w.writeheader()
        w.writerows(kept)
    with_image = sum(1 for r in rows if r['commons_file'])
    print(f'{len(rows)} stades, {sum(1 for r in rows if r["wikidata"])} trouvés sur Wikidata, {with_image} avec une image, '
          f'{len(kept)} retenues')


if __name__ == '__main__':
    main()
