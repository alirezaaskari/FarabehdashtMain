"""تصویرهای حالت خالی: کوچک و آرام، بالای پیام «هنوز چیزی نیست». viewBox ۲۰۰×۱۵۰، زمین در y=۱۴۰."""
import math
from engine import Person, wash, ground, rect, line, circle, path, poly, ellipse, group, seg, f, INK
from registry import art
import props as p

VB = '0 0 200 150'
G = 140


# ------------------------------------------------------------ helpers (this file only)

def reach(who, x, s, targets, bends=None, **kw):
    """آدمی می‌سازد که دست‌هایش به نقطه‌های داده‌شده (مختصات صحنه) برسد."""
    y = kw.pop('y', G)
    fig = Person(who, x, y, s, **kw)
    arms = list(kw.pop('arms', ((-12, -6), (12, 6))))
    bends = bends or {}
    for i, (tx, ty) in targets.items():
        S = fig.sh[i]
        T = ((tx - fig.x) / s, (ty - fig.oy) / s)
        dx, dy = T[0] - S[0], T[1] - S[1]
        d = min(math.hypot(dx, dy), 56.5)
        d = max(d, 4)
        th = math.degrees(math.atan2(dx, dy))
        c = (30 ** 2 + d ** 2 - 27 ** 2) / (2 * 30 * d)
        a = math.degrees(math.acos(max(-1, min(1, c))))
        up = th + a * bends.get(i, 1)
        E = seg(S, up, 30)
        fa = math.degrees(math.atan2(T[0] - E[0], T[1] - E[1]))
        arms[i] = (up, fa)
    return Person(who, x, y, s, arms=tuple(arms), **kw)


def mark(x, y, ch, size=16, op=.8, rot=0):
    t = f' transform="rotate({f(rot)} {f(x)} {f(y)})"' if rot else ''
    return (f'<text x="{f(x)}" y="{f(y)}" font-size="{f(size)}" font-weight="700" text-anchor="middle" '
            f'fill="currentColor" opacity="{op}"{t}>{ch}</text>')


def dashed(d, w=1.4, op=.55, c=INK):
    return path(d, None, c, w, op, ' stroke-dasharray="3 3.5"')


def at(x, y, s, inner, rot=0):
    r = f' rotate({f(rot)})' if rot else ''
    return group(inner, f'translate({f(x)} {f(y)}){r} scale({f(s)})')


def shade(cx, cy, rx, ry=3):
    return ellipse((cx, cy), rx, ry, INK, None, op=.1)


def moth(x, y, s=1, rot=0):
    o = ellipse((-5, -3), 6, 4.2, 'sand', INK, 1.3, rot=-30) + ellipse((5, -3), 6, 4.2, 'sand', INK, 1.3, rot=30)
    o += ellipse((-4, 2), 3.6, 2.6, 'sand', INK, 1.2, rot=20) + ellipse((4, 2), 3.6, 2.6, 'sand', INK, 1.2, rot=-20)
    o += ellipse((0, 0), 1.8, 5, 'hair', INK, 1.1)
    o += line('M-.6 -4.6 Q-3 -9 -5 -9.5 M.6 -4.6 Q3 -9 5 -9.5', 1)
    return at(x, y, s, o, rot)


def cobweb(x, y, s=1):
    """تار عنکبوت گوشه بالا-راست؛ لنگر گوشه."""
    o = ''
    for a in (180, 205, 232, 260):
        e = seg((0, 0), a + 90, 20)
        o += line(f'M0 0 L{f(e[0])} {f(e[1])}', .9, op=.6)
    for r in (7, 13, 19):
        pts = [seg((0, 0), a + 90, r) for a in (180, 205, 232, 260)]
        d = f'M{f(pts[0][0])} {f(pts[0][1])}' + ''.join(
            f' Q{f((pts[k][0] + pts[k + 1][0]) / 2 * .86)} {f((pts[k][1] + pts[k + 1][1]) / 2 * .86)} {f(pts[k + 1][0])} {f(pts[k + 1][1])}'
            for k in range(3))
        o += line(d, .9, op=.6)
    return at(x, y, s, o)


