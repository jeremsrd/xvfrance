#!/usr/bin/env python3
"""
Complète les feuilles de match JSON d'après les pages finalesrugby.fr en cache (scripts/finalesrugby/fetch.py) :

- « played »: false pour les remplaçants français restés sur le banc (marqués « * » sur le site) ;
  ils figurent sur la feuille mais ne comptent pas comme sélection ;
- remplacements définitifs (D) de la France, avec leur minute quand elle est connue, si le JSON
  n'a encore aucun remplacement français. Les remplacements temporaires (T) sont ignorés : le joueur
  entré compte bien comme sélection, mais les minutes jouées resteraient fausses.

  python3 scripts/finalesrugby/apply.py [--dry] [AAAA-MM-JJ ...]

Le fichier n'est réécrit que si tous les joueurs du site sont retrouvés dans la composition du JSON.
Relancer ensuite xv:validate-match-data.
"""
import os,re,sys,glob,json,html,unicodedata
ROOT=os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CACHE=os.path.join(ROOT,'storage/app/finalesrugby')
dry='--dry' in sys.argv
only={a for a in sys.argv[1:] if not a.startswith('--')}
norm=lambda s: re.sub(r'[^a-z ]','',unicodedata.normalize('NFD',s).encode('ascii','ignore').decode().lower().replace('-',' ')).split()

def text(h):
    h=re.sub(r'<script.*?</script>|<style.*?</style>','',h,flags=re.S)
    return re.sub(r'\s+',' ',html.unescape(re.sub(r'<[^>]+>',' ',h))).strip()

def split_name(t):
    # « DE ROUGEMONT Marc » : nom en capitales, puis prénom
    w=t.split(); i=0
    while i<len(w) and w[i]==w[i].upper() and any(c.isalpha() for c in w[i]): i+=1
    return ' '.join(w[:i]),' '.join(w[i:])

def parse(page):
    h=open(page,encoding='utf-8',errors='ignore').read()
    # date du match : la première après « RENCONTRE N° » (avant, une « Revue FFR du … » peut s'intercaler)
    m=re.search(r'RENCONTRE N.*?(\d\d)/(\d\d)/(\d{4})',text(h))
    if not m: return None
    date=f'{m.group(3)}-{m.group(2)}-{m.group(1)}'
    # composition : un bloc par joueur, du premier « JOUEURS » jusqu'aux arbitres
    a=h.find('JOUEURS'); b=h.find('Arbitres',a)
    players=[]
    bench=False
    for blk in re.split(r'<div[ >]',h[a:b]):
        if 'SUR LE BANC' in blk: bench=True
        t=re.search(r'title="([^"]+)">\s*(\d{1,2})\s',blk)
        if not t: continue
        rest=text(blk.split('/clubs/')[0])
        last,first=split_name(html.unescape(t.group(1)))
        players.append({'num':int(t.group(2)),'last':last,'first':first,'bench':bench,'dnp':'*' in rest})
    # remplacements : « 42e (D) : → SORTANT » puis « 42e (D) : ← ENTRANT »
    subs=[]
    c=h.find('Remplacements',b)
    for blk in re.split(r'<div>',h[c:]):
        k=re.search(r'title="(Définitif|Temporaire)"',blk); n=re.search(r'title="([^"]+)"\s*>',blk.split('</span>',1)[-1])
        if not k or not n: continue
        mi=re.search(r'(\d+)e',text(blk.split('<span',1)[0]))
        subs.append({'kind':k.group(1)[0],'dir':'off' if 'arrow-right' in blk else 'on','minute':int(mi.group(1)) if mi else None,'name':split_name(html.unescape(n.group(1)))})
    return date,players,subs

def find(lineup,last,first):
    L,F=norm(last),norm(first)
    c=[p for p in lineup if norm(p['last_name'])==L]
    if len(c)>1: c=[p for p in c if norm(p['first_name'])[:1]==F[:1]] or c
    if not c:  # nom composé ou abrégé d'un côté : « N'TAMACK-MUYENGA » / « Ntamack »
        c=[p for p in lineup if set(norm(p['last_name']))&set(L) and (not F or norm(p['first_name'])[:1]==F[:1])]
    return c[0] if len(c)==1 else None

def pair(subs,lineup):
    """Remplacements définitifs regroupés par minute ; un joueur à la fois sortant et entrant change de poste."""
    out=[];groups={}
    for s in subs:
        if s['kind']=='D': groups.setdefault(s['minute'],[]).append(s)
    for mi,g in groups.items():
        offs=[find(lineup,*s['name']) for s in g if s['dir']=='off']
        ons=[find(lineup,*s['name']) for s in g if s['dir']=='on']
        if None in offs or None in ons: return None
        both=[p for p in offs if p in ons]
        offs=[p for p in offs if p not in both]; ons=[p for p in ons if p not in both]
        if len(offs)!=len(ons): return None
        for o,i in zip(offs,ons):
            out.append({'team_side':'france','player_off_last_name':o['last_name'],'player_off_first_name':o['first_name'],
                        'player_on_last_name':i['last_name'],'player_on_first_name':i['first_name'],'minute':mi,'is_tactical':True})
    return out

