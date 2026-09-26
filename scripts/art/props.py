"""
اشیای صحنه‌ها. هر تابع (x, y, s) می‌گیرد: x و y نقطه لنگر (معمولاً وسط پایین
شیء، یا وسط آن برای چیزی که در دست است) و s مقیاس. رنگ‌ها نام نمادین‌اند.
"""
from engine import INK, f, pt, path, line, poly, circle, ellipse, rect, limb, group


def at(x, y, s, inner, rot=0):
    r = f' rotate({f(rot)})' if rot else ''
    return group(inner, f'translate({f(x)} {f(y)}){r} scale({f(s)})')


# ------------------------------------------------------------ instruments

def sound_meter(x, y, s=1, rot=0, waves=True):
    """صداسنج دستی با میکروفون گرد؛ لنگر وسط دسته."""
    o = rect(-7, -16, 14, 34, 'paper', 4)
    o += rect(-4.5, -12, 9, 8, INK, 1.2, None)
    o += rect(-3, -10.5, 6, 3, 'leaf', .6, None)
    o += line('M-4 1 L4 1 M-4 6 L4 6', 1.4)
    o += circle((0, 12), 2.2, 'sun', INK, 1.2)
    o += line('M0 -16 L0 -21', 3)
    o += circle((0, -25), 5, 'pants', INK, 1.6)
    if waves:
        o += line('M8 -32 Q13 -25 8 -18', 1.6) + line('M13 -38 Q21 -25 13 -12', 1.6, op=.7)
    return at(x, y, s, o, rot)


def clipboard(x, y, s=1, rot=0, ticks=3, fill='sand'):
    o = rect(-15, -20, 30, 40, fill, 3)
    o += rect(-13, -17, 26, 35, 'paper', 1.5, None)
    o += rect(-7, -23, 14, 6, 'pants', 2, sw=1.6)
    for i in range(ticks):
        yy = -10 + i * 9
        o += line(f'M-9 {yy} L-7 {yy + 2} L-4 {yy - 2}', 1.4) + line(f'M-1 {yy} L9 {yy}', 1.3)
    return at(x, y, s, o, rot)


def flask(x, y, s=1, liquid='leaf', rot=0, bubbles=True):
    """ارلن؛ لنگر وسط کف."""
    o = path('M-5 -34 L-5 -20 L-16 -3 Q-18 0 -14 0 L14 0 Q18 0 16 -3 L5 -20 L5 -34 Z', 'paper')
    o += path('M-10.5 -12 L10.5 -12 L16 -3 Q18 0 14 0 L-14 0 Q-18 0 -16 -3 Z', liquid, None, op=.95)
    o += path('M-5 -34 L-5 -20 L-16 -3 Q-18 0 -14 0 L14 0 Q18 0 16 -3 L5 -20 L5 -34', None)
    o += line('M-7 -34 L7 -34', 2.4)
    if bubbles:
        o += circle((-2, -40), 2, liquid, INK, 1.1) + circle((3, -47), 1.4, liquid, INK, 1)
    return at(x, y, s, o, rot)


def beaker(x, y, s=1, liquid='sky', h=26):
    o = path(f'M-11 -{h} L-11 -2 Q-11 0 -9 0 L9 0 Q11 0 11 -2 L11 -{h} L13 -{h + 2}', 'paper')
    o += rect(-10, -h * .5, 20, h * .5 - 1, liquid, 1, None, op=.9)
    o += path(f'M-11 -{h} L-11 -2 Q-11 0 -9 0 L9 0 Q11 0 11 -2 L11 -{h} L13 -{h + 2}', None)
    o += line(f'M4 -{h - 6} L10 -{h - 6} M6 -{h - 12} L10 -{h - 12}', 1.2)
    return at(x, y, s, o)


def test_tubes(x, y, s=1, fills=('rose', 'sky', 'leaf')):
    o = rect(-24, -12, 48, 6, 'sand', 1.5)
    for i, c in enumerate(fills):
        xx = -15 + i * 15
        o += path(f'M{xx - 4} -34 L{xx - 4} -4 Q{xx} 2 {xx + 4} -4 L{xx + 4} -34', 'paper')
        if c:
            o += path(f'M{xx - 4} -18 L{xx - 4} -4 Q{xx} 2 {xx + 4} -4 L{xx + 4} -18 Z', c, None)
        o += path(f'M{xx - 4} -34 L{xx - 4} -4 Q{xx} 2 {xx + 4} -4 L{xx + 4} -34', None)
    o += line('M-20 -6 L-20 0 M20 -6 L20 0', 2)
    return at(x, y, s, o)


def drum(x, y, s=1, color='sky', label=True):
    o = path('M-18 -46 L-18 -2 Q0 4 18 -2 L18 -46 Z', color)
    o += ellipse((0, -46), 18, 4.5, color)
    o += line('M-18 -32 Q0 -27 18 -32 M-18 -14 Q0 -9 18 -14', 1.5)
    if label:
        o += poly([(0, -30), (7, -23), (0, -16), (-7, -23)], 'paper', w=1.4)
        o += poly([(0, -27.5), (4.5, -23), (0, -18.5), (-4.5, -23)], 'rose', stroke=None)
    return at(x, y, s, o)


def gas_cylinder(x, y, s=1, color='leaf'):
    o = path('M-9 -52 Q-9 -60 0 -60 Q9 -60 9 -52 L9 -2 Q9 0 7 0 L-7 0 Q-9 0 -9 -2 Z', color)
    o += rect(-3, -67, 6, 7, 'pants', 1, sw=1.5) + line('M-6 -69 L6 -69', 2.2)
    o += rect(-6, -40, 12, 14, 'paper', 1.5, sw=1.3)
    return at(x, y, s, o)


def air_pump(x, y, s=1):
    """پمپ نمونه‌برداری هوا با شلنگ؛ لنگر وسط."""
    o = rect(-10, -8, 20, 16, 'leaf', 3)
    o += rect(-6, -5, 8, 5, INK, 1, None) + circle((6, 3), 1.6, 'sun', None)
    o += line('M0 -8 Q2 -24 -8 -30', 1.6)
    return at(x, y, s, o)


def lux_meter(x, y, s=1, rot=0):
    o = rect(-9, -12, 18, 24, 'sun', 3) + rect(-6, -9, 12, 7, INK, 1, None)
    o += line('M0 12 Q4 20 12 20', 1.5) + circle((16, 20), 5, 'paper', INK, 1.6) + circle((16, 20), 2.4, 'sun', None)
    return at(x, y, s, o, rot)


def thermometer(x, y, s=1):
    o = rect(-4, -40, 8, 36, 'paper', 4) + circle((0, -4), 6.5, 'rose')
    o += rect(-1.6, -30, 3.2, 26, 'rose', 1.5, None)
    o += line('M4 -34 L7 -34 M4 -26 L7 -26 M4 -18 L7 -18', 1.2)
    return at(x, y, s, o)