def dust(x, y, s=1):
    o = circle((0, 0), 3.2, 'paper', INK, 1.1, op=.7) + circle((5, -2), 2.4, 'paper', INK, 1.1, op=.6) + circle((9, 1), 1.6, 'paper', INK, 1, op=.5)
    return at(x, y, s, o)


def round_tag(x, y, s=1, color='sun', rot=0):
    """برچسب گرد آویزان؛ لنگر سر نخ."""
    o = line('M0 0 Q3 5 0 10', 1.3) + circle((0, 24), 14, color) + circle((0, 14), 2.6, 'paper', INK, 1.3)
    o += circle((0, 24), 9.5, None, INK, 1, op=.35)
    return at(x, y, s, o, rot)


def open_toolbox(x, y, s=1, color='rose'):
    o = path('M-30 -22 L-26 -44 L34 -44 L30 -22 Z', color)
    o += path('M-6 -40 L-6 -46 Q-6 -48 -4 -48 L12 -48 Q14 -48 14 -46 L14 -40', None, INK, 2.2)
    o += rect(-28, -22, 56, 6, INK, 1, None, op=.28)
    o += path('M-30 -22 L30 -22 L28 0 L-28 0 Z', color)
    o += line('M-30 -14 L30 -14', 1.5) + rect(-4, -12, 8, 5, 'sun', 1, sw=1.3)
    return at(x, y, s, o)


def jar(x, y, s=1):
    o = path('M-16 -40 Q-20 -36 -20 -28 L-20 -4 Q-20 0 -16 0 L16 0 Q20 0 20 -4 L20 -28 Q20 -36 16 -40 Z', 'paper')
    o += path('M-20 -8 L20 -8 L20 -4 Q20 0 16 0 L-16 0 Q-20 0 -20 -4 Z', 'sky', None, op=.35)
    o += line('M-14 -30 L-14 -10', 2.4, 'paper') + line('M-14 -30 L-14 -12', 1.2, 'sky', .8)
    o += rect(-15, -48, 30, 9, 'sand', 2)
    o += rect(-6, -46, 12, 3, INK, 1, None, op=.8)
    return at(x, y, s, o)


def snail(x, y, s=1):
    o = path('M-14 0 Q-16 -4 -10 -4 L10 -4 Q14 -6 15 -14 L17 -14 Q18 -4 14 0 Z', 'sand')
    o += line('M14 -12 L12 -20 M16 -12 L18 -20', 1.2) + circle((12, -20.5), 1.4, INK, None) + circle((18, -20.5), 1.4, INK, None)
    o += circle((-2, -12), 9, 'sun') + path('M-2 -12 Q-2 -16 1 -15 Q4 -13 1 -9 Q-3 -6 -6 -10 Q-9 -16 -3 -19', None, INK, 1.3)
    return at(x, y, s, o)


def pen(x, y, s=1, rot=0, color='grape'):
    o = rect(-3, -34, 6, 30, color, 2) + path('M-3 -4 L3 -4 L0 3 Z', 'paper', INK, 1.4) + line('M3 -30 L5 -30 L5 -20', 1.3)
    return at(x, y, s, o, rot)


def clock(x, y, s=1, h=-60, m=0):
    o = circle((0, 0), 14, 'paper', INK, 2)
    for i in range(12):
        a = seg((0, 0), i * 30, 11)
        b = seg((0, 0), i * 30, 12.5 if i % 3 else 9.5)
        o += line(f'M{f(a[0])} {f(a[1])} L{f(b[0])} {f(b[1])}', 1 if i % 3 else 1.6)
    hh, mm = seg((0, 0), 180 - h, 6.5), seg((0, 0), 180 - m, 9.5)
    o += line(f'M0 0 L{f(hh[0])} {f(hh[1])}', 2.2) + line(f'M0 0 L{f(mm[0])} {f(mm[1])}', 1.6) + circle((0, 0), 1.6, 'rose', INK, 1)
    return at(x, y, s, o)


def sleeping_bell(x, y, s=1, rot=0):
    o = p.bell(0, 0, 1, 0, ring=False)
    o += line('M-8 -8 Q-5 -5.5 -2 -8 M2 -8 Q5 -5.5 8 -8', 1.5) + line('M-2.5 -1 Q0 1 2.5 -1', 1.3)
    o += ellipse((-8, -3), 2.4, 1.5, 'rose', None, op=.7) + ellipse((8, -3), 2.4, 1.5, 'rose', None, op=.7)
    return at(x, y, s, o, rot)


