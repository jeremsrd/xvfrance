"""
Fonctions communes aux scripts finalesrugby.fr : lecture des pages en cache, rapprochement avec les feuilles
de match JSON et les joueurs de leurs compositions, écriture des JSON au format du projet.
"""
import os,re,glob,json,html,datetime,unicodedata
ROOT=os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CACHE=os.path.join(ROOT,'storage/app/finalesrugby')
norm=lambda s: re.sub(r'[^a-z ]','',unicodedata.normalize('NFD',s).encode('ascii','ignore').decode().lower().replace('-',' ')).split()

SCORING={'Essais':'essai','Transformations':'transformation','Pénalités':'penalite','Drops':'drop',
         'Cartons Rouges':'carton_rouge','Cartons Jaunes':'carton_jaune'}

def text(h):
    h=re.sub(r'<script.*?</script>|<style.*?</style>','',h,flags=re.S)
    return re.sub(r'\s+',' ',html.unescape(re.sub(r'<[^>]+>',' ',h))).strip()

def split_name(t):
    # « DE ROUGEMONT Marc » : nom en capitales, puis prénom
    w=t.split(); i=0
    while i<len(w) and w[i]==w[i].upper() and any(c.isalpha() for c in w[i]): i+=1
    return ' '.join(w[:i]),' '.join(w[i:])

def minutes(s):
    """« 31e-40e+3-84e » → [31, 43, 84] ; None si une valeur n'est pas une minute (« 2e mi-temps », « 1er essai »)."""
    out=[]
    for tok in s.split('-'):
        m=re.fullmatch(r'\s*(\d+)(?:e|re|er)?(?:\+(\d+))?\s*',tok)
        if not m: return None
        out.append(int(m.group(1))+int(m.group(2) or 0))
    return out

def parse(page):
    """(date, composition française, remplacements, points et cartons) d'une page de match, ou None."""
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
    # points et cartons : un bloc par type, « 3 - VENDITTI David (31e-61e-84e) »
    scoring={}
    for m in re.finditer(r'card-header">\s*([^<]+?)\s*<(.*?)(?=<div class="card-header|$)',h[b:c],re.S):
        kind=SCORING.get(m.group(1).strip())
        if not kind: continue
        for blk in re.split(r'<div>',m.group(2)):
            n=re.match(r'(\d+) - (.*?)(?: \(([^()]*)\))?$',text(blk))
            if not n: continue
            name=None if 'ESSAI DE PENALITE' in n.group(2) else split_name(n.group(2))
            scoring.setdefault(kind,[]).append({'count':int(n.group(1)),'name':name,'minutes':minutes(n.group(3) or '')})
    return date,players,subs,scoring

def find(lineup,last,first):
    L,F=norm(last),norm(first)
    c=[p for p in lineup if norm(p['last_name'])==L]
    if len(c)>1: c=[p for p in c if norm(p['first_name'])[:1]==F[:1]] or c
    if not c:  # nom composé ou abrégé d'un côté : « N'TAMACK-MUYENGA » / « Ntamack »
        c=[p for p in lineup if set(norm(p['last_name']))&set(L) and (not F or norm(p['first_name'])[:1]==F[:1])]
    return c[0] if len(c)==1 else None

def match_file(date):
    """Feuilles JSON du match, à 3 jours près (date décalée d'une source à l'autre)."""
    d0=datetime.date.fromisoformat(date)
    for k in (0,1,-1,2,-2,3,-3):
        dk=(d0+datetime.timedelta(days=k)).isoformat()
        files=glob.glob(os.path.join(ROOT,f'database/data/matches/{dk[:4]}/{dk}-*.json'))
        if files: return files
    return []

def same_match(players,lineup):
    """Titulaires du site introuvables dans le JSON s'ils sont plus d'un tiers (autre match), sinon None.
    Deux sources, deux orthographes : quelques noms peuvent manquer sans que ce soit un autre match."""
    missing=[f"{p['last']} {p['first']}" for p in players if not p['bench'] and not find(lineup,p['last'],p['first'])]
    return missing if len(missing)*3>sum(not p['bench'] for p in players) else None

def write(path,d):
    one=lambda o: json.dumps(o,ensure_ascii=False,separators=(', ',': '))
    arr=lambda items,ind: '[]' if not items else '[\n'+',\n'.join(' '*(ind+2)+one(i) for i in items)+'\n'+' '*ind+']'
    parts=[]
    for k,v in d.items():
        if k=='lineups': parts.append('  "lineups": {\n'+',\n'.join(f'    "{sd}": '+arr(v[sd],4) for sd in v)+'\n  }')
        elif isinstance(v,list): parts.append(f'  "{k}": '+arr(v,2))
        else: parts.append(f'  "{k}": '+one(v))
    open(path,'w').write('{\n'+',\n'.join(parts)+'\n}\n')
