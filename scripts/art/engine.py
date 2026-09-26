"""
موتور تصویرهای نقاشی‌گونه سایت: شخصیت‌ها (آقا و خانم کارشناس)، اشیا و پس‌زمینه.

رنگ‌ها با نام نمادین نوشته می‌شوند ('ink'، 'paper'، 'sun'…) و هنگام خروجی به
currentColor یا متغیر توکن (var(--fbh-art-*)) تبدیل می‌شوند؛ هیچ هگزی در خروجی نیست.
زاویه‌ها مطلق‌اند: ۰ = رو به پایین، ۹۰ = به راست، ‎-۹۰ = به چپ، ۱۸۰ = بالا.
"""
import math

INK = 'ink'
FACE = 'hair'  # چشم و دهان همیشه تیره‌اند، حتی وقتی خط در حالت تاریک روشن می‌شود
HEAD = 1.2  # سر کمی بزرگ‌تر تا حالت چهره در اندازه کوچک هم خوانا بماند
W = 2.0  # ضخامت خط جوهر


def col(c):
    if c in (None, 'none'):
        return 'none'
    if c == INK:
        return 'currentColor'
    if c == 'paper':
        return 'var(--fbh-surface)'
    return f'var(--fbh-art-{c})'


def f(n):
    s = f'{n:.1f}'
    return s[:-2] if s.endswith('.0') else s


def pt(p):
    return f'{f(p[0])} {f(p[1])}'


def seg(p, ang, length):
    a = math.radians(ang)
    return (p[0] + math.sin(a) * length, p[1] + math.cos(a) * length)


def add(p, q, k=1.0):
    return (p[0] + q[0] * k, p[1] + q[1] * k)


def mid(p, q, t=.5):
    return (p[0] + (q[0] - p[0]) * t, p[1] + (q[1] - p[1]) * t)


# ---------------------------------------------------------------- primitives

def path(d, fill=None, stroke=INK, w=W, op=None, extra=''):
    o = f' opacity="{op}"' if op is not None else ''
    s = f' stroke="{col(stroke)}" stroke-width="{f(w)}" stroke-linecap="round" stroke-linejoin="round"' if stroke else ''
    return f'<path d="{d}" fill="{col(fill)}"{s}{o}{extra}/>'


def line(d, w=W, c=INK, op=None):
    return path(d, None, c, w, op)


def poly(points, fill=None, close=True, w=W, stroke=INK, op=None):
    d = 'M' + ' L'.join(pt(p) for p in points) + (' Z' if close else '')
    return path(d, fill, stroke, w, op)


def circle(c, r, fill=None, stroke=INK, w=W, op=None):
    o = f' opacity="{op}"' if op is not None else ''
    s = f' stroke="{col(stroke)}" stroke-width="{f(w)}"' if stroke else ''
    return f'<circle cx="{f(c[0])}" cy="{f(c[1])}" r="{f(r)}" fill="{col(fill)}"{s}{o}/>'


def ellipse(c, rx, ry, fill=None, stroke=INK, w=W, op=None, rot=0):
    o = f' opacity="{op}"' if op is not None else ''
    s = f' stroke="{col(stroke)}" stroke-width="{f(w)}"' if stroke else ''
    t = f' transform="rotate({f(rot)} {pt(c)})"' if rot else ''
    return f'<ellipse cx="{f(c[0])}" cy="{f(c[1])}" rx="{f(rx)}" ry="{f(ry)}" fill="{col(fill)}"{s}{o}{t}/>'


def rect(x, y, w, h, fill='paper', r=2, stroke=INK, sw=W, op=None, rot=0):
    o = f' opacity="{op}"' if op is not None else ''
    s = f' stroke="{col(stroke)}" stroke-width="{f(sw)}" stroke-linejoin="round"' if stroke else ''
    t = f' transform="rotate({f(rot)} {f(x + w / 2)} {f(y + h / 2)})"' if rot else ''
    return f'<rect x="{f(x)}" y="{f(y)}" width="{f(w)}" height="{f(h)}" rx="{f(r)}" fill="{col(fill)}"{s}{o}{t}/>'