def tumbleweed(x, y, r=10):
    o = circle((x, y), r, None, INK, 1.2, op=.75)
    for k, rot in enumerate((0, 50, 110)):
        o += ellipse((x, y), r * .8, r * .45, None, INK, 1, op=.6, rot=rot)
    o += line(f'M{f(x - r - 14)} {f(y - 3)} L{f(x - r - 4)} {f(y - 3)} M{f(x - r - 18)} {f(y + 3)} L{f(x - r - 6)} {f(y + 3)}', 1.2, op=.5)
    return o


def lens_empty(x, y, s=1, rot=0):
    """ذره‌بین با شیشه روشن (بدون رنگ آسمانی) برای نشان دادن «هیچ»."""
    o = limb_handle() + circle((0, 0), 12, 'paper', INK, 2.4) + circle((0, 0), 12, 'sky', None, op=.25)
    o += path('M-6 -4 Q-5 -8 -1 -8', None, 'paper', 2)
    return at(x, y, s, o, rot)


def limb_handle():
    from engine import limb
    return limb([(9, 9), (22, 22)], 'vest', 5, 1.6)


def plus(x, y, r=8, color='sun'):
    return path(f'M{f(x - 3)} {f(y - r)} L{f(x + 3)} {f(y - r)} L{f(x + 3)} {f(y - 3)} L{f(x + r)} {f(y - 3)} L{f(x + r)} {f(y + 3)} '
                f'L{f(x + 3)} {f(y + 3)} L{f(x + 3)} {f(y + r)} L{f(x - 3)} {f(y + r)} L{f(x - 3)} {f(y + 3)} L{f(x - r)} {f(y + 3)} '
                f'L{f(x - r)} {f(y - 3)} L{f(x - 3)} {f(y - 3)} Z', color, INK, 1.5)


def price_tag(x, y, s=1, rot=0):
    o = line('M0 0 L0 6', 1.2) + path('M-6 6 L6 6 L6 20 L0 25 L-6 20 Z', 'sun', INK, 1.4) + circle((0, 10), 1.4, 'paper', INK, 1)
    return at(x, y, s, o, rot)


# ------------------------------------------------------------ scenes

@art('empty-projects-compare', VB)
def _():
    w = wash((100, 80), 76, 50, 'grape', .28)
    b = ground(10, 190, G) + line('M18 34 L112 34', 2.2) + line('M18 30 L18 38 M112 30 L112 38', 2)
    b += round_tag(46, 34, 1, 'sun', 6) + round_tag(84, 34, 1, 'sky', -8)
    fg = reach('f', 150, .6, {0: (116, 88)}, {0: -1}, expr='happy', look=-.7, outfit='shirt', top='grape', hat=False, hair_down=True, front=0)
    b += fg.draw()
    return w, b


@art('empty-projects', VB)
def _():
    w = wash((96, 84), 70, 48, 'sun', .3)
    b = ground(10, 190, G) + shade(96, 139, 38)
    b += p.clipboard(92, 88, 2.2, -5, ticks=0, fill='sand')
    b += p.pencil(150, 139, 1, 78, 46, 'sun') + dust(40, 130, .9)
    return w, b


@art('empty-equipment', VB)
def _():
    w = wash((100, 96), 74, 42, 'rose', .28)
    b = ground(10, 190, G) + shade(100, 139, 50) + open_toolbox(100, 139, 1.5, 'rose')
    b += line('M78 56 Q84 50 90 54', 1.2, op=.5) + mark(108, 60, '?', 16, .5, 12)
    return w, b


@art('empty-calendar', VB)
def _():
    w = wash((80, 70), 64, 50, 'rose', .26) + wash((156, 110), 34, 26, 'leaf', .3)
    b = ground(10, 190, G) + circle((74, 20), 2, INK, None) + line('M74 20 L60 30 M74 20 L88 30', 1.1, op=.7)
    b += p.calendar(40, 30, 1.15, blank=True)
    b += p.plant(156, 140, 1.2, 'vest') + p.mug(124, 140, .9, 'sky', False)
    return w, b


