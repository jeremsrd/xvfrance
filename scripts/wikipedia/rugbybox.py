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
    # {{rugbybox …}} ou, sur les pages récentes, {{#invoke:rugby box|main …}}
    m=re.compile(r'\{\{\s*(?:rugbybox|#invoke:\s*rugby box)',re.I).search(txt,i)
    if not m: break
    i=m.start()
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
    for m in re.finditer(r'\|\s*(\w+)\s*=[ \t]*(.*?)(?=\n\s*\||\s*\|\s*\n\s*\w+\s*=|\s*\|?\s*\}\}\s*$)',box,re.S):
        fields[m.group(1)]=re.sub(r'\s+',' ',m.group(2)).strip()
    # selon les pages, les équipes sont dans team1/team2 ou home/away
    t1=fields.get('team1') or fields.get('home',''); t2=fields.get('team2') or fields.get('away','')
    if not re.search(r'France|\bFRA\b',t1+t2): continue
    if dates and not any(d in fields.get('date','') for d in dates): continue
    clean=lambda v: re.sub(r"<ref[^>]*/>|<ref[^>]*>.*?</ref>|\{\{(?:flagicon|sortname)[^}]*\}\}|\[\[(?:[^|\]]*\|)?([^\]]*)\]\]|'''?",lambda m:(m.group(1) or ' ') if m.group(0).startswith('[[') else ' ',re.sub(r'<br\s*/?>|\n\*',' ; ',re.sub(r'(?i)\{\{ru(?:-rt)?\|(\w+)(?:\|[^}]*)?\}\}',r'\1',v)))
    print('##',clean(fields.get('date','')),'|',clean(t1),clean(fields.get('score','')),clean(t2))
    for k in ('homescore','try1','con1','pen1','drop1','cards1','awayscore','try2','con2','pen2','drop2','cards2','time','stadium','attendance','referee'):
        if fields.get(k): print(f'  {k}: {re.sub(r" +"," ",clean(fields[k]))}')