def gauge(x, y, s=1, angle=-30, fill='paper', blank=False):
    o = circle((0, 0), 18, fill) + circle((0, 0), 14, 'paper', INK, 1.2)
    if not blank:
        o += path('M-11 6 A12 12 0 0 1 -6 -9', None, 'leaf', 3) + path('M-6 -9 A12 12 0 0 1 8 -8', None, 'sun', 3) + path('M8 -8 A12 12 0 0 1 11 6', None, 'rose', 3)
        import math
        a = math.radians(angle)
        o += line(f'M0 0 L{f(math.sin(a) * 11)} {f(-math.cos(a) * 11)}', 2)
    o += circle((0, 0), 2.2, INK, None)
    o += rect(-4, 18, 8, 7, 'pants', 1, sw=1.4)
    return at(x, y, s, o)


def dosimeter(x, y, s=1):
    o = rect(-7, -10, 14, 20, 'vest', 3) + rect(-4.5, -7, 9, 5, INK, 1, None) + circle((0, 5), 2, 'paper', None)
    return at(x, y, s, o)


def ear_muffs(x, y, s=1):
    o = path('M-14 0 Q-14 -22 0 -22 Q14 -22 14 0', None, INK, 3)
    o += rect(-19, -6, 9, 14, 'rose', 4) + rect(10, -6, 9, 14, 'rose', 4)
    return at(x, y, s, o)


def respirator(x, y, s=1):
    o = path('M-12 -6 Q-12 -16 0 -16 Q12 -16 12 -6 Q12 8 0 10 Q-12 8 -12 -6 Z', 'paper')
    o += circle((-12, 2), 5, 'sky') + circle((12, 2), 5, 'sky') + line('M-4 2 L4 2 M-4 5 L4 5', 1.2)
    return at(x, y, s, o)


def calculator(x, y, s=1, screen='42', blank=False):
    o = rect(-14, -20, 28, 40, 'pants', 3.5) + rect(-10, -16, 20, 9, 'leaf', 1.5, sw=1.4)
    if not blank:
        o += f'<text x="8" y="-9" font-size="7" text-anchor="end" font-family="monospace" fill="currentColor" data-numeric>{screen}</text>'
    for r in range(3):
        for c in range(3):
            o += rect(-9 + c * 7, -3 + r * 7, 5, 5, 'paper', 1, None)
    return at(x, y, s, o)


def toolbox(x, y, s=1, empty=False, color='rose'):
    o = ''
    if not empty:
        o += line('M-10 -22 L-4 -34 M6 -22 L12 -36', 3)
        o += circle((12, -38), 3, 'sun', INK, 1.3)
    o += path('M-30 -22 L30 -22 L28 0 L-28 0 Z', color)
    o += line('M-30 -14 L30 -14', 1.5)
    o += path('M-10 -22 L-10 -28 Q-10 -31 -7 -31 L7 -31 Q10 -31 10 -28 L10 -22', None, INK, 2.4)
    o += rect(-4, -17, 8, 5, 'sun', 1, sw=1.3)
    return at(x, y, s, o)


# ------------------------------------------------------------ office and learning

def laptop(x, y, s=1, screen='sky', content='lines', flip=False):
    """لنگر وسط لبه جلوی لپ‌تاپ روی میز."""
    o = path('M-26 0 L26 0 L22 -4 L-22 -4 Z', 'pants')
    o += rect(-20, -34, 40, 30, 'pants', 2.5) + rect(-17, -31, 34, 24, screen, 1, None)
    if content == 'lines':
        o += line('M-12 -25 L6 -25 M-12 -19 L10 -19 M-12 -13 L2 -13', 1.6, 'paper')
    elif content == 'play':
        o += poly([(-4, -25), (6, -19), (-4, -13)], 'paper', w=1.3)
    elif content == 'chart':
        o += rect(-12, -16, 5, 7, 'paper', .5, None) + rect(-5, -21, 5, 12, 'paper', .5, None) + rect(2, -26, 5, 17, 'paper', .5, None)
    elif content == 'x':
        o += line('M-5 -24 L5 -14 M5 -24 L-5 -14', 2.4, 'rose')
    elif content == 'check':
        o += line('M-6 -19 L-2 -15 L6 -24', 2.4, 'paper')
    return group(o, f'translate({f(x)} {f(y)}) scale({f(-s if flip else s)} {f(s)})')


def desk(x, y, w=120, h=40, color='sand'):
    """میز؛ y سطح میز."""
    o = rect(x - w / 2, y, w, 6, color, 2)
    o += line(f'M{f(x - w / 2 + 8)} {f(y + 6)} L{f(x - w / 2 + 8)} {f(y + h)} M{f(x + w / 2 - 8)} {f(y + 6)} L{f(x + w / 2 - 8)} {f(y + h)}', 2.6)
    return o


def chair(x, y, s=1, face=1, color='sky'):
    """صندلی؛ y کف زمین، نشیمن ۳۸ بالاتر. face جهت نشستن (پشتی در سمت مقابل)."""
    o = rect(-16, -40, 32, 6, color, 2)
    o += line('M-12 -34 L-14 0 M12 -34 L14 0', 2.2)
    o += path(f'M{-16 * face} -38 L{-18 * face} -76 Q{-18 * face} -80 {-14 * face} -80 L{-12 * face} -80 L{-12 * face} -40', color)
    return at(x, y, s, o)


def book(x, y, s=1, color='rose', rot=0, open_=False):
    if open_:
        o = path('M-24 -4 Q-12 -10 0 -4 Q12 -10 24 -4 L24 10 Q12 4 0 10 Q-12 4 -24 10 Z', 'paper')
        o += line('M0 -4 L0 10', 1.4) + line('M-19 -1 Q-12 -4 -5 -1 M-19 4 Q-12 1 -5 4 M5 -1 Q12 -4 19 -1 M5 4 Q12 1 19 4', 1.1, op=.7)
        o += path('M-24 10 Q-12 4 0 10 Q12 4 24 10 L24 13 Q12 7 0 13 Q-12 7 -24 13 Z', color, INK, 1.4)
        return at(x, y, s, o, rot)
    o = rect(-16, -5, 32, 10, color, 1.5) + line('M-12 -1 L-12 5', 1.2) + rect(11, -5, 5, 10, 'paper', 1, None, op=.5)
    return at(x, y, s, o, rot)


def book_stack(x, y, s=1, colors=('sky', 'rose', 'leaf', 'sun')):
    o = ''
    for i, c in enumerate(colors):
        w = 64 - (i % 2) * 8
        dx = (-1) ** i * 3
        o += rect(-w / 2 + dx, -14 * (i + 1), w, 14, c, 2)
        o += line(f'M{f(-w / 2 + dx + 8)} {-14 * (i + 1)} L{f(-w / 2 + dx + 8)} {-14 * i}', 1.3)
    return at(x, y, s, o)