@art('empty-stations', VB)
def _():
    w = wash((96, 100), 80, 38, 'leaf', .3)
    b = ground(10, 190, G)
    b += p.flag(40, 138, 1.4, 'vest', down=True) + p.flag(78, 134, 1.3, 'sky', down=True) + p.flag(104, 139, 1.2, 'rose', down=True)
    for hx in (30, 62, 94):
        b += ellipse((hx, 142), 4, 1.3, INK, None, op=.35)
    m = Person('m', 150, G, .6, arms=((10, 4), (40, 170)), expr='oops', look=-.6, legs=((-4, -2), (6, 2)))
    b += m.draw()
    return w, b


@art('empty-instructor-courses', VB)
def _():
    w = wash((70, 76), 62, 46, 'sky', .28)
    b = ground(10, 190, G) + p.whiteboard(18, 38, 92, 56, content=None, legs=False)
    b += line('M32 94 L24 140 M96 94 L104 140', 2.2)
    fg = reach('f', 150, .6, {0: (120, 72)}, {0: -1}, expr='determined', look=-.7, outfit='jacket', top='rose', hat=False, front=0)
    h = fg.hand_at(0)
    b += fg.shadow() + fg.back() + pen(h[0] - 2, h[1] + 2, .7, -70, 'leaf') + fg.front_()
    return w, b


def _():
    w = wash((100, 92), 70, 44, 'grape', .28)
    b = ground(10, 190, G) + shade(100, 139, 50)
    o = p.register(0, 0, 1.6, empty=True)
    b += at(100, 128, 1, o) + rect(68, 128, 64, 12, 'paper', 1.5) + rect(72, 131, 56, 6, INK, 1, None, op=.18)
    b += cobweb(146, 90, 1.1)
    return w, b


@art('empty-courses-search', VB)
def _():
    w = wash((104, 104), 80, 34, 'sun', .28)
    b = ground(10, 190, G)
    for i, c in enumerate(('sky', 'rose', 'leaf', 'sun')):
        b += p.chair(44 + i * 38, G, .62, 1, c)
    b += lens_empty(110, 46, 1.5, 0)
    return w, b


@art('empty-courses-mine', VB)
def _():
    w = wash((100, 92), 60, 44, 'sky', .3)
    b = ground(10, 190, G) + shade(100, 139, 36)
    b += p.backpack(100, 139, 1.6, 'sky', empty=True)
    b += path('M76 76 Q66 62 86 58 L100 70', 'sky', INK, 1.8)
    b += dust(126, 52, 1) + line('M122 58 Q118 62 116 66', 1, op=.4)
    return w, b


@art('empty-notifications', VB)
def _():
    w = wash((96, 96), 66, 44, 'sun', .3)
    b = ground(10, 190, G) + ellipse((96, 134), 40, 7, 'rose', INK, 1.8)
    b += sleeping_bell(94, 112, 1.5, -18) + p.zzz(128, 70, 1.3)
    return w, b


@art('empty-search', VB)
def _():
    w = wash((104, 88), 78, 44, 'sky', .26)
    b = ground(10, 190, G) + shade(90, 139, 30)
    b += lens_empty(70, 104, 2, -10) + mark(70, 110, '?', 15, .45)
    b += tumbleweed(160, 128, 11)
    return w, b


@art('empty-legal', VB)
def _():
    w = wash((96, 80), 60, 50, 'leaf', .28)
    b = ground(10, 190, G) + shade(90, 139, 36)
    b += p.paper(88, 94, 2.2, -4, lines=0)
    b += line('M72 72 L100 72 M72 82 L92 82', 1.3, op=.25)
    b += clock(122, 116, 1.3, -60, 0)
    return w, b


@art('empty-wallet', VB)
def _():
    w = wash((90, 100), 70, 38, 'vest', .25)
    b = ground(10, 190, G) + shade(86, 139, 42)
    o = path('M-34 -2 L-30 -30 L2 -24 L0 0 Z', 'vest') + path('M0 0 L2 -24 L34 -30 L36 -2 Z', 'vest')
    o += path('M-28 -24 L-2 -20 L-2 -6 L-28 -8 Z', INK, None, op=.2) + path('M6 -20 L30 -24 L30 -10 L6 -8 Z', INK, None, op=.12)
    o += rect(-36, -2, 72, 6, 'vest', 2)
    b += at(86, 134, 1.3, o)
    b += dashed('M96 96 Q110 70 132 74 Q150 78 146 56') + moth(152, 46, 1.2, 14)
    return w, b