def limb(points, color, width, w=1.7):
    """اندام یا شیء لوله‌ای: خط جوهر پهن و رنگ روی آن، پس لبه تمیز می‌ماند."""
    d = 'M' + ' L'.join(pt(p) for p in points)
    return line(d, width + 2 * w, INK) + line(d, width, color)


def group(inner, transform=None, op=None):
    t = f' transform="{transform}"' if transform else ''
    o = f' opacity="{op}"' if op is not None else ''
    return f'<g{t}{o}>{inner}</g>'


# ---------------------------------------------------------------- the people

EXPR = {
    # brows, eyes, mouth
    'smile': ('soft', 'dot', 'smile'),
    'happy': ('up', 'arc', 'open'),
    'laugh': ('up', 'arc', 'laugh'),
    'surprised': ('high', 'wide', 'o'),
    'think': ('one', 'side', 'side'),
    'focus': ('down', 'squint', 'flat'),
    'worried': ('sad', 'dot', 'wavy'),
    'sad': ('sad', 'dot', 'frown'),
    'wink': ('soft', 'wink', 'grin'),
    'calm': ('soft', 'half', 'smile'),
    'proud': ('up', 'arc', 'smile'),
    'call': ('up', 'dot', 'shout'),
    'oops': ('sad', 'wide', 'teeth'),
    'sleepy': ('flat', 'closed', 'flat'),
    'curious': ('high', 'dot', 'small'),
    'determined': ('down', 'dot', 'grin'),
}


