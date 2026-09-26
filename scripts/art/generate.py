"""
تصویرهای نقاشی‌گونه سایت (x-art) را می‌سازد.

اجرا:  python3 scripts/art/generate.py
خروجی: resources/views/components/art/pictures/<نام>.blade.php

طراحی‌ها در scenes_*.py هستند، شخصیت‌ها در engine.py و اشیا در props.py.
فایل‌های Blade خروجی را دستی ویرایش نکنید. رنگ‌ها currentColor و متغیر
توکن‌اند (--fbh-art-* در resources/css/tokens.css)، نه هگز. هر نام فقط در
یک جای سایت به کار می‌رود؛ tests/Feature/ArtTest.php این را می‌پاید.
"""
import glob
import importlib
import os
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)

from engine import svg  # noqa: E402
from registry import ART  # noqa: E402

for module in sorted(glob.glob(os.path.join(HERE, 'scenes_*.py'))):
    importlib.import_module(os.path.basename(module)[:-3])

out = sys.argv[1] if len(sys.argv) > 1 else os.path.join(HERE, '../../resources/views/components/art/pictures')
os.makedirs(out, exist_ok=True)
for old in glob.glob(os.path.join(out, '*.blade.php')):
    os.remove(old)

ROOT = ('<svg viewBox="{vb}" xmlns="http://www.w3.org/2000/svg" '
        "{{{{ $attributes->class(['text-ink'])->merge(['aria-hidden' => 'true', 'focusable' => 'false']) }}}}>")

for name, (viewbox, washes, body) in sorted(ART.items()):
    markup = svg(name, viewbox, washes, body)
    markup = markup.replace(f'<svg viewBox="{viewbox}" xmlns="http://www.w3.org/2000/svg">', ROOT.format(vb=viewbox), 1)
    with open(os.path.join(out, f'{name}.blade.php'), 'w') as fh:
        fh.write(markup + '\n')

print(f'{len(ART)} pictures written to {os.path.relpath(out)}')