def shelf(x, y, w=110, rows=2, gap=38, items=True, colors=('sky', 'rose', 'leaf', 'sun', 'grape', 'vest')):
    """قفسه دیواری؛ y کف پایین‌ترین طبقه."""
    o = ''
    for r in range(rows):
        yy = y - r * gap
        o += rect(x - w / 2, yy, w, 4, 'sand', 1)
        if items:
            xx = x - w / 2 + 6
            i = r * 3
            while xx < x + w / 2 - 12:
                c = colors[i % len(colors)]
                hh = 20 + (i * 7) % 12
                ww = 7 + (i * 3) % 6
                o += rect(xx, yy - hh, ww, hh, c, 1.2, sw=1.5)
                xx += ww + 2
                i += 1
    return o


def whiteboard(x, y, w=110, h=64, content='chart', legs=True):
    """تخته؛ x,y گوشه بالا-چپ."""
    o = rect(x, y, w, h, 'paper', 3)
    if content == 'chart':
        o += line(f'M{f(x + 12)} {f(y + h - 12)} L{f(x + 30)} {f(y + h - 28)} L{f(x + 46)} {f(y + h - 22)} L{f(x + 64)} {f(y + 16)}', 2, 'vest')
        o += line(f'M{f(x + 10)} {f(y + 10)} L{f(x + 10)} {f(y + h - 10)} L{f(x + 70)} {f(y + h - 10)}', 1.4)
        o += line(f'M{f(x + 78)} {f(y + 16)} L{f(x + w - 10)} {f(y + 16)} M{f(x + 78)} {f(y + 26)} L{f(x + w - 16)} {f(y + 26)} M{f(x + 78)} {f(y + 36)} L{f(x + w - 12)} {f(y + 36)}', 1.4)
    elif content == 'text':
        for i in range(4):
            o += line(f'M{f(x + 12)} {f(y + 14 + i * 12)} L{f(x + w - 14 - (i % 2) * 20)} {f(y + 14 + i * 12)}', 1.6)
    if legs:
        o += line(f'M{f(x + 14)} {f(y + h)} L{f(x + 6)} {f(y + h + 40)} M{f(x + w - 14)} {f(y + h)} L{f(x + w - 6)} {f(y + h + 40)}', 2.2)
    o += rect(x + w / 2 - 14, y + h - 2, 28, 4, 'sand', 1, sw=1.3)
    return o


def paper(x, y, s=1, rot=0, lines=4, fill='paper', fold=True, chart=False):
    o = path('M-14 -19 L8 -19 L14 -13 L14 19 L-14 19 Z', fill)
    if fold:
        o += path('M8 -19 L8 -13 L14 -13', None, INK, 1.4)
    for i in range(lines):
        o += line(f'M-9 {-10 + i * 6} L{9 - (i % 2) * 5} {-10 + i * 6}', 1.3, op=.8)
    if chart:
        o += rect(-8, 9, 4, 6, 'leaf', .5, None) + rect(-2, 5, 4, 10, 'sky', .5, None) + rect(4, 1, 4, 14, 'vest', .5, None)
    return at(x, y, s, o, rot)


def envelope(x, y, s=1, rot=0, color='paper'):
    o = rect(-16, -11, 32, 22, color, 2) + line('M-16 -11 L0 2 L16 -11', 1.6)
    return at(x, y, s, o, rot)


def phone(x, y, s=1, rot=0, screen='sky', content='lines'):
    o = rect(-8, -15, 16, 30, 'pants', 3) + rect(-6, -12, 12, 22, screen, 1, None)
    if content == 'lines':
        o += line('M-3.5 -8 L3.5 -8 M-3.5 -4 L2 -4 M-3.5 0 L3.5 0', 1.2, 'paper')
    elif content == 'qr':
        for i, j in ((0, 0), (1, 1), (2, 0), (0, 2), (2, 2), (1, 0)):
            o += rect(-4.5 + i * 3, -8 + j * 3, 2.6, 2.6, INK, .3, None)
    elif content == 'check':
        o += line('M-3 -1 L-1 1 L3.5 -4', 1.8, 'paper')
    elif content == 'bubble':
        o += rect(-4.5, -9, 8, 5, 'paper', 2, None) + rect(-3, -2, 8, 5, 'sun', 2, None)
    return at(x, y, s, o, rot)


def calendar(x, y, s=1, marked=((1, 1),), blank=False):
    """تقویم دیواری؛ لنگر گوشه بالا-چپ، ۶۰×۵۴."""
    o = rect(0, 0, 60, 54, 'paper', 3) + rect(0, 0, 60, 12, 'rose', 3)
    o += line('M14 -4 L14 5 M46 -4 L46 5', 2.6)
    if not blank:
        for r in range(3):
            for c in range(5):
                cx, cy = 7 + c * 11.5, 20 + r * 11
                if (r, c) in marked:
                    o += circle((cx + 2, cy + 2), 5.5, 'sun', INK, 1.4)
                o += rect(cx, cy, 4, 4, INK, 1, None, op=.55)
    return at(x, y, s, o)


def bell(x, y, s=1, rot=0, ring=True):
    o = path('M-14 6 Q-12 -4 -11 -10 Q-9 -22 0 -22 Q9 -22 11 -10 Q12 -4 14 6 Z', 'sun')
    o += line('M-16 6 L16 6', 2.4) + circle((0, 10), 3.6, 'sun', INK, 1.6) + circle((0, -24), 2.4, None, INK, 1.6)
    if ring:
        o += line('M-22 -14 Q-26 -6 -22 2 M22 -14 Q26 -6 22 2', 1.6)
    return at(x, y, s, o, rot)


def magnifier(x, y, s=1, rot=0):
    """ذره‌بین؛ لنگر مرکز شیشه."""
    o = limb([(9, 9), (22, 22)], 'vest', 5, 1.6)
    o += circle((0, 0), 12, 'sky', INK, 2.4, op=None)
    o += circle((0, 0), 12, None, INK, 2.4) + path('M-6 -4 Q-5 -8 -1 -8', None, 'paper', 2)
    return at(x, y, s, o, rot)


def pencil(x, y, s=1, rot=0, length=40, color='sun'):
    """مداد؛ لنگر نوک."""
    o = path(f'M0 0 L-4 -9 L-4 -{length} L4 -{length} L4 -9 Z', color)
    o += path('M0 0 L-4 -9 L4 -9 Z', 'sand', INK, 1.4) + path('M0 0 L-1.4 -3 L1.4 -3 Z', INK, None)
    o += rect(-4, -length - 5, 8, 5, 'rose', 1, sw=1.4)
    return at(x, y, s, o, rot)


def wallet(x, y, s=1, open_=False, coins=True):
    o = rect(-20, -14, 40, 28, 'vest', 4) + path('M6 -6 L22 -6 L22 6 L6 6 Q2 0 6 -6 Z', 'sun', INK, 1.6) + circle((12, 0), 1.8, INK, None)
    if open_ and not coins:
        o += path('M-18 -14 Q-10 -24 4 -14', None, INK, 1.4)
    return at(x, y, s, o)


