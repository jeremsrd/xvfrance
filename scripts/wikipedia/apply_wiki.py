#!/usr/bin/env python3
"""
Corrige les événements de score (essais, transformations, pénalités, drops, essais de
pénalité) d'une feuille de match JSON d'après l'encadré de match de Wikipédia (anglais).

  python3 scripts/wikipedia/apply_wiki.py database/data/matches/2025/2025-03-15-SCO.json \
      "2025 Six Nations Championship" "15 March" --dry

- Les cartons, compositions et remplacements ne sont pas touchés.
- Un événement existant est conservé s'il correspond (joueur, type) à 3 minutes près.
- Le fichier n'est réécrit que si la somme des points égale le score et que chaque
  marqueur est retrouvé dans la composition ; sinon le script affiche le problème.
- Sans --dry, le fichier est réécrit : relancer ensuite xv:validate-match-data.
"""
import os,sys,re,json,unicodedata,subprocess
S=os.path.dirname(os.path.abspath(__file__)); dry='--dry' in sys.argv
args=[x for x in sys.argv[1:] if not x.startswith('--')]
if len(args)!=3:
    sys.exit('Usage : apply_wiki.py <fichier.json> "<page Wikipédia>" "<date de l\'encadré>" [--dry]')
jobs=[(args[0],args[1],args[2])]
norm=lambda s: unicodedata.normalize('NFD',s).encode('ascii','ignore').decode().lower().replace('-',' ').strip()
cache={}
def lev(a,b):
    prev=list(range(len(b)+1))
    for i,ca in enumerate(a,1):
        cur=[i]
        for j,cb in enumerate(b,1): cur.append(min(prev[j]+1,cur[j-1]+1,prev[j-1]+(ca!=cb)))
        prev=cur
    return prev[-1]
def boxes(page,date):
    if page not in cache:
        cache[page]=subprocess.run(['python3',f'{S}/rugbybox.py',page],capture_output=True,text=True).stdout
    out=[];cur=None
    for line in cache[page].splitlines():
        if line.startswith('## '): cur=[line];out.append(cur)
        elif cur is not None: cur.append(line)
    return [b for b in out if re.search(r'(?<!\d)'+re.escape(date),b[0])]
def minutes(s):
    return [int(a)+int(b or 0) for a,b in re.findall(r"(\d+)(?:\+(\d+))?'",s)]
def parse(field,typ):
    # « Nom (2) 12' c, 54' m Autre 30' c » → [(nom, minute)]
    ev=[]
    s=re.sub(r'\(\d+/\d+\)','',field)
    s=re.sub(r'<!--.*?-->','',s)
    for m in re.finditer(r"([A-Za-zÀ-ÿ'.\- ]+?)\s*(?:\(\d+\))?\s*((?:\d+(?:\+\d+)?'\s*[cm]?\s*,?\s*)+)",s):
        name=m.group(1).strip()
        for mi in minutes(m.group(2)): ev.append((name,mi,typ))
    return ev
types={'try':'essai','con':'transformation','pen':'penalite','drop':'drop'}
for f,page,date in jobs:
    path=f; d=json.load(open(path))
    bx=boxes(page,date)
    # le bon encadré : celui dont les marqueurs sont dans nos compositions
    lineup={side:{norm(p['last_name']):p for p in d['lineups'][side]} for side in ('france','adversaire')}
    best=None
    for b in bx:
        fields={l.split(':')[0].strip():l.split(':',1)[1] for l in b[1:]}
        hits=sum(1 for k in ('try1','try2') for n,_,_ in parse(fields.get(k,''),'essai') if norm(n.split('. ')[-1]) in lineup['france'])
        if best is None or hits>best[0]: best=(hits,fields,b[0])
    fields=best[1]
    t1=[n for n,_,_ in parse(fields.get('try1',''),'essai')]
    fr_is_1=sum(norm(n.split('. ')[-1]) in lineup['france'] for n in t1) >= max(1,len(t1)/2) if t1 else 'FRA' in best[2].split('–')[0]
    new=[];problems=[]
    for num,side in ((1,'france' if fr_is_1 else 'adversaire'),(2,'adversaire' if fr_is_1 else 'france')):
        for k,typ in types.items():
            for name,mi,_ in parse(fields.get(f'{k}{num}',''),typ):
                if norm(name)=='penalty try':
                    new.append({'team_side':side,'type':'essai_penalite','player_last_name':None,'player_first_name':None,'minute':mi}); continue
                initial=None
                if re.match(r'^[A-Z]\. ',name): initial,name=name[0],name[3:]
                cands=[p for p in d['lineups'][side] if norm(p['last_name'])==norm(name) and (not initial or p['first_name'].startswith(initial))]
                if not cands:
                    cands=[p for p in d['lineups'][side] if lev(norm(p['last_name']),norm(name))<=2]
                if len(cands)!=1:
                    problems.append(f'{side} {typ} {name} {mi}: {len(cands)} candidats'); continue
                p=cands[0]
                new.append({'team_side':side,'type':typ,'player_last_name':p['last_name'],'player_first_name':p['first_name'],'minute':mi})
    cards=[e for e in d['events'] if e['type'] in ('carton_jaune','carton_rouge')]
    pool=[e for e in d['events'] if e['type'] not in ('carton_jaune','carton_rouge')]
    merged=[]
    for w in new:
        hit=next((e for e in pool if e['team_side']==w['team_side'] and e['type']==w['type'] and e.get('player_last_name')==w['player_last_name']
                  and (e['minute'] is None or w['minute'] is None or abs(e['minute']-w['minute'])<=3)),None)
        if hit: pool.remove(hit); merged.append(hit)
        else: merged.append(w)
    new=merged
    pts={'essai':5,'transformation':2,'penalite':3,'drop':3,'essai_penalite':7}
    tot={s:sum(pts[e['type']] for e in new if e['team_side']==s) for s in ('france','adversaire')}
    ok=tot['france']==d['france_score'] and tot['adversaire']==d['opponent_score']
    old={(e['team_side'],e['type'],e.get('player_last_name'),e['minute']) for e in d['events'] if e['type'] in pts}
    nw={(e['team_side'],e['type'],e['player_last_name'],e['minute']) for e in new}
    print(f"== {os.path.basename(f)} : wiki {tot['france']}-{tot['adversaire']} / fichier {d['france_score']}-{d['opponent_score']} {'OK' if ok else 'ÉCART'} | retirés {sorted(old-nw, key=str)} | ajoutés {sorted(nw-old, key=str)}")
    for p in problems: print('   !!',p)
    if ok and not problems and not dry:
        d['events']=sorted(new+cards,key=lambda e:(e['minute'] if e['minute'] is not None else 999))
        one=lambda o: json.dumps(o,ensure_ascii=False,separators=(', ',': '))
        arr=lambda items,ind: '[]' if not items else '[\n'+',\n'.join(' '*(ind+2)+one(i) for i in items)+'\n'+' '*ind+']'
        parts=[]
        for k,v in d.items():
            if k=='lineups': parts.append('  "lineups": {\n'+',\n'.join(f'    "{sd}": '+arr(v[sd],4) for sd in v)+'\n  }')
            elif isinstance(v,list): parts.append(f'  "{k}": '+arr(v,2))
            else: parts.append(f'  "{k}": '+one(v))
        open(path,'w').write('{\n'+',\n'.join(parts)+'\n}\n')
