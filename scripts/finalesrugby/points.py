#!/usr/bin/env python3
"""
Complète la minute des points français (essais, transformations, pénalités, drops, essais de pénalité) des
feuilles de match JSON d'après les pages finalesrugby.fr en cache, quand le site donne, pour le même joueur et
le même type, autant de points que la feuille. Le site a des blocs « cartons », mais toujours vides.

Les points eux-mêmes ne sont jamais ajoutés ni retirés : le total reste celui contrôlé par
xv:validate-match-data.

  python3 scripts/finalesrugby/points.py [--dry] [AAAA-MM-JJ ...]
"""
import os,sys,glob,json
sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
from common import CACHE,parse,find,write,match_file,same_match
dry='--dry' in sys.argv
only={a for a in sys.argv[1:] if not a.startswith('--')}

def fill(events,site_minutes):
    """Minutes du site données aux points sans minute ; une minute déjà connue « consomme » la plus proche (±3)."""
    free=list(site_minutes)
    for e in events:
        if e['minute'] is None: continue
        near=[m for m in free if abs(m-e['minute'])<=3]
        if not near: return 0  # la feuille et le site ne parlent pas des mêmes points
        free.remove(min(near,key=lambda m:abs(m-e['minute'])))
    todo=[e for e in events if e['minute'] is None]
    for e,m in zip(todo,sorted(free)): e['minute']=m
    return len(todo)

stats={'pages':0,'minutes':0,'ecarts':0,'fichiers':0}
for page in sorted(glob.glob(os.path.join(CACHE,'*.html'))):
    r=parse(page)
    if not r: continue
    date,players,_,scoring=r
    if only and date not in only: continue
    files=match_file(date)
    if len(files)!=1: continue
    path=files[0]; d=json.load(open(path))
    lineup=d.get('lineups',{}).get('france')
    if not lineup or same_match(players,lineup): continue
    stats['pages']+=1
    fr=[e for e in d['events'] if e['team_side']=='france']
    changed=0
    for kind in ('essai','transformation','penalite','drop'):
        for s in scoring.get(kind,[]):
            if not s['minutes'] or len(s['minutes'])!=s['count']: continue
            if s['name'] is None:  # essai de pénalité
                typ,ours='essai_penalite',[e for e in fr if e['type']=='essai_penalite']
            else:
                p=find(lineup,*s['name'])
                if not p: continue
                typ,ours=kind,[e for e in fr if e['type']==kind and e.get('player_last_name')==p['last_name']
                               and e.get('player_first_name') in (None,p['first_name'])]
            if not any(e['minute'] is None for e in ours): continue
            if len(ours)!=s['count']:
                stats['ecarts']+=1; print(f"{os.path.basename(path)} : {typ} {s['name']} : {len(ours)} sur la feuille, {s['count']} sur le site"); continue
            changed+=fill(ours,s['minutes'])
    stats['minutes']+=changed
    if changed:
        d['events'].sort(key=lambda e:(e['minute'] if e['minute'] is not None else 999))
        stats['fichiers']+=1
        if not dry: write(path,d)
print(stats)