def coin(x, y, s=1):
    return at(x, y, s, circle((0, 0), 7, 'sun') + circle((0, 0), 4.2, None, INK, 1.1))


def coins_stack(x, y, s=1, n=4):
    o = ''
    for i in range(n):
        o += ellipse((0, -i * 5), 11, 4, 'sun', INK, 1.6)
    return at(x, y, s, o)


def card(x, y, s=1, rot=0, color='grape', broken=False):
    o = rect(-18, -12, 36, 24, color, 3) + rect(-18, -6, 36, 5, INK, 0, None, op=.8) + rect(-13, 4, 10, 4, 'sun', 1, None)
    if broken:
        o += path('M2 -13 L-2 -4 L3 2 L-1 13', None, 'paper', 3)
    return at(x, y, s, o, rot)


def cart(x, y, s=1, items=True):
    o = ''
    if items:
        o += rect(-16, -46, 16, 20, 'sky', 1.5, sw=1.6) + rect(0, -42, 14, 16, 'rose', 1.5, sw=1.6) + rect(-8, -54, 12, 10, 'sun', 1.5, sw=1.6)
    o += path('M-34 -48 L-26 -48 L-20 -14 L20 -14 L26 -40 L-24 -40', None, INK, 2.4)
    o += line('M-22 -26 L23 -26', 1.4, op=.6)
    o += circle((-14, -5), 4.5, 'paper', INK, 2) + circle((16, -5), 4.5, 'paper', INK, 2)
    return at(x, y, s, o)


def bag(x, y, s=1, color='leaf', items=True):
    o = ''
    if items:
        o += rect(-12, -44, 10, 18, 'sky', 1, sw=1.5) + rect(0, -40, 10, 14, 'rose', 1, sw=1.5)
    o += path('M-18 -32 L18 -32 L15 0 L-15 0 Z', color)
    o += path('M-8 -32 Q-8 -42 0 -42 Q8 -42 8 -32', None, INK, 2)
    return at(x, y, s, o)


def box(x, y, s=1, open_=False, color='sand', label=True):
    o = rect(-22, -30, 44, 30, color, 2)
    if open_:
        o += path('M-22 -30 L-30 -40 L-6 -40 L0 -30', color, INK, 1.8) + path('M22 -30 L30 -40 L6 -40 L0 -30', color, INK, 1.8)
    else:
        o += line('M-22 -22 L22 -22', 1.4) + rect(-5, -30, 10, 8, 'paper', 0, None, op=.7)
    if label:
        o += rect(-14, -14, 14, 8, 'paper', 1, sw=1.2)
    return at(x, y, s, o)


def key(x, y, s=1, rot=0):
    o = circle((0, 0), 9, 'sun') + circle((0, 0), 3.4, 'paper', INK, 1.5)
    o += path('M9 -2.5 L34 -2.5 L34 6 L30 6 L30 2.5 L26 2.5 L26 6 L22 6 L22 2.5 L9 2.5 Z', 'sun', INK, 1.8)
    return at(x, y, s, o, rot)


def padlock(x, y, s=1, open_=False):
    arc = 'M-9 -14 L-9 -22 Q-9 -32 0 -32 Q9 -32 9 -22 L9 -14' if not open_ else 'M-9 -14 L-9 -24 Q-9 -34 0 -34 Q9 -34 9 -26'
    o = path(arc, None, INK, 3.4) + rect(-14, -15, 28, 22, 'sun', 3)
    o += circle((0, -6), 2.8, INK, None) + line('M0 -6 L0 1', 2.2)
    return at(x, y, s, o)


def door(x, y, w=44, h=90, color='sky', open_=False):
    """در؛ x,y وسط پایین."""
    o = rect(x - w / 2 - 4, y - h - 4, w + 8, h + 4, 'paper', 2)
    if open_:
        o += rect(x - w / 2, y - h, w, h, 'sun', 1, None, op=.45)
        o += path(f'M{f(x - w / 2)} {f(y - h)} L{f(x - w / 2 + 14)} {f(y - h + 8)} L{f(x - w / 2 + 14)} {f(y + 6)} L{f(x - w / 2)} {f(y)} Z', color)
    else:
        o += rect(x - w / 2, y - h, w, h, color, 1)
        o += circle((x + w / 2 - 8, y - h / 2), 2.6, 'sun', INK, 1.4)
        o += rect(x - w / 2 + 7, y - h + 8, w - 14, 26, None, 1, sw=1.3) + rect(x - w / 2 + 7, y - h + 42, w - 14, 36, None, 1, sw=1.3)
    return o


def speech(x, y, w=60, h=34, color='paper', tail=-1, content='lines'):
    """حباب گفت‌وگو؛ x,y گوشه بالا-چپ. tail: ‎-۱ دم پایین چپ، ۱ پایین راست."""
    tx = x + 14 if tail < 0 else x + w - 14
    o = path(f'M{f(x + 8)} {f(y)} L{f(x + w - 8)} {f(y)} Q{f(x + w)} {f(y)} {f(x + w)} {f(y + 8)} L{f(x + w)} {f(y + h - 8)} '
             f'Q{f(x + w)} {f(y + h)} {f(x + w - 8)} {f(y + h)} L{f(tx + 6)} {f(y + h)} L{f(tx - 4 * tail)} {f(y + h + 10)} L{f(tx - 4)} {f(y + h)} '
             f'L{f(x + 8)} {f(y + h)} Q{f(x)} {f(y + h)} {f(x)} {f(y + h - 8)} L{f(x)} {f(y + 8)} Q{f(x)} {f(y)} {f(x + 8)} {f(y)} Z', color)
    if content == 'lines':
        o += line(f'M{f(x + 10)} {f(y + 12)} L{f(x + w - 12)} {f(y + 12)} M{f(x + 10)} {f(y + 21)} L{f(x + w - 22)} {f(y + 21)}', 1.5, op=.8)
    elif content == 'q':
        cx = x + w / 2
        o += path(f'M{f(cx - 5)} {f(y + 12)} Q{f(cx - 5)} {f(y + 6)} {f(cx)} {f(y + 6)} Q{f(cx + 5)} {f(y + 6)} {f(cx + 5)} {f(y + 11)} Q{f(cx + 5)} {f(y + 15)} {f(cx)} {f(y + 17)} L{f(cx)} {f(y + 21)}', None, INK, 2.4)
        o += circle((cx, y + 27), 1.8, INK, None)
    elif content == 'dots':
        for i in range(3):
            o += circle((x + w / 2 - 10 + i * 10, y + h / 2), 2.6, INK, None)
    elif content == 'check':
        cx = x + w / 2
        o += line(f'M{f(cx - 8)} {f(y + h / 2)} L{f(cx - 2)} {f(y + h / 2 + 6)} L{f(cx + 9)} {f(y + h / 2 - 7)}', 2.6, 'leaf')
        o += line(f'M{f(cx - 8)} {f(y + h / 2)} L{f(cx - 2)} {f(y + h / 2 + 6)} L{f(cx + 9)} {f(y + h / 2 - 7)}', 1, INK, .5)
    return o


