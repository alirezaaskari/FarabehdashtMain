"""پیش‌نمایش همه تصویرها در یک صفحه: python3 preview.py out.html [prefix]"""
import sys, importlib, glob, os
sys.path.insert(0, os.path.dirname(__file__))
from registry import ART
from engine import svg
MODS = os.environ.get('ART_MODULES')
for m in sorted(glob.glob(os.path.join(os.path.dirname(__file__), 'scenes_*.py'))):
    if MODS and os.path.basename(m)[7:-3] not in MODS.split(','):
        continue
    importlib.import_module(os.path.basename(m)[:-3])
TOK = open(os.path.join(os.path.dirname(__file__), '../../resources/css/tokens.css')).read() if False else ''
css = (':root{--fbh-surface:#fff;--fbh-art-hair:#3a302c;--fbh-art-sun:#f2c14e;--fbh-art-vest:#ee8547;--fbh-art-lime:#c9dd5a;'
       '--fbh-art-sky:#8fc9c3;--fbh-art-leaf:#a7cf8c;--fbh-art-rose:#ee9c9c;--fbh-art-skin:#f5d9c0;--fbh-art-pants:#56677a;'
       '--fbh-art-shirt:#cfe0ea;--fbh-art-shirt2:#e9dcef;--fbh-art-grape:#b7a6d9;--fbh-art-sand:#ebd9b4}'
       'body{margin:0;background:#fff;color:#1c2321;font:12px sans-serif}.g{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:10px}'
       'figure{margin:0;border:1px solid #eee;padding:4px}svg{width:100%;height:auto;display:block}')
pre = sys.argv[2] if len(sys.argv) > 2 else ''
items = [(n, v) for n, v in ART.items() if n.startswith(pre)]
html = f'<style>{css}</style><div class="g">' + ''.join(f'<figure>{svg(n, *v)}<figcaption>{n}</figcaption></figure>' for n, v in items) + '</div>'
open(sys.argv[1], 'w').write(html)
print(len(items), 'drawn')