def write(path,d):
    one=lambda o: json.dumps(o,ensure_ascii=False,separators=(', ',': '))
    arr=lambda items,ind: '[]' if not items else '[\n'+',\n'.join(' '*(ind+2)+one(i) for i in items)+'\n'+' '*ind+']'
    parts=[]
    for k,v in d.items():
        if k=='lineups': parts.append('  "lineups": {\n'+',\n'.join(f'    "{sd}": '+arr(v[sd],4) for sd in v)+'\n  }')
        elif isinstance(v,list): parts.append(f'  "{k}": '+arr(v,2))
        else: parts.append(f'  "{k}": '+one(v))
    open(path,'w').write('{\n'+',\n'.join(parts)+'\n}\n')

stats={'pages':0,'sans_json':0,'introuvables':0,'banc':0,'reservistes':0,'banc_absent':0,'subs':0,'subs_ko':0,'fichiers':0}
for page in sorted(glob.glob(os.path.join(CACHE,'*.html'))):
    r=parse(page)
    if not r: continue
    date,players,subs=r
    if only and date not in only: continue
    stats['pages']+=1
    files=glob.glob(os.path.join(ROOT,f'database/data/matches/{date[:4]}/{date}-*.json'))
    if not files:  # date décalée d'une source à l'autre : la plus proche à 3 jours près
        import datetime
        d0=datetime.date.fromisoformat(date)
        for k in (1,-1,2,-2,3,-3):
            dk=(d0+datetime.timedelta(days=k)).isoformat()
            files=glob.glob(os.path.join(ROOT,f'database/data/matches/{dk[:4]}/{dk}-*.json'))
            if files: break
    if len(files)!=1: stats['sans_json']+=1; print(f'{date} {os.path.basename(page)} : {len(files)} JSON'); continue
    path=files[0]; d=json.load(open(path))
    if not d.get('lineups',{}).get('france'): stats['sans_json']+=1; continue  # match sans composition
    lineup=d['lineups']['france']
    found=[(p,find(lineup,p['last'],p['first'])) for p in players]
    missing=[f"{p['last']} {p['first']}" for p,e in found if not e and not p['bench']]
    # deux sources, deux orthographes : on vérifie sur les titulaires que c'est bien le même match
    if len(missing)*3>sum(not p['bench'] for p in players):
        stats['introuvables']+=1; print(f'{os.path.basename(path)} : match différent ? introuvables {missing}'); continue
    # remplaçant du JSON que le site ne cite pas : on ne sait pas s'il est entré
    seen=[id(e) for _,e in found if e]
    absent=[e for e in lineup if not e['is_starter'] and id(e) not in seen]
    changed=False
    # Le site liste parfois seulement les remplaçants entrés en jeu : un remplaçant du JSON qu'il ne cite pas
    # n'est pas entré, sauf s'il a marqué ou figure déjà dans nos remplacements (orthographe différente).
    if absent and any(p['bench'] for p in players):
        busy={x.get('player_last_name') for x in d['events']}|{x['player_on_last_name'] for x in d['substitutions']}
        for e in absent:
            if e['last_name'] in busy or e.get('played') is False: continue
            items=list(e.items()); e.clear()
            for k,v in items:
                e[k]=v
                if k=='is_captain': e['played']=False
            if 'played' not in e: e['played']=False
            stats['banc_absent']+=1; changed=True
            print(f"{os.path.basename(path)} : remplaçant absent du site, non entré : {e['last_name']}")
    for p,e in found:
        if not (p['bench'] and p['dnp']): continue
        if not e: stats['reservistes']+=1; continue  # réserviste absent du JSON (avant 1969 surtout) : sans effet
        if e.get('played') is not False and not e['is_starter']:
            # « played » rangé après « is_captain »
            items=list(e.items()); e.clear()
            for k,v in items:
                e[k]=v
                if k=='is_captain': e['played']=False
            if 'played' not in e: e['played']=False
            stats['banc']+=1; changed=True
    if subs and not any(s['team_side']=='france' for s in d['substitutions']):
        new=pair(subs,lineup)
        if new is None: stats['subs_ko']+=1; print(f'{os.path.basename(path)} : remplacements non appariés')
        elif new:
            new=[s for s in new if not any(p.get('played') is False for p in lineup if p['last_name'] in (s['player_on_last_name'],s['player_off_last_name']))]
            d['substitutions']=sorted(d['substitutions']+new,key=lambda s:(s['minute'] if s['minute'] is not None else 999))
            stats['subs']+=len(new); changed=True
    if changed:
        stats['fichiers']+=1
        if not dry: write(path,d)
print(stats)