class Person:
    """
    who: 'm' (آقا: ریش کوتاه، کلاه زرد، جلیقه نارنجی) یا 'f' (خانم: موی دم‌اسبی،
    کلاه سفید، جلیقه سبز فسفری). legs و arms دو جفت زاویه (بالا، پایین) برای
    اندام چپ و راست تصویرند. look بین ‎-۱ و ۱ چهره را به چپ یا راست می‌چرخاند.
    """

    def __init__(self, who, x, ground, s=1.0, legs=((-3, -1), (3, 1)), arms=((-12, -6), (12, 6)),
                 lean=0, tilt=0, expr='smile', look=0, outfit='vest', hat=True, top=None, vest=None,
                 pants='pants', anchor='feet', front=1, hair_down=False, sleeves=None, blush=True):
        self.who, self.s, self.x, self.ground = who, s, x, ground
        self.expr, self.look, self.outfit, self.hat, self.front = expr, look, outfit, hat, front
        self.top = top or ('shirt' if who == 'm' else 'shirt2')
        self.vest = vest or ('vest' if who == 'm' else 'lime')
        self.pants, self.hair_down, self.blush = pants, hair_down, blush
        self.sleeves = sleeves or ('paper' if outfit == 'coat' else self.top)
        f_ = who == 'f'
        self.sw, self.hw = (14.5, 12.5) if f_ else (16.5, 12)
        self.limbw = 7.5 if f_ else 8.5
        self.legw = 10.5 if f_ else 12
        P = (0.0, 0.0)
        self.lean, self.tilt = lean, tilt
        up = 180 - lean
        self.sc = seg(P, up, 58)
        r = (math.cos(math.radians(lean)), math.sin(math.radians(lean)))
        self.sh = [add(add(self.sc, r, -self.sw), (0, 3)), add(add(self.sc, r, self.sw), (0, 3))]
        self.shl = [add(self.sc, r, -self.sw - 3), add(self.sc, r, self.sw + 3)]
        self.hip = [add(P, r, -self.hw * .75), add(P, r, self.hw * .75)]
        self.neck = seg(self.sc, 180 - (lean + tilt * .5), 7)
        self.head = seg(self.sc, 180 - (lean + tilt), 28.5)
        self.knee, self.foot = [], []
        for h, (a1, a2) in zip(self.hip, legs):
            k = seg(h, a1, 39)
            self.knee.append(k)
            self.foot.append(seg(k, a2, 37))
        self.elbow, self.hand = [], []
        for sp, (a1, a2) in zip(self.sh, arms):
            e = seg(sp, a1, 30)
            self.elbow.append(e)
            self.hand.append(seg(e, a2, 27))
        low = max(self.foot[0][1], self.foot[1][1]) + 5
        self.oy = ground - (low if anchor == 'feet' else 0) * s

    # scene coordinates of a local point
    def at(self, p, dx=0, dy=0):
        return (self.x + p[0] * self.s + dx, self.oy + p[1] * self.s + dy)

    def hand_at(self, i, dx=0, dy=0):
        return self.at(self.hand[i], dx, dy)

    def head_at(self, dx=0, dy=0):
        return self.at(self.head, dx, dy)

    def _wrap(self, inner):
        return group(inner, f'translate({f(self.x)} {f(self.oy)}) scale({f(self.s)})')

    def shadow(self):
        cx = (self.foot[0][0] + self.foot[1][0]) / 2
        cy = max(self.foot[0][1], self.foot[1][1]) + 5
        return self._wrap(ellipse((cx, cy), 34, 4.5, INK, None, op=.1))

    # -- parts
    def _legs(self):
        out = ''
        for h, k, ft in zip(self.hip, self.knee, self.foot):
            out += limb([h, k, ft], self.pants, self.legw)
            face = 1 if (self.look >= 0) else -1
            if abs(ft[0] - k[0]) > 30:  # shin roughly horizontal: shoe points along it
                face = 1 if ft[0] > k[0] else -1
            c = (ft[0] + 4 * face, ft[1] + 1.5)
            out += ellipse(c, 10.5, 5.2, INK, None)
            out += ellipse((c[0] + 3 * face, c[1] - 1.6), 3.2, 1.2, 'paper', None, op=.55)
        return out

    def _torso(self):
        L_, R_ = self.shl
        hl, hr = self.hip
        bottom = 10 if self.outfit == 'coat' else 4
        lean = math.radians(self.lean)
        down = (math.sin(lean) * -1, math.cos(lean))
        hl2, hr2 = add(hl, down, bottom), add(hr, down, bottom)
        if self.outfit == 'coat':
            hl2, hr2 = add(hl2, (-4, 0)), add(hr2, (4, 0))
            hl2, hr2 = add(hl, (-self.hw * .5 - 4, 30)), add(hr, (self.hw * .5 + 4, 30))
        d = (f'M{pt(add(L_, (0, 3)))} Q{pt(add(mid(L_, self.sc, .2), (0, -3)))} {pt(mid(L_, R_))} '
             f'Q{pt(add(mid(R_, self.sc, .2), (0, -3)))} {pt(add(R_, (0, 3)))} '
             f'L{pt(hr2)} Q{pt(mid(hl2, hr2))} {pt(hl2)} Z')
        body = self.sleeves if self.outfit == 'coat' else self.top
        if self.outfit == 'coat':
            body = 'paper'
        out = path(d, body)
        nl, nr = mid(L_, R_, .36), mid(L_, R_, .64)
        v = seg(mid(L_, R_), 180 - self.lean + 180, 20)
        if self.outfit == 'vest':
            vd = (f'M{pt(add(L_, (1.5, 4)))} L{pt(nl)} L{pt(v)} L{pt(nr)} L{pt(add(R_, (-1.5, 4)))} '
                  f'L{pt(add(hr2, (-1, -1)))} Q{pt(mid(hl2, hr2))} {pt(add(hl2, (1, -1)))} Z')
            out += path(vd, self.vest, INK, 1.8)
            for t in (.62, .8):
                a, b = mid(L_, hl2, t), mid(R_, hr2, t)
                out += line(f'M{pt(add(a, (3, 0)))} L{pt(add(b, (-3, 0)))}', 3.2, 'paper', .95)
            out += line(f'M{pt(v)} L{pt(mid(hl2, hr2))}', 1.4)
        elif self.outfit == 'coat':
            out += line(f'M{pt(nl)} L{pt(v)} L{pt(nr)}', 1.8)
            out += poly([nl, add(v, (-4, 0)), add(nl, (-6, 12))], self.top, w=1.4)
            out += line(f'M{pt(v)} L{pt(add(mid(hl2, hr2), (0, 0)))}', 1.6)
            pk = mid(L_, hl2, .5)
            out += rect(pk[0] + 2, pk[1], 10, 9, 'paper', 1.5, sw=1.4)
            out += line(f'M{f(pk[0] + 5)} {f(pk[1] - 3)} L{f(pk[0] + 5)} {f(pk[1] + 4)}', 2, 'sky')
            out += line(f'M{pt(add(mid(hl2, hr2), (0, 0)))} L{pt(add(mid(self.hip[0], self.hip[1]), (0, 30)))}', 1.4)
        else:  # shirt / jacket
            out += poly([nl, add(mid(L_, R_), (0, 7)), nr], 'paper' if self.outfit == 'shirt' else self.top, close=False, w=1.6)
            out += line(f'M{pt(add(mid(L_, R_), (0, 7)))} L{pt(mid(hl2, hr2))}', 1.3, op=.6)
            if self.outfit == 'jacket':
                pk = mid(R_, hr2, .7)
                out += line(f'M{f(pk[0] - 12)} {f(pk[1])} L{f(pk[0] - 4)} {f(pk[1])}', 1.4)
        if self.outfit != 'coat':
            a, b = mid(L_, hl2, .93), mid(R_, hr2, .93)
            out += line(f'M{pt(a)} L{pt(b)}', 2.4, op=.85) if self.outfit == 'shirt' else ''
        return out

    def _arm(self, i):
        sp, e, h = self.sh[i], self.elbow[i], self.hand[i]
        out = limb([sp, e, h], self.sleeves, self.limbw)
        # cuff and hand
        cuff = mid(e, h, .82)
        out += circle(h, 5, 'skin', INK, 1.6)
        return out, cuff

    def _head(self):
        hx, hy = self.head
        L = self.look
        fx = hx + 6 * L
        f_ = self.who == 'f'
        out = ''
        neck = limb([self.sc, add(self.head, (0, 12))], 'skin', 9, 1.7)
        # woman's ponytail / long hair behind head
        if f_:
            side = -1 if L > 0 else 1
            if self.hair_down:
                out += path(f'M{f(hx - 16)} {f(hy - 6)} Q{f(hx - 20)} {f(hy + 18)} {f(hx - 14)} {f(hy + 28)} '
                            f'L{f(hx + 14)} {f(hy + 28)} Q{f(hx + 20)} {f(hy + 18)} {f(hx + 16)} {f(hy - 6)} Z', 'hair', INK, 1.6)
            else:
                bx = hx + 13 * side
                out += path(f'M{f(bx)} {f(hy - 12)} Q{f(bx + 18 * side)} {f(hy - 6)} {f(bx + 14 * side)} {f(hy + 20)} '
                            f'Q{f(bx + 10 * side)} {f(hy + 30)} {f(bx + 4 * side)} {f(hy + 32)} '
                            f'Q{f(bx + 8 * side)} {f(hy + 14)} {f(bx - 2 * side)} {f(hy - 2)} Z', 'hair', INK, 1.6)
                out += circle((bx + 3 * side, hy - 8), 3, self.vest if self.vest != 'lime' else 'rose', INK, 1.4)
        # ears
        for sx in (-1, 1):
            if (sx < 0 and L < .6) or (sx > 0 and L > -.6):
                ex = hx + sx * (15.5 - 2.5 * abs(L)) - 3 * L
                out += ellipse((ex, hy + 2), 3.2, 4.4, 'skin', INK, 1.6)
        # face
        out += ellipse((hx, hy), 15.5, 17.5, 'skin', INK, 1.9)
        # hair
        if f_:
            out += path(f'M{f(hx - 15.5)} {f(hy + 6)} Q{f(hx - 18)} {f(hy - 16)} {f(hx)} {f(hy - 18.5)} '
                        f'Q{f(hx + 18)} {f(hy - 16)} {f(hx + 15.5)} {f(hy + 6)} '
                        f'Q{f(hx + 12)} {f(hy - 6)} {f(fx + 4)} {f(hy - 9)} '
                        f'Q{f(fx - 4)} {f(hy - 4)} {f(hx - 13)} {f(hy - 2)} '
                        f'Q{f(hx - 14)} {f(hy + 2)} {f(hx - 15.5)} {f(hy + 6)} Z', 'hair', INK, 1.6)
            out += circle((hx - 15.8 + 3 * max(L, 0), hy + 8), 1.6, 'sun', None)
        else:
            out += path(f'M{f(hx - 15.5)} {f(hy + 2)} Q{f(hx - 17)} {f(hy - 17)} {f(hx)} {f(hy - 18.5)} '
                        f'Q{f(hx + 17)} {f(hy - 17)} {f(hx + 15.5)} {f(hy + 2)} '
                        f'L{f(hx + 13)} {f(hy - 5)} Q{f(fx + 6)} {f(hy - 11)} {f(fx - 4)} {f(hy - 9)} '
                        f'Q{f(hx - 10)} {f(hy - 8)} {f(hx - 13)} {f(hy - 5)} Z', 'hair', INK, 1.6)
            # short beard along the jaw
            out += path(f'M{f(hx - 15)} {f(hy + 3)} Q{f(hx - 14)} {f(hy + 18)} {f(fx)} {f(hy + 18.2)} '
                        f'Q{f(hx + 14)} {f(hy + 18)} {f(hx + 15)} {f(hy + 3)} '
                        f'Q{f(hx + 13)} {f(hy + 12)} {f(fx + 6)} {f(hy + 15)} '
                        f'Q{f(fx)} {f(hy + 16.5)} {f(fx - 6)} {f(hy + 15)} '
                        f'Q{f(hx - 13)} {f(hy + 12)} {f(hx - 15)} {f(hy + 3)} Z', 'hair', None)
        out += self._face(fx, hy)
        if self.hat:
            out += self._helmet(hx, hy)
        k = HEAD
        return neck + group(out, f'translate({f(hx)} {f(hy)}) scale({k}) translate({f(-hx)} {f(-hy)})')

    def _face(self, fx, hy):
        brows, eyes, mouth = EXPR[self.expr]
        m = self.who == 'm'
        ex, ey, my = 6.2, hy + 1.5, hy + 10
        out = ''
        # brows
        bl = {'soft': (-1, -1), 'up': (-2, -2), 'high': (-3.5, -3.5), 'down': (1.2, -.6), 'sad': (-.6, 1.4),
              'flat': (0, 0), 'one': (-3.5, 0)}[brows]
        for sx in (-1, 1):
            inner, outer = bl if sx < 0 else (bl[0], bl[1]) if brows != 'one' else (0, 0)
            if brows == 'one' and sx < 0:
                inner, outer = -3.5, -2.5
            x0, x1 = fx + sx * (ex - 3.2), fx + sx * (ex + 3.2)
            y0, y1 = hy - 6 + inner, hy - 6 + outer
            out += line(f'M{f(x0)} {f(y0)} Q{f((x0 + x1) / 2)} {f(min(y0, y1) - 1)} {f(x1)} {f(y1)}', 2.2 if m else 1.7)
        # eyes
        for sx in (-1, 1):
            c = (fx + sx * ex, ey)
            kind = eyes
            if eyes == 'wink' and sx > 0:
                kind = 'arc'
            elif eyes == 'wink':
                kind = 'dot'
            if kind == 'dot':
                out += ellipse(c, 1.9, 2.5, FACE, None)
            elif kind == 'side':
                out += ellipse((c[0] + 1.2, c[1] - .8), 1.9, 2.5, FACE, None)
            elif kind == 'arc':
                out += line(f'M{f(c[0] - 2.8)} {f(c[1] + .8)} Q{f(c[0])} {f(c[1] - 2.8)} {f(c[0] + 2.8)} {f(c[1] + .8)}', 1.8, FACE)
            elif kind == 'closed':
                out += line(f'M{f(c[0] - 2.8)} {f(c[1])} Q{f(c[0])} {f(c[1] + 1.8)} {f(c[0] + 2.8)} {f(c[1])}', 1.8, FACE)
            elif kind == 'half':
                out += line(f'M{f(c[0] - 2.6)} {f(c[1] - .4)} L{f(c[0] + 2.6)} {f(c[1] - .4)}', 1.8, FACE)
                out += ellipse((c[0], c[1] + .6), 1.7, 1.4, FACE, None)
            elif kind == 'squint':
                out += line(f'M{f(c[0] - 2.6)} {f(c[1])} L{f(c[0] + 2.6)} {f(c[1] - .3)}', 2.2, FACE)
            elif kind == 'wide':
                out += circle(c, 3.2, 'paper', FACE, 1.4) + circle((c[0], c[1] + .3), 1.5, FACE, None)
            if not m and kind in ('dot', 'side', 'wide', 'half'):
                out += line(f'M{f(c[0] + sx * 2)} {f(c[1] - 2.2)} L{f(c[0] + sx * 3.6)} {f(c[1] - 3.6)}', 1.2, FACE)
        # nose
        out += line(f'M{f(fx + .5)} {f(hy + 3)} Q{f(fx + 2.6)} {f(hy + 6.2)} {f(fx - .6)} {f(hy + 6.6)}', 1.4, FACE)
        # cheeks
        if self.blush:
            for sx in (-1, 1):
                out += ellipse((fx + sx * 9.5, hy + 7.5), 3, 2, 'rose', None, op=.6)
        # moustache for the man
        if m:
            out += path(f'M{f(fx - 5)} {f(my - 1.4)} Q{f(fx)} {f(my - 3.8)} {f(fx + 5)} {f(my - 1.4)} Q{f(fx)} {f(my - 2.4)} {f(fx - 5)} {f(my - 1.4)} Z', 'hair', 'hair', 1.2)
        mo = {
            'smile': line(f'M{f(fx - 4.5)} {f(my)} Q{f(fx)} {f(my + 4)} {f(fx + 4.5)} {f(my)}', 1.8, FACE),
            'small': line(f'M{f(fx - 2.4)} {f(my + .6)} Q{f(fx)} {f(my + 2)} {f(fx + 2.4)} {f(my + .6)}', 1.7, FACE),
            'open': path(f'M{f(fx - 5)} {f(my - .5)} Q{f(fx)} {f(my + 7)} {f(fx + 5)} {f(my - .5)} Z', FACE, FACE, 1.4)
                    + path(f'M{f(fx - 2.6)} {f(my + 3.6)} Q{f(fx)} {f(my + 2)} {f(fx + 2.6)} {f(my + 3.6)} Q{f(fx)} {f(my + 5.4)} {f(fx - 2.6)} {f(my + 3.6)} Z', 'rose', None),
            'laugh': path(f'M{f(fx - 6)} {f(my - 1)} Q{f(fx)} {f(my + 9)} {f(fx + 6)} {f(my - 1)} Z', FACE, FACE, 1.4)
                     + path(f'M{f(fx - 3.4)} {f(my + 4.2)} Q{f(fx)} {f(my + 1.6)} {f(fx + 3.4)} {f(my + 4.2)} Q{f(fx)} {f(my + 7)} {f(fx - 3.4)} {f(my + 4.2)} Z', 'rose', None),
            'o': ellipse((fx, my + 1.4), 2.3, 2.9, FACE, None),
            'side': line(f'M{f(fx - 2.5)} {f(my + 1.2)} Q{f(fx + 1.5)} {f(my + 1.6)} {f(fx + 4)} {f(my - .6)}', 1.8, FACE),
            'flat': line(f'M{f(fx - 3.4)} {f(my + 1.2)} L{f(fx + 3.4)} {f(my + 1.2)}', 1.8, FACE),
            'wavy': line(f'M{f(fx - 4)} {f(my + 1.6)} Q{f(fx - 2)} {f(my - .2)} {f(fx)} {f(my + 1.4)} Q{f(fx + 2)} {f(my + 2.8)} {f(fx + 4)} {f(my + 1)}', 1.6, FACE),
            'frown': line(f'M{f(fx - 4)} {f(my + 2.6)} Q{f(fx)} {f(my - .6)} {f(fx + 4)} {f(my + 2.6)}', 1.8, FACE),
            'grin': path(f'M{f(fx - 5)} {f(my)} Q{f(fx)} {f(my + 5.5)} {f(fx + 5)} {f(my)} Q{f(fx)} {f(my + 1.6)} {f(fx - 5)} {f(my)} Z', 'paper', FACE, 1.5),
            'shout': ellipse((fx, my + 2), 3.4, 4.2, FACE, None) + ellipse((fx, my + 4), 2, 1.3, 'rose', None),
            'teeth': rect(fx - 5, my - .5, 10, 4.6, 'paper', 1.6, sw=1.5) + line(f'M{f(fx - 5)} {f(my + 1.8)} L{f(fx + 5)} {f(my + 1.8)}', 1, FACE),
        }[mouth]
        return out + mo

    def _helmet(self, hx, hy):
        c = 'sun' if self.who == 'm' else 'paper'
        L = self.look
        out = path(f'M{f(hx - 18)} {f(hy - 6)} C{f(hx - 18)} {f(hy - 28)} {f(hx - 7)} {f(hy - 32)} {f(hx + 2 * L)} {f(hy - 32)} '
                   f'C{f(hx + 7)} {f(hy - 32)} {f(hx + 18)} {f(hy - 28)} {f(hx + 18)} {f(hy - 6)} Z', c)
        out += path(f'M{f(hx - 22 + 4 * min(L, 0))} {f(hy - 6.5)} Q{f(hx)} {f(hy - 10)} {f(hx + 22 + 4 * max(L, 0))} {f(hy - 6.5)} '
                    f'L{f(hx + 22 + 4 * max(L, 0))} {f(hy - 3.5)} Q{f(hx)} {f(hy - 6.5)} {f(hx - 22 + 4 * min(L, 0))} {f(hy - 3.5)} Z', c)
        out += line(f'M{f(hx + 2 * L - 4)} {f(hy - 30)} Q{f(hx + 2 * L)} {f(hy - 20)} {f(hx + 2 * L - 1)} {f(hy - 9)}', 1.5)
        out += line(f'M{f(hx - 12)} {f(hy - 24)} Q{f(hx - 15)} {f(hy - 18)} {f(hx - 14)} {f(hy - 12)}', 2.4, 'paper', .7)
        return out

    def back(self):
        """همه‌چیز جز دست جلویی."""
        b = 1 - self.front
        arm, _ = self._arm(b)
        return self._wrap(self._legs() + arm + self._torso() + self._head())

    def front_(self):
        arm, _ = self._arm(self.front)
        return self._wrap(arm)

    def draw(self, held=''):
        return self.shadow() + self.back() + held + self.front_()


