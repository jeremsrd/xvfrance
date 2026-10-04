#!/usr/bin/env python3
"""
Affiche les encadrés de match ({{rugbybox}}) de la France d'une page Wikipédia anglaise,
à partir du wikitext brut (aucun modèle de langage, aucune troncature).

  python3 scripts/wikipedia/rugbybox.py "2025 Six Nations Championship" "15 March"
"""
import sys,re,urllib.request,urllib.parse
title=sys.argv[1]; dates=sys.argv[2:]
url='https://en.wikipedia.org/w/index.php?action=raw&title='+urllib.parse.quote(title.replace(' ','_'))
txt=urllib.request.urlopen(urllib.request.Request(url,headers={'User-Agent':'xvfrance.fr data check'})).read().decode()
# découpe des modèles {{rugbybox ... }} avec accolades imbriquées
i=0
while True:
    i=txt.lower().find('{{rugbybox',i)
    if i<0: break
    depth=0;j=i
    while j<len(txt):
        if txt.startswith('{{',j): depth+=1;j+=2;continue
        if txt.startswith('}}',j):
            depth-=1;j+=2
            if depth==0: break
            continue
        j+=1
    box=txt[i:j]; i=j
    if 'France' not in box and 'FRA' not in box: continue
    fields={}
    for m in re.finditer(r'\|\s*(\w+)\s*=\s*(.*?)(?=\n\s*\||\}\}\s*$)',box,re.S):
        fields[m.group(1)]=re.sub(r'\s+',' ',m.group(2)).strip()
    if not re.search(r'France|\bFRA\b',fields.get('team1','')+fields.get('team2','')): continue
    if dates and not any(d in fields.get('date','') for d in dates): continue
    clean=lambda v: re.sub(r"<ref[^>]*/>|<ref[^>]*>.*?</ref>|\{\{(?:flagicon|sortname)[^}]*\}\}|<br\s*/?>|\[\[(?:[^|\]]*\|)?([^\]]*)\]\]|'''?",lambda m:(m.group(1) or ' ') if m.group(0).startswith('[[') else ' ',re.sub(r'\{\{ru(?:-rt)?\|(\w+)\}\}',r'\1',v))
    print('##',clean(fields.get('date','')),'|',clean(fields.get('team1','')),clean(fields.get('score','')),clean(fields.get('team2','')))
    for k in ('try1','con1','pen1','drop1','cards1','try2','con2','pen2','drop2','cards2','time','stadium','attendance','referee'):
        if fields.get(k): print(f'  {k}: {re.sub(r" +"," ",clean(fields[k]))}')