@art('empty-report-sources', VB)
def _():
    w = wash((100, 90), 64, 46, 'sun', .28)
    b = ground(10, 190, G) + shade(100, 139, 50)
    b += at(100, 139, 1.9, path('M-24 -26 L-8 -26 L-4 -22 L24 -22 L24 0 L-24 0 Z', 'sun') + rect(-22, -20, 44, 6, INK, 1, None, op=.2)
                         + path('M-26 -12 L26 -12 L22 0 L-22 0 Z', 'sun', INK, 1.6))
    b += dust(128, 74, 1)
    return w, b


@art('empty-report-results', VB)
def _():
    w = wash((100, 80), 76, 48, 'sky', .26)
    b = ground(10, 190, G)
    b += line('M44 24 L44 124 L166 124', 2.2) + path('M40 30 L44 22 L48 30', None, INK, 2) + path('M160 120 L168 124 L160 128', None, INK, 2)
    for yy in (50, 76, 102):
        b += dashed(f'M50 {yy} L160 {yy}', 1, .35)
    b += p.pencil(150, 139, .8, 72, 40, 'leaf') + mark(104, 96, '?', 18, .35)
    return w, b


@art('empty-reports', VB)
def _():
    w = wash((92, 92), 70, 44, 'grape', .26)
    b = ground(10, 190, G)
    for i in range(5):
        b += rect(52 + (i % 2) * 4 - 2 * i, 126 - i * 7, 76, 8, 'paper', 1.2, sw=1.6)
    b += rect(46, 90, 80, 8, 'paper', 1.2, sw=1.6, rot=-3)
    b += pen(146, 138, 1.1, 76, 'sky')
    return w, b


@art('empty-encyclopedia-filter', VB)
def _():
    w = wash((100, 82), 60, 54, 'leaf', .28)
    b = ground(10, 190, G) + line('M60 140 L60 30 M50 140 L74 140', 2.4) + line('M60 52 L84 52', 2)
    b += p.funnel(100, 58, 1.3)
    b += circle((88, 26), 3, 'rose', INK, 1.2) + circle((100, 20), 3, 'sky', INK, 1.2) + circle((110, 28), 3, 'sun', INK, 1.2)
    b += p.beaker(100, 140, 1.2, 'paper', 30)
    b += dashed('M100 76 L100 94', 1.3, .4) + mark(134, 116, '?', 16, .5)
    return w, b


@art('empty-writing', VB)
def _():
    w = wash((92, 84), 66, 50, 'rose', .24)
    b = ground(10, 190, G) + shade(88, 139, 40)
    b += at(86, 133, 1, poly([(-40, 0), (34, 0), (44, -76), (-30, -80)], 'paper', w=2))
    b += p.pencil(138, 110, 1, 18, 44, 'sun')
    b += circle((166, 134), 6, 'paper', INK, 1.4) + line('M162 132 L168 136 M164 130 L170 134', 1, op=.6)
    return w, b


@art('empty-purchases', VB)
def _():
    w = wash((96, 96), 72, 42, 'leaf', .28)
    b = ground(10, 190, G)
    fg = reach('f', 70, .6, {1: (110, 96)}, {1: 1}, expr='curious', look=.8, outfit='jacket', top='sky', hat=False, lean=10, front=1)
    h = fg.hand_at(1)
    b += fg.shadow() + fg.back() + p.bag(h[0] + 14, 140, 1.3, 'leaf', items=False) + fg.front_()
    b += mark(140, 68, '?', 16, .6)
    return w, b


@art('empty-shop', VB)
def _():
    w = wash((100, 70), 72, 44, 'rose', .24)
    b = ground(10, 190, G) + p.shelf(100, 106, 120, 3, 34, items=False)
    b += line('M44 40 L44 110 M156 40 L156 110', 2) + price_tag(80, 110, 1, -8) + dust(118, 68, .8)
    b += p.box(158, 140, .7, False, 'sand', False)
    return w, b


