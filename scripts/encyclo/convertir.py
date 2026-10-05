#!/usr/bin/env python3
"""
Convertit la liste chronologique des internationaux (« Encyclo 1020 n.doc », arrêtée au 31/12/2016, saisie vers 2015
pour un ancien projet) en CSV : database/data/sources/internationaux_encyclo.csv.

  textutil -convert txt "Encyclo 1020 n.doc"   (macOS)
  python3 scripts/encyclo/convertir.py "Encyclo 1020 n.txt" "Encyclo 1020 a.txt"

Le second fichier (index alphabétique « Nom Prénom, numéro ») sert de contrôle : chaque numéro doit y porter le même nom.
"""
import csv, os, re, sys, unicodedata

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT = os.path.join(ROOT, 'database/data/sources/internationaux_encyclo.csv')
# Erreurs du document, corrigées d'après l'index alphabétique
FIXED_NUMBERS = {('PUJOLLE', 571): 771}
DATE = re.compile(r'(\d{1,2})\.(\d{1,2})\.(\d{4})$')


def norm(s):
    return re.sub(r'[^a-z]', '', unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower())


def is_surname(line):
    letters = [c for c in line if c.isalpha()]
    return letters and sum(c.isupper() for c in letters) / len(letters) > 0.6


def parse(path):
    lines = [l.strip() for l in open(path, encoding='utf-8') if l.strip()]
    lines = lines[lines.index('Clubs alors fréquentés') + 1:]
    lines = [l for l in lines if not l.startswith('Liste arrêtée')]
    lines = [l[:-1] if re.fullmatch(r'\d{1,3}\.', l) else l for l in lines]  # « 1. » sélection
    starts = [i for i in range(len(lines) - 1) if re.fullmatch(r'\d{1,4}', lines[i]) and is_surname(lines[i + 1])]
    rows = []
    for a, b in zip(starts, starts[1:] + [len(lines)]):
        block = lines[a + 1:b]
        last, rest = block[0], block[1:]
        first = rest.pop(0) if rest and not DATE.match(rest[0]) and not re.fullmatch(r'\d{1,3}', rest[0]) else ''
        birth = ''
        if rest and DATE.match(rest[0]):
            d, m, y = DATE.match(rest.pop(0)).groups()
            birth = f'{y}-{int(m):02d}-{int(d):02d}'
        k = next((j for j, x in enumerate(rest) if re.fullmatch(r'\d{1,3}', x)), None)
        positions = rest[:k] if k is not None else rest
        rows.append({
            'cap_number': FIXED_NUMBERS.get((last, int(lines[a])), int(lines[a])), 'last_name': last, 'first_name': first, 'birth_date': birth,
            'positions': ', '.join(positions),
            'caps': rest[k] if k is not None else '',
            'years': rest[k + 1] if k is not None and k + 1 < len(rest) else '',
            'clubs': ', '.join(rest[k + 2:]) if k is not None else '',
        })
    return rows


def index(path):
    """Index alphabétique : « Abadie André, 545 » → {545: 'abadieandre'}"""
    out = {}
    for line in open(path, encoding='utf-8'):
        m = re.match(r'(.+?),?\s+(\d{1,4})\s*$', line.strip())
        if m:
            out.setdefault(int(m.group(2)), []).append(m.group(1))
    return out


def main():
    rows = parse(sys.argv[1])
    alpha = index(sys.argv[2]) if len(sys.argv) > 2 else {}
    numbers = [r['cap_number'] for r in rows]
    print(f'{len(rows)} internationaux, du n° {min(numbers)} au n° {max(numbers)}')
    dup = sorted({n for n in numbers if numbers.count(n) > 1})
    if dup:
        print('numéros en double :', dup)
    missing = sorted(set(range(1, max(numbers) + 1)) - set(numbers))
    print(f'{len(missing)} numéros absents :', ', '.join(f"{n} ({' / '.join(alpha.get(n, ['?']))})" for n in missing))
    # Absents de la liste mais nommés dans l'index : numéro et nom seulement (« Triviaux Roger » → TRIVIAUX / Roger)
    for n in missing:
        if len(alpha.get(n, [])) == 1:
            last, _, first = alpha[n][0].partition(' ')
            rows.append({'cap_number': n, 'last_name': last.upper(), 'first_name': first, 'birth_date': '',
                         'positions': '', 'caps': '', 'years': '', 'clubs': ''})
    rows.sort(key=lambda r: r['cap_number'])
    for r in rows:
        names = alpha.get(r['cap_number'])
        if names and not any(norm(n) == norm(r['last_name'] + r['first_name']) for n in names):
            print(f"  index ≠ liste : n° {r['cap_number']} {r['last_name']} {r['first_name']} / index : {' / '.join(names)}")
    for r in rows:
        if r['positions'] and not r['first_name'] or r['positions'] and not r['caps'] or r['positions'] and not re.fullmatch(r'\d{4}(-\d{2,4})?', r['years']):
            print(f"  à vérifier : {r}")
    with open(OUT, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=list(rows[0]))
        w.writeheader()
        w.writerows(rows)
    print('écrit :', os.path.relpath(OUT, ROOT))


if __name__ == '__main__':
    main()