# ---------------------------------------------------------------- backgrounds

def wash(c, rx, ry, color, op=.35):
    """لکه آبرنگی پشت صحنه."""
    return ellipse(c, rx, ry, color, None, op=op, extra_filter=True) if False else \
        f'<ellipse cx="{f(c[0])}" cy="{f(c[1])}" rx="{f(rx)}" ry="{f(ry)}" fill="{col(color)}" opacity="{op}"/>'


def ground(x1, x2, y, w=1.8):
    return line(f'M{f(x1)} {f(y)} Q{f((x1 + x2) / 2)} {f(y - 2)} {f(x2)} {f(y)}', w)


def svg(name, viewbox, washes, body):
    """خروجی نهایی: لایه آبرنگ با فیلتر نرم و لایه اصلی با لرزش کم خط."""
    i = name
    defs = (f'<defs>'
            f'<filter id="w-{i}" x="-20%" y="-20%" width="140%" height="140%">'
            f'<feTurbulence type="fractalNoise" baseFrequency=".022" numOctaves="3" seed="7"/>'
            f'<feDisplacementMap in="SourceGraphic" scale="26"/><feGaussianBlur stdDeviation="1.6"/></filter>'
            f'<filter id="r-{i}" x="-5%" y="-5%" width="110%" height="110%">'
            f'<feTurbulence type="fractalNoise" baseFrequency=".04" numOctaves="2" seed="3" result="n"/>'
            f'<feDisplacementMap in="SourceGraphic" in2="n" scale="1.8" result="d"/>'
            f'<feTurbulence type="fractalNoise" baseFrequency=".8" numOctaves="2" seed="11" result="g"/>'
            f'<feColorMatrix in="g" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 -.22 1.06" result="a"/>'
            f'<feComposite in="d" in2="a" operator="in"/></filter>'
            f'</defs>')
    return (f'<svg viewBox="{viewbox}" xmlns="http://www.w3.org/2000/svg">{defs}'
            f'<g filter="url(#w-{i})" style="opacity:var(--fbh-art-wash)">{washes}</g><g filter="url(#r-{i})">{body}</g></svg>')