def plant(x, y, s=1, pot='vest'):
    o = path('M-10 -18 L10 -18 L8 0 L-8 0 Z', pot)
    o += path('M0 -18 Q-2 -34 -14 -40 Q-12 -26 0 -18 Z', 'leaf', INK, 1.5)
    o += path('M0 -18 Q4 -40 14 -46 Q14 -28 0 -18 Z', 'leaf', INK, 1.5)
    o += path('M0 -18 Q0 -30 -2 -44', None, INK, 1.3)
    return at(x, y, s, o)


def mug(x, y, s=1, color='sky', steam=True):
    o = path('M-7 -14 L7 -14 L6 0 L-6 0 Z', color) + path('M7 -11 Q13 -11 12 -6 Q11 -3 6.5 -3', None, INK, 1.6)
    if steam:
        o += line('M-2 -18 Q-5 -22 -2 -26 M3 -18 Q0 -22 3 -26', 1.2, op=.7)
    return at(x, y, s, o)


def lamp(x, y, s=1, color='sun'):
    o = line('M0 0 L0 -6 M-10 0 L10 0', 2.4) + line('M0 -6 L-10 -30 L6 -44', 2.2)
    o += path('M6 -44 L20 -50 L24 -36 Z', color) + path('M22 -38 L30 -20 L12 -34', 'sun', None, op=.25)
    return at(x, y, s, o)


def window(x, y, w=56, h=50, sky='sky'):
    o = rect(x, y, w, h, sky, 2)
    o += path(f'M{f(x + 8)} {f(y + h - 8)} Q{f(x + 18)} {f(y + h - 20)} {f(x + 28)} {f(y + h - 8)}', 'leaf', None)
    o += line(f'M{f(x + w / 2)} {f(y)} L{f(x + w / 2)} {f(y + h)} M{f(x)} {f(y + h / 2)} L{f(x + w)} {f(y + h / 2)}', 2)
    o += rect(x - 4, y + h, w + 8, 4, 'paper', 1, sw=1.6)
    return o


def cone(x, y, s=1):
    o = path('M-8 -30 L8 -30 L14 0 L-14 0 Z', 'vest') + path('M-5.5 -18 L5.5 -18 L7.2 -10 L-7.2 -10 Z', 'paper', None)
    o += rect(-18, -2, 36, 4, 'vest', 1.5)
    return at(x, y, s, o)


def factory(x, y, s=1, color='sky', chimney='rose', smoke=True, lit=False):
    """کارخانه ۱۴۰×۹۰؛ لنگر گوشه پایین-چپ."""
    o = ''
    if smoke:
        o += circle((122, -104), 7, 'paper', INK, 1.5) + circle((132, -114), 9, 'paper', INK, 1.5) + circle((146, -120), 10, 'paper', INK, 1.5)
    o += path('M0 0 L0 -60 L30 -78 L30 -60 L60 -78 L60 -60 L90 -78 L90 0 Z', color)
    o += rect(112, -96, 16, 96, chimney, 1) + line('M112 -84 L128 -84 M112 -76 L128 -76', 1.4)
    o += rect(90, -44, 26, 44, 'sand', 1)
    for i in range(4):
        o += rect(8 + i * 20, -46, 12, 14, 'sun' if lit and i % 2 == 0 else 'paper', 1.5, sw=1.5)
    o += rect(32, -24, 22, 24, 'pants', 1.5)
    return at(x, y, s, o)


def tank(x, y, s=1, color='leaf'):
    o = path('M-22 -60 Q-22 -70 0 -70 Q22 -70 22 -60 L22 0 L-22 0 Z', color)
    o += line('M-22 -44 L22 -44 M-22 -24 L22 -24', 1.5) + line('M16 0 L16 -70 M16 -52 L24 -52', 1.2, op=.6)
    return at(x, y, s, o)


def pipe(x1, y1, x2, y2, color='sand'):
    return limb([(x1, y1), (x2, y2)], color, 6, 1.6)


def forklift(x, y, s=1):
    o = rect(-30, -34, 42, 24, 'sun', 3) + path('M-18 -34 L-18 -58 L6 -58 L10 -34', None, INK, 2.2)
    o += line('M18 -64 L18 -4 M18 -8 L38 -8', 3) + rect(22, -26, 18, 14, 'sand', 1, sw=1.6)
    o += circle((-18, -8), 8, INK, None) + circle((6, -8), 8, INK, None) + circle((-18, -8), 3, 'paper', None) + circle((6, -8), 3, 'paper', None)
    return at(x, y, s, o)


def crane(x, y, s=1):
    o = line('M0 0 L0 -130', 3) + line('M-4 0 L-4 -130 M4 0 L4 -130', 1.2, op=.7)
    for i in range(8):
        o += line(f'M-4 {-i * 16} L4 {-i * 16 - 16}', 1, op=.7)
    o += line('M-30 -130 L100 -130', 3) + line('M0 -146 L-30 -130 M0 -146 L100 -130', 1.3)
    o += rect(-34, -128, 16, 14, 'pants', 1, sw=1.5) + line('M80 -130 L80 -80', 1.4) + rect(72, -80, 16, 10, 'vest', 1, sw=1.5)
    return at(x, y, s, o)


def tree(x, y, s=1, color='leaf'):
    o = line('M0 0 L0 -26', 3) + circle((0, -40), 17, color) + circle((-10, -32), 9, color, INK, 1.4) + circle((10, -30), 8, color, INK, 1.4)
    return at(x, y, s, o)


def bird(x, y, s=1):
    return at(x, y, s, line('M-6 0 Q-3 -4 0 0 Q3 -4 6 0', 1.5))


def cloud(x, y, s=1, color='paper'):
    return at(x, y, s, path('M-20 0 Q-22 -10 -12 -12 Q-10 -22 2 -20 Q10 -28 18 -18 Q28 -18 26 -6 Q30 0 22 2 L-18 2 Q-24 2 -20 0 Z', color, INK, 1.5))


def sun(x, y, s=1):
    o = circle((0, 0), 12, 'sun')
    for i in range(8):
        import math
        a = math.radians(i * 45)
        o += line(f'M{f(math.cos(a) * 16)} {f(math.sin(a) * 16)} L{f(math.cos(a) * 21)} {f(math.sin(a) * 21)}', 1.8)
    return at(x, y, s, o)


def sparkle(x, y, s=1, color='sun'):
    return at(x, y, s, path('M0 -8 Q1 -1 8 0 Q1 1 0 8 Q-1 1 -8 0 Q-1 -1 0 -8 Z', color, INK, 1.2))


