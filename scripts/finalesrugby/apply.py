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
import os,sys,glob,json
sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
from common import CACHE,parse,find,write,match_file,same_match
dry='--dry' in sys.argv
only={a for a in sys.argv[1:] if not a.startswith('--')}

def pair(subs,lineup):
    """Remplacements définitifs regroupés par minute, dans l'ordre du site ; renvoie (remplacements, problèmes).
    Un joueur à la fois sortant et entrant change de poste ; la sortie d'un remplaçant entré temporairement
    marque le retour du titulaire. Le talonneur sortant est apparié d'abord au n° 16. Si les sortants et les entrants ne s'équilibrent pas (joueur oublié par
    le site), seul un sortant immédiatement suivi d'un entrant est apparié. Une minute dont un joueur est
    introuvable est laissée de côté."""
    out=[];probs=[];groups={}
    temp={id(p) for s in subs if s['kind']=='T' and s['dir']=='on' for p in [find(lineup,*s['name'])] if p and not p['is_starter']}
    for s in subs:
        if s['kind']=='D': groups.setdefault(s['minute'],[]).append(s)
    for mi,g in groups.items():
        seq=[];seen=set()
        for s in g:
            p=find(lineup,*s['name'])
            if p is None: probs.append(f"{mi}' introuvable : {' '.join(s['name'][::-1])}"); seq=None; break
            if (s['dir'],id(p)) not in seen: seen.add((s['dir'],id(p))); seq.append((s['dir'],p))
        if seq is None: continue
        both={id(p) for d,p in seq if d=='off'}&{id(p) for d,p in seq if d=='on'}
        ons={id(p) for d,p in seq if d=='on'}
        seq=[(d,p) for d,p in seq if id(p) not in both and not (d=='off' and id(p) in temp and id(p) not in ons)]
        hook=[(o,i) for d,o in seq if d=='off' and o.get('position')=='talonneur' for e,i in seq if e=='on' and i['jersey']==16]
        if hook: seq=[(d,p) for d,p in seq if p not in hook[0]]
        offs=[p for d,p in seq if d=='off']; ins=[p for d,p in seq if d=='on']
        if len(offs)==len(ins): pairs=hook[:1]+list(zip(offs,ins))
        else:
            pairs=hook[:1]+[(seq[i][1],seq[i+1][1]) for i in range(len(seq)-1) if seq[i][0]=='off' and seq[i+1][0]=='on']
            used={id(p) for o,i in pairs for p in (o,i)}
            left=[f"{'sortie' if d=='off' else 'entrée'} {p['last_name']}" for d,p in seq if id(p) not in used]
            probs.append(f"{mi}' sans vis-à-vis : {', '.join(left)}")
        for o,i in pairs:
            out.append({'team_side':'france','player_off_last_name':o['last_name'],'player_off_first_name':o['first_name'],
                        'player_on_last_name':i['last_name'],'player_on_first_name':i['first_name'],'minute':mi,'is_tactical':True})
    return out,probs

stats={'pages':0,'sans_json':0,'introuvables':0,'banc':0,'reservistes':0,'banc_absent':0,'subs':0,'subs_ko':0,'fichiers':0}
for page in sorted(glob.glob(os.path.join(CACHE,'*.html'))):
    r=parse(page)
    if not r: continue
    date,players,subs,_=r
    if only and date not in only: continue
    stats['pages']+=1
    files=match_file(date)
    if len(files)!=1: stats['sans_json']+=1; print(f'{date} {os.path.basename(page)} : {len(files)} JSON'); continue
    path=files[0]; d=json.load(open(path))
    if not d.get('lineups',{}).get('france'): stats['sans_json']+=1; continue  # match sans composition
    lineup=d['lineups']['france']
    found=[(p,find(lineup,p['last'],p['first'])) for p in players]
    missing=same_match(players,lineup)
    if missing:
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
        new,probs=pair(subs,lineup)
        if probs: stats['subs_ko']+=1; print(f"{os.path.basename(path)} : {' ; '.join(probs)}")
        if new:
            new=[s for s in new if not any(p.get('played') is False for p in lineup if p['last_name'] in (s['player_on_last_name'],s['player_off_last_name']))]
            d['substitutions']=sorted(d['substitutions']+new,key=lambda s:(s['minute'] if s['minute'] is not None else 999))
            stats['subs']+=len(new); changed=True
    if changed:
        stats['fichiers']+=1
        if not dry: write(path,d)
print(stats)
