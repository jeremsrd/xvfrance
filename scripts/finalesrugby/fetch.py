#!/usr/bin/env python3
"""
Met en cache les pages de match du XV de France de finalesrugby.fr, listées par le plan du site.

  python3 scripts/finalesrugby/fetch.py

Les pages vont dans storage/app/finalesrugby/{slug}.html (hors git). Une page déjà en cache n'est pas
retéléchargée. Une pause d'une demi-seconde sépare les requêtes.
"""
import os,re,time,urllib.request
ROOT=os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT=os.path.join(ROOT,'storage/app/finalesrugby'); os.makedirs(OUT,exist_ok=True)
get=lambda u: urllib.request.urlopen(urllib.request.Request(u,headers={'User-Agent':'Mozilla/5.0 (xvfrance.fr)'}),timeout=30).read().decode('utf-8','ignore')
index=get('https://finalesrugby.fr/sitemap.xml')
urls=set()
for sm in re.findall(r'<loc>([^<]+)</loc>',index):
    urls|=set(re.findall(r'https://finalesrugby\.fr/xv-de-france/[a-z0-9-]+-\d{4}(?:-\d+)?(?=<)',get(sm)))
print(len(urls),'pages de match')
new=0
for u in sorted(urls):
    path=os.path.join(OUT,u.rsplit('/',1)[1]+'.html')
    if os.path.exists(path): continue
    try: open(path,'w').write(get(u)); new+=1
    except Exception as e: print('échec',u,e)
    time.sleep(0.5)
print(new,'pages téléchargées')