def confetti(x, y, w=80, h=40, seed=1):
    import random
    r = random.Random(seed)
    o = ''
    cs = ['sun', 'rose', 'sky', 'leaf', 'grape', 'vest']
    for i in range(14):
        cx, cy = x + r.random() * w, y + r.random() * h
        c = cs[i % len(cs)]
        if i % 3 == 0:
            o += circle((cx, cy), 2.2, c, None)
        else:
            a = r.randint(0, 180)
            o += rect(cx - 3, cy - 1.2, 6, 2.4, c, .5, None, rot=a)
    return o


def check_badge(x, y, s=1, color='leaf'):
    o = circle((0, 0), 14, color) + line('M-6 0 L-2 5 L7 -5', 2.8, 'paper')
    return at(x, y, s, o)


def x_badge(x, y, s=1, color='rose'):
    o = circle((0, 0), 14, color) + line('M-5 -5 L5 5 M5 -5 L-5 5', 2.8, 'paper')
    return at(x, y, s, o)


def qr(x, y, s=1):
    o = rect(-16, -16, 32, 32, 'paper', 2)
    pat = [(0, 0), (1, 0), (0, 1), (4, 0), (5, 0), (5, 1), (0, 4), (0, 5), (1, 5), (2, 2), (3, 3), (2, 4), (4, 4), (5, 5), (3, 1), (4, 2)]
    for i, j in pat:
        o += rect(-12 + i * 4.2, -12 + j * 4.2, 3.8, 3.8, INK, .4, None)
    return at(x, y, s, o)


def shield(x, y, s=1, color='leaf', mark='check'):
    o = path('M0 -24 L18 -17 Q18 8 0 20 Q-18 8 -18 -17 Z', color)
    if mark == 'check':
        o += line('M-7 -1 L-2 5 L8 -6', 3, 'paper')
    return at(x, y, s, o)


def hourglass(x, y, s=1):
    o = rect(-14, -40, 28, 4, 'sand', 1.5) + rect(-14, -4, 28, 4, 'sand', 1.5)
    o += path('M-10 -36 L10 -36 Q10 -26 2 -20 Q10 -14 10 -4 L-10 -4 Q-10 -14 -2 -20 Q-10 -26 -10 -36 Z', 'paper')
    o += path('M-6 -30 L6 -30 Q4 -25 0 -22 Q-4 -25 -6 -30 Z', 'sun', None) + path('M-8 -6 Q0 -14 8 -6 Z', 'sun', None)
    return at(x, y, s, o)


def server(x, y, s=1):
    o = rect(-20, -80, 40, 80, 'pants', 3)
    for i in range(4):
        o += rect(-15, -74 + i * 18, 30, 13, 'shirt', 1.5, sw=1.4) + circle((-9, -67.5 + i * 18), 2, 'leaf', None) + line(f'M-2 {-67.5 + i * 18} L10 {-67.5 + i * 18}', 1.2)
    return at(x, y, s, o)


def signpost(x, y, s=1, blank=False, colors=('sun', 'sky', 'rose')):
    o = rect(-3, -90, 6, 90, 'sand', 1)
    for i, c in enumerate(colors):
        d = 1 if i % 2 == 0 else -1
        yy = -84 + i * 22
        pts = [(0, yy), (34 * d, yy), (42 * d, yy + 7), (34 * d, yy + 14), (0, yy + 14)] if d > 0 else [(0, yy), (-34, yy), (-42, yy + 7), (-34, yy + 14), (0, yy + 14)]
        o += poly(pts, c, w=1.8)
        if not blank:
            o += line(f'M{6 * d} {yy + 7} L{24 * d} {yy + 7}', 1.4)
    return at(x, y, s, o)


def flag(x, y, s=1, color='vest', down=False):
    if down:
        return at(x, y, s, line('M-20 0 L20 -4', 2.2) + poly([(14, -4), (22, -14), (26, -3)], color, w=1.6))
    return at(x, y, s, line('M0 0 L0 -40', 2.2) + poly([(0, -40), (18, -34), (0, -28)], color, w=1.6))


def chart_board(x, y, s=1, bars=(14, 22, 30, 40), colors=('sky', 'leaf', 'sun', 'vest'), flat=False):
    """نمودار میله‌ای ایستاده؛ لنگر وسط پایین."""
    o = rect(-36, -60, 72, 54, 'paper', 3) + line('M-36 -6 L-40 0 M36 -6 L40 0', 2)
    o += line('M-28 -14 L28 -14', 1.4)
    for i, b in enumerate(bars):
        hh = 3 if flat else b
        o += rect(-24 + i * 13, -14 - hh, 9, hh, colors[i % len(colors)], 1, sw=1.4)
    return at(x, y, s, o)


def piggy(x, y, s=1):
    o = ellipse((0, -18), 22, 16, 'rose') + ellipse((20, -20), 5, 6, 'rose', INK, 1.6)
    o += circle((19, -21), 1, INK, None) + circle((22, -19), 1, INK, None) + circle((8, -24), 1.6, INK, None)
    o += path('M-2 -34 L4 -40 L6 -32', 'rose', INK, 1.6) + line('M-6 -33 L4 -33', 2.4)
    o += line('M-12 -4 L-12 0 M10 -4 L10 0', 3) + path('M-22 -20 Q-30 -24 -26 -30', None, INK, 1.6)
    return at(x, y, s, o)


def bank(x, y, s=1, color='sand'):
    o = poly([(-40, -60), (0, -80), (40, -60)], color) + rect(-40, -60, 80, 6, color, 1)
    for i in range(4):
        o += rect(-32 + i * 19, -52, 7, 46, 'paper', 1, sw=1.5)
    o += rect(-44, -6, 88, 6, color, 1)
    return at(x, y, s, o)


def gift(x, y, s=1, color='grape'):
    o = rect(-20, -30, 40, 30, color, 2) + rect(-23, -38, 46, 9, color, 2)
    o += rect(-4, -38, 8, 38, 'sun', 0, sw=1.4) + path('M0 -38 Q-14 -52 -12 -40 Z M0 -38 Q14 -52 12 -40 Z', 'sun', INK, 1.5)
    return at(x, y, s, o)


def stairs(x, y, n=4, w=26, h=18, color='sky'):
    o = ''
    for i in range(n):
        o += rect(x + i * w, y - (i + 1) * h, w * (n - i), h, color, 1)
    return o


def star(x, y, s=1, color='sun'):
    import math
    pts = []
    for i in range(10):
        r = 14 if i % 2 == 0 else 6
        a = math.radians(-90 + i * 36)
        pts.append((math.cos(a) * r, math.sin(a) * r))
    return at(x, y, s, poly(pts, color, w=1.8))