@art('empty-vendor-products', VB)
def _():
    w = wash((100, 96), 62, 42, 'sand', .32)
    b = ground(10, 190, G) + shade(100, 139, 48)
    b += at(100, 139, 1.7, path('M-22 -30 L-30 -40 L-6 -40 L0 -30', 'sand', INK, 1.8) + path('M22 -30 L30 -40 L6 -40 L0 -30', 'sand', INK, 1.8)
                         + rect(-22, -30, 44, 5, INK, 0, None, op=.25) + rect(-22, -30, 44, 30, 'sand', 2)
                         + path('M-22 -30 L-30 -20 L-22 -18', 'sand', INK, 1.6) + path('M22 -30 L30 -20 L22 -18', 'sand', INK, 1.6))
    b += at(100, 139, 1.7, rect(-22, -30, 44, 30, 'sand', 2) + rect(-10, -16, 20, 8, 'paper', 1, sw=1.2))
    b += mark(100, 56, '?', 16, .55)
    return w, b


@art('empty-settlement', VB)
def _():
    w = wash((104, 84), 70, 50, 'rose', .26)
    b = ground(10, 190, G)
    m = reach('m', 76, .6, {0: (106, 70), 1: (114, 62)}, {0: -1, 1: 1}, expr='focus', look=.7, outfit='jacket', top='grape', hat=False)
    b += m.shadow() + m.back() + at(122, 58, 1.05, p.piggy(0, 0, 1), 180) + m.front_()
    b += line('M146 60 Q152 70 146 80 M154 56 Q162 70 154 84', 1.4, op=.6)
    b += dashed('M122 104 L122 130', 1.2, .35) + mark(150, 124, '?', 14, .5)
    return w, b


@art('empty-vendor-sales', VB)
def _():
    w = wash((100, 80), 76, 46, 'leaf', .26)
    b = ground(10, 190, G) + rect(26, 24, 148, 92, 'paper', 3) + line('M40 34 L40 104 L162 104', 1.6)
    b += line('M44 98 L158 98', 2.4, 'vest') + snail(118, 96, .7)
    b += line('M56 116 L48 140 M144 116 L152 140', 2.2)
    return w, b


@art('empty-cart', VB)
def _():
    w = wash((110, 100), 76, 36, 'sky', .28)
    b = ground(10, 190, G) + p.cart(126, 140, 1.25, items=False)
    m = reach('m', 50, .6, {1: (84, 80)}, {1: 1}, expr='wink', look=.7, outfit='shirt', top='sky', hat=False, lean=6,
              legs=((-14, -6), (14, 4)))
    b += m.draw() + line('M150 86 Q158 80 166 86', 1.2, op=.5)
    return w, b


@art('empty-chem-compare-pick', VB)
def _():
    w = wash((100, 96), 60, 40, 'sky', .28)
    b = ground(10, 190, G) + shade(100, 139, 44)
    b += p.test_tubes(100, 140, 1.7, (None, None, None))
    b += p.sparkle(152, 56, .8, 'sky')
    return w, b


@art('empty-chem-compare-need', VB)
def _():
    w = wash((90, 90), 72, 44, 'leaf', .28)
    b = ground(10, 190, G)
    fg = reach('f', 56, .6, {1: (84, 96)}, {1: 1}, expr='worried', look=.7, outfit='coat', hat=False, tilt=6)
    h = fg.hand_at(1)
    b += fg.shadow() + fg.back() + p.flask(h[0] + 2, h[1] + 12, .7, 'leaf', bubbles=False) + fg.front_()
    b += plus(116, 96, 8) + dashed('M140 128 L140 110 L150 96 L150 80 L162 80 L162 96 L172 110 L172 128 Z', 1.4, .45)
    return w, b


@art('empty-chemicals', VB)
def _():
    w = wash((96, 96), 60, 42, 'grape', .26)
    b = ground(10, 190, G) + shade(88, 139, 26)
    b += p.flask(88, 139, 1.5, 'paper', bubbles=False)
    b += lens_empty(122, 88, 1.3, 10)
    return w, b


@art('empty-chem-limits', VB)
def _():
    w = wash((100, 80), 60, 50, 'sun', .26)
    b = ground(10, 190, G) + p.pipe(12, 116, 188, 116, 'sand') + p.pipe(100, 116, 100, 96, 'sand')
    b += p.gauge(100, 60, 1.8, blank=True)
    return w, b