def camera(x, y, s=1):
    o = rect(-16, -10, 32, 20, 'pants', 3) + circle((0, 0), 7, 'sky', INK, 1.8) + rect(-10, -14, 8, 5, 'pants', 1, sw=1.4)
    o += line('M0 10 L-12 46 M0 10 L12 46 M0 10 L0 46', 1.8)
    return at(x, y, s, o)


def headphones(x, y, s=1):
    o = path('M-12 4 Q-12 -14 0 -14 Q12 -14 12 4', None, INK, 2.6) + rect(-15, 0, 7, 11, 'grape', 3, sw=1.6) + rect(8, 0, 7, 11, 'grape', 3, sw=1.6)
    return at(x, y, s, o)


def backpack(x, y, s=1, color='sky', empty=False):
    o = path('M-16 -40 Q-16 -48 0 -48 Q16 -48 16 -40 L16 0 L-16 0 Z', color)
    o += rect(-10, -24, 20, 14, color, 3, sw=1.6)
    if empty:
        o += path('M-16 -40 Q0 -30 16 -40', None, INK, 1.4) + line('M-6 -44 L6 -44', 1.2)
    return at(x, y, s, o)


def folder(x, y, s=1, color='sun', empty=False, papers=True):
    o = ''
    if papers and not empty:
        o += rect(-14, -32, 28, 24, 'paper', 1, sw=1.5) + line('M-9 -26 L9 -26 M-9 -20 L6 -20', 1.2)
    o += path('M-24 -26 L-8 -26 L-4 -22 L24 -22 L24 0 L-24 0 Z', color)
    o += path('M-24 -16 L24 -16 L22 0 L-22 0 Z', color, INK, 1.6)
    return at(x, y, s, o)


def funnel(x, y, s=1):
    o = path('M-20 -30 L20 -30 L4 -10 L4 4 L-4 8 L-4 -10 Z', 'sky')
    return at(x, y, s, o)


def pegboard(x, y, w=80, h=50):
    o = rect(x, y, w, h, 'sand', 2)
    for i in range(6):
        for j in range(4):
            o += circle((x + 8 + i * 13, y + 8 + j * 11), 1.2, INK, None, op=.5)
    return o


def register(x, y, s=1, empty=True):
    o = path('M-26 0 L-22 -26 L22 -26 L26 0 Z', 'grape') + rect(-14, -40, 28, 14, 'pants', 2) + rect(-10, -37, 20, 8, 'leaf', 1, None)
    if empty:
        o += rect(-18, -2, 36, 8, 'paper', 1, sw=1.5)
    return at(x, y, s, o)


def tray(x, y, s=1, papers=0):
    o = ''
    for i in range(papers):
        o += rect(-18 + i, -10 - i * 3, 36, 4, 'paper', 1, sw=1.3)
    o += path('M-24 -10 L24 -10 L20 0 L-20 0 Z', 'sky')
    return at(x, y, s, o)


def typewriter(x, y, s=1):
    o = rect(-18, -44, 36, 24, 'paper', 1, sw=1.6) + line('M-12 -38 L12 -38 M-12 -32 L6 -32', 1.2)
    o += path('M-30 0 L-26 -22 L26 -22 L30 0 Z', 'rose')
    for i in range(3):
        for j in range(6):
            o += circle((-18 + j * 7.2, -16 + i * 5), 1.8, 'paper', INK, 1)
    return at(x, y, s, o)


def scroll_doc(x, y, s=1):
    o = rect(-18, -30, 36, 48, 'paper', 1) + ellipse((0, -30), 20, 4, 'sand', INK, 1.6) + ellipse((0, 18), 20, 4, 'sand', INK, 1.6)
    o += f'<text x="0" y="-6" font-size="14" text-anchor="middle" fill="currentColor">§</text>'
    o += line('M-10 2 L10 2 M-10 8 L6 8', 1.2)
    return at(x, y, s, o)


def hat_rack(x, y, s=1):
    o = line('M0 0 L0 -90 M-16 0 L16 0', 2.6) + line('M0 -80 L-14 -86 M0 -80 L14 -86 M0 -60 L-14 -66 M0 -60 L14 -66', 2)
    o += path('M-28 -86 Q-28 -100 -16 -100 Q-4 -100 -4 -86 Z', 'sun', INK, 1.6) + line('M-31 -86 L-1 -86', 2.4)
    o += path('M4 -86 Q4 -98 16 -98 Q28 -98 28 -86 Z', 'grape', INK, 1.6) + line('M2 -86 L32 -86', 2.4)
    o += path('M-26 -64 L-8 -70 L-2 -64 L-20 -58 Z', INK, None) + line('M-14 -64 L-14 -56', 1.2)
    o += path('M6 -66 Q6 -76 16 -76 Q26 -76 26 -66 Z', 'paper', INK, 1.6) + line('M4 -66 L28 -66', 2.4)
    return at(x, y, s, o)


def pie(x, y, s=1):
    o = circle((0, 0), 16, 'sky') + path('M0 0 L0 -16 A16 16 0 0 1 15 5 Z', 'sun', INK, 1.6) + path('M0 0 L15 5 A16 16 0 0 1 -6 15 Z', 'rose', INK, 1.6)
    return at(x, y, s, o)


def stopwatch(x, y, s=1):
    o = circle((0, 0), 14, 'paper') + rect(-3, -20, 6, 5, 'pants', 1, sw=1.4) + line('M0 0 L5 -7', 2) + circle((0, 0), 1.8, INK, None)
    return at(x, y, s, o)


def zzz(x, y, s=1):
    o = f'<text x="0" y="0" font-size="12" font-weight="700" fill="currentColor">z</text><text x="8" y="-10" font-size="9" font-weight="700" fill="currentColor">z</text><text x="14" y="-18" font-size="7" font-weight="700" fill="currentColor">z</text>'
    return at(x, y, s, o)


def wrench(x, y, s=1, rot=0):
    o = limb([(0, 0), (0, 30)], 'pants', 5, 1.6) + path('M-7 -8 Q-8 2 0 3 Q8 2 7 -8 L3 -8 L3 -3 L-3 -3 L-3 -8 Z', 'pants', INK, 1.6)
    return at(x, y, s, o, rot)


def smoke(x, y, s=1):
    return at(x, y, s, circle((0, 0), 6, 'paper', INK, 1.3) + circle((8, -8), 8, 'paper', INK, 1.3) + circle((0, -18), 7, 'paper', INK, 1.3))


def machine(x, y, s=1, color='leaf', broken=False):
    """دستگاه صنعتی ۷۰×۵۰؛ لنگر وسط پایین."""
    o = rect(-35, -50, 70, 50, color, 3) + rect(-26, -64, 36, 14, color, 2)
    o += circle((-14, -26), 9, 'paper', INK, 1.8) + circle((-14, -26), 3, INK, None)
    o += rect(4, -36, 22, 10, 'paper', 1.5, sw=1.5) + line('M6 -18 L26 -18 M6 -12 L22 -12', 1.4)
    if broken:
        o += line('M8 -34 L14 -30 L10 -28 L18 -24', 1.6, 'rose')
    return at(x, y, s, o)


def maint_sign(x, y, s=1):
    o = line('M-16 0 L-4 -46 L8 0 M-10 -22 L2 -22', 2.2)
    o += poly([(-4, -70), (18, -34), (-26, -34)], 'sun', w=2) + line('M-4 -58 L-4 -46', 2.8) + circle((-4, -40), 1.6, INK, None)
    return at(x, y, s, o)


def papers_flying(x, y, s=1, n=5, seed=3):
    import random
    r = random.Random(seed)
    o = ''
    for i in range(n):
        o += paper(r.randint(-50, 50), r.randint(-40, 10), .7, r.randint(-40, 40), lines=2)
    return at(x, y, s, o)


def binder(x, y, s=1, color='sky', rot=0):
    o = rect(-7, -30, 14, 30, color, 1.5) + circle((0, -8), 3, 'paper', INK, 1.2) + rect(-4, -26, 8, 10, 'paper', .5, sw=1.2)
    return at(x, y, s, o, rot)


def tape(x, y, s=1):
    """متر نواری."""
    o = circle((0, 0), 10, 'sun') + circle((0, 0), 3.5, 'paper', INK, 1.4) + rect(8, 4, 34, 5, 'sun', .5, sw=1.4)
    for i in range(6):
        o += line(f'M{12 + i * 5} 4 L{12 + i * 5} 6.5', 1)
    return at(x, y, s, o)


def fire_ext(x, y, s=1):
    o = path('M-8 -44 Q-8 -50 0 -50 Q8 -50 8 -44 L8 -2 Q8 0 6 0 L-6 0 Q-8 0 -8 -2 Z', 'rose')
    o += path('M-4 -54 L6 -54 L10 -50 M0 -54 L0 -50', None, INK, 2) + path('M6 -52 Q18 -50 16 -30', None, INK, 2.2) + rect(-5, -34, 10, 12, 'paper', 1, sw=1.3)
    return at(x, y, s, o)


def goggles(x, y, s=1):
    o = rect(-16, -7, 32, 14, 'sky', 6) + line('M0 -5 L0 5', 1.6) + line('M-16 0 L-22 0 M16 0 L22 0', 2)
    return at(x, y, s, o)


def glove(x, y, s=1, rot=0):
    o = path('M-8 10 L-8 -6 Q-8 -10 -5 -10 L-5 -16 Q-5 -19 -2 -19 Q1 -19 1 -16 L1 -12 L4 -14 Q7 -14 7 -11 L7 10 Z', 'vest', INK, 1.7)
    return at(x, y, s, o, rot)


def id_card(x, y, s=1, rot=0, color='sky'):
    o = rect(-15, -10, 30, 20, 'paper', 2.5) + rect(-15, -10, 30, 6, color, 2, None)
    o += circle((-7, 3), 4, 'skin', INK, 1.3) + line('M0 1 L10 1 M0 6 L7 6', 1.3)
    return at(x, y, s, o, rot)


def rocket_arrow(x1, y1, x2, y2, color='leaf'):
    import math
    a = math.atan2(y2 - y1, x2 - x1)
    hx, hy = x2 - math.cos(a) * 10, y2 - math.sin(a) * 10
    n = (-math.sin(a) * 7, math.cos(a) * 7)
    return limb([(x1, y1), (hx, hy)], color, 5, 1.6) + poly([(x2, y2), (hx + n[0], hy + n[1]), (hx - n[0], hy - n[1])], color, w=1.6)


def cloud_upload(x, y, s=1):
    o = path('M-26 8 Q-34 8 -32 -2 Q-30 -12 -20 -10 Q-18 -26 -2 -26 Q14 -26 16 -12 Q30 -14 30 0 Q30 8 22 8 Z', 'paper')
    o += limb([(0, 14), (0, -8)], 'sky', 5, 1.6) + poly([(0, -16), (8, -6), (-8, -6)], 'sky', w=1.6)
    return at(x, y, s, o)


def balance(x, y, s=1, left='rose', right='sky', tilt=6):
    import math
    a = math.radians(tilt)
    dx, dy = math.cos(a) * 34, math.sin(a) * 34
    o = line('M0 0 L0 -60 M-14 0 L14 0', 2.6) + line(f'M{f(-dx)} {f(-60 - dy)} L{f(dx)} {f(-60 + dy)}', 2.4) + circle((0, -60), 3, 'sun', INK, 1.4)
    for sx, c in ((-1, left), (1, right)):
        px, py = sx * dx, -60 + sx * dy
        o += line(f'M{f(px)} {f(py)} L{f(px - 10)} {f(py + 22)} M{f(px)} {f(py)} L{f(px + 10)} {f(py + 22)}', 1.2)
        o += path(f'M{f(px - 14)} {f(py + 22)} L{f(px + 14)} {f(py + 22)} Q{f(px)} {f(py + 32)} {f(px - 14)} {f(py + 22)} Z', 'sand', INK, 1.6)
        o += flask(px, py + 21, .45, c, bubbles=False)
    return at(x, y, s, o)


def video_screen(x, y, w=100, h=64, color='sky'):
    o = rect(x, y, w, h, 'pants', 3) + rect(x + 5, y + 5, w - 10, h - 16, color, 1.5, None)
    cx, cy = x + w / 2, y + (h - 11) / 2 + 2
    o += circle((cx, cy), 11, 'paper', INK, 1.6) + poly([(cx - 3.5, cy - 6), (cx + 6, cy), (cx - 3.5, cy + 6)], 'rose', w=1.2)
    o += line(f'M{f(x + 8)} {f(y + h - 6)} L{f(x + w - 8)} {f(y + h - 6)}', 1.6, 'paper') + circle((x + w * .35, y + h - 6), 2.4, 'sun', None)
    return o


def market_stall(x, y, w=110, color='rose'):
    """دکه با سایبان راه‌راه؛ x,y وسط پایین."""
    o = rect(x - w / 2, y - 40, w, 40, 'sand', 2)
    o += line(f'M{f(x - w / 2 + 6)} {f(y - 40)} L{f(x - w / 2 + 6)} {f(y - 104)} M{f(x + w / 2 - 6)} {f(y - 40)} L{f(x + w / 2 - 6)} {f(y - 104)}', 2.4)
    n = 6
    sw = w / n
    for i in range(n):
        c = color if i % 2 == 0 else 'paper'
        o += path(f'M{f(x - w / 2 + i * sw)} {f(y - 108)} L{f(x - w / 2 + (i + 1) * sw)} {f(y - 108)} L{f(x - w / 2 + (i + 1) * sw)} {f(y - 96)} Q{f(x - w / 2 + (i + .5) * sw)} {f(y - 88)} {f(x - w / 2 + i * sw)} {f(y - 96)} Z', c, INK, 1.6)
    return o