@art('empty-advisor', VB)
def _():
    w = wash((96, 76), 70, 52, 'sun', .26)
    b = ground(10, 190, G) + p.signpost(66, 140, 1.25, blank=True, colors=('sun', 'sky', 'leaf'))
    m = Person('m', 146, G, .6, arms=((-14, -8), (10, 4)), expr='surprised', look=-.8, tilt=-10, outfit='jacket', top='leaf')
    b += m.draw()
    return w, b


@art('empty-tools', VB)
def _():
    w = wash((100, 76), 72, 46, 'vest', .22)
    b = ground(10, 190, G) + p.pegboard(40, 26, 120, 80)
    b += line('M46 106 L40 140 M154 106 L160 140', 2.2)
    b += dashed('M62 40 L62 92 M54 40 L70 40 L70 48 L54 48 Z', 1.3, .5)
    b += dashed('M96 42 Q92 52 100 53 Q108 52 104 42 M100 53 L100 92', 1.3, .5)
    b += dashed('M130 40 L130 70 L126 76 L134 76 L130 70 M126 80 L134 80 L132 94 L128 94 Z', 1.3, .5)
    b += circle((62, 36), 2, INK, None) + circle((100, 38), 2, INK, None) + circle((130, 36), 2, INK, None)
    return w, b


@art('empty-calculations', VB)
def _():
    w = wash((92, 92), 64, 44, 'leaf', .26)
    b = ground(10, 190, G) + shade(100, 139, 50)
    b += p.paper(128, 118, 1.1, 12, lines=0) + p.calculator(80, 112, 1.4, blank=True)
    b += p.pencil(150, 139, .8, 80, 36, 'rose')
    return w, b


@art('empty-tool-result', VB)
def _():
    w = wash((100, 90), 76, 44, 'sun', .28)
    b = ground(10, 190, G)
    m = reach('m', 136, .6, {1: (130, 66)}, {1: -1}, expr='think', look=-.6, outfit='shirt', top='sand', hat=False,
              front=1)
    b += m.draw()
    b += at(64, 108, 1.6, rect(-14, -20, 28, 40, 'pants', 3.5) + rect(-10, -16, 20, 9, 'leaf', 1.5, sw=1.4)
                        + ''.join(rect(-9 + c * 7, -3 + r * 7, 5, 5, 'paper', 1, None) for r in range(3) for c in range(3)))
    b += mark(64, 88, '?', 13, .85) + shade(64, 139, 26)
    return w, b


@art('empty-expert-queue', VB)
def _():
    w = wash((110, 90), 76, 44, 'grape', .26)
    b = ground(10, 190, G)
    fg0 = Person('f', 62, 112, .6, legs=((88, 0), (82, -2)), anchor='pelvis', lean=-10)
    hd = fg0.head_at()
    fg = reach('f', 62, .6, {0: (hd[0] - 10, hd[1] - 4), 1: (hd[0] + 14, 108)}, {0: -1, 1: 1}, y=112, legs=((88, 0), (82, -2)),
               anchor='pelvis', lean=-10, expr='calm', look=.4, outfit='shirt', top='leaf', hat=False, hair_down=True)
    b += p.chair(62, G, .62, 1, 'rose') + fg.back() + fg.front_()
    b += p.desk(152, 108, 64, 32) + p.tray(144, 108, .9, 0) + p.mug(172, 108, .8, 'sun')
    return w, b


@art('empty-expert-index', VB)
def _():
    w = wash((100, 70), 66, 46, 'grape', .26)
    b = ground(10, 190, G) + p.speech(52, 26, 96, 56, 'paper', -1, None)
    b += p.plant(158, 140, .9, 'grape') + p.sparkle(150, 22, .7)
    return w, b


@art('empty-expert-mine', VB)
def _():
    w = wash((110, 76), 60, 44, 'sky', .26)
    b = ground(10, 190, G) + p.speech(92, 30, 74, 44, 'sun', -1, 'dots')
    b += p.phone(64, 124, 1.2, -8, 'sky', None) + shade(64, 139, 14, 2)
    return w, b


@art('empty-sales-course', VB)
def _():
    w = wash((100, 90), 56, 48, 'sun', .28)
    b = ground(10, 190, G) + shade(100, 139, 30)
    b += jar(100, 139, 1.6) + dust(122, 64, .8)
    return w, b
