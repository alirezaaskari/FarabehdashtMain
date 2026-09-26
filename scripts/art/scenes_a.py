"""صحنه‌های سرصفحه مواد شیمیایی، فروشگاه، دوره‌ها، نویسندگی دانشنامه، مشاوره و حساب کاربری."""
import math
from engine import Person, wash, ground, rect, line, circle, ellipse, path, poly, limb, INK, f
from registry import art
import props as p


# ------------------------------------------------------------ local helpers

def hold(fig, *things):
    return fig.shadow() + fig.back() + ''.join(things) + fig.front_()


def seated(fig, *things):
    return fig.back() + ''.join(things) + fig.front_()


def q(x, y, size=18, op=.85):
    return f'<text x="{f(x)}" y="{f(y)}" font-size="{size}" font-weight="700" fill="currentColor" opacity="{op}">?</text>'


def bang(x, y, size=18, op=.85):
    return f'<text x="{f(x)}" y="{f(y)}" font-size="{size}" font-weight="700" fill="currentColor" opacity="{op}">!</text>'


def receipt(x, y, s=1, rot=0):
    """رسید با لبه دندانه‌ای؛ لنگر وسط."""
    zig = ' '.join(f'L{f(12 - i * 4)} {18 if i % 2 == 0 else 15}' for i in range(7))
    o = path(f'M-12 -18 L12 -18 L12 18 {zig} Z', 'paper')
    o += line('M-7 -11 L7 -11 M-7 -5 L4 -5 M-7 1 L7 1', 1.3, op=.8) + line('M-7 9 L7 9', 2, 'leaf')
    return p.at(x, y, s, o, rot)


def big_page(x, y, w, h, fill='paper', lines=5, rot=0):
    """برگه بزرگ ایستاده؛ x,y گوشه بالا-چپ."""
    t = f'rotate({f(rot)} {f(x + w / 2)} {f(y + h / 2)})' if rot else None
    o = path(f'M{f(x)} {f(y)} L{f(x + w - 16)} {f(y)} L{f(x + w)} {f(y + 16)} L{f(x + w)} {f(y + h)} L{f(x)} {f(y + h)} Z', fill)
    o += path(f'M{f(x + w - 16)} {f(y)} L{f(x + w - 16)} {f(y + 16)} L{f(x + w)} {f(y + 16)}', None, INK, 1.6)
    for i in range(lines):
        yy = y + 22 + i * (h - 34) / max(lines - 1, 1)
        o += line(f'M{f(x + 12)} {f(yy)} L{f(x + w - 14 - (i % 3) * 12)} {f(yy)}', 1.8, op=.75)
    return p.group(o, t) if t else o


def awning(x, y, w=150, color='sun', n=7):
    """سایبان راه‌راه؛ x وسط، y لبه بالا."""
    o = ''
    sw = w / n
    for i in range(n):
        c = color if i % 2 == 0 else 'paper'
        x0 = x - w / 2 + i * sw
        o += path(f'M{f(x0)} {f(y)} L{f(x0 + sw)} {f(y)} L{f(x0 + sw)} {f(y + 12)} Q{f(x0 + sw / 2)} {f(y + 20)} {f(x0)} {f(y + 12)} Z', c, INK, 1.6)
    return o


def tile(x, y, s=1, color='sky', play=True):
    """کاشی ویدئو؛ لنگر وسط."""
    o = rect(-14, -10, 28, 20, color, 2.5)
    if play:
        o += poly([(-3.5, -5), (5, 0), (-3.5, 5)], 'paper', w=1.2)
    return p.at(x, y, s, o)


def dashed_slot(x, y, w, h):
    return path(f'M{f(x)} {f(y)} h{f(w)} v{f(h)} h{f(-w)} Z', None, INK, 1.4, extra=' stroke-dasharray="3 3" opacity=".6"')


def pin(x, y, color='rose'):
    return circle((x, y), 3.2, color, INK, 1.3)


def hard_hat(x, y, s=1, color='grape', rot=0):
    """کلاه در دست؛ لنگر وسط لبه."""
    o = path('M-12 0 Q-12 -14 0 -14 Q12 -14 12 0 Z', color, INK, 1.6) + line('M-16 0 L16 0', 2.6)
    return p.at(x, y, s, o, rot)


def dotted(d, op=.6):
    return path(d, None, INK, 1.6, extra=f' stroke-dasharray="2 4" opacity="{op}"')


# ------------------------------------------------------------ chemicals

@art('chemicals-compare')
def _():
    w = wash((110, 120), 105, 70, 'grape', .28) + wash((200, 70), 45, 35, 'sun', .3)
    b = ground(8, 252, 188) + p.balance(92, 188, 1.55, 'rose', 'leaf', 9)
    m = Person('m', 206, 188, .82, arms=((-8, -3), (22, -168)), expr='think', look=-.6, outfit='coat', hat=False, tilt=-5)
    b += m.draw()
    b += q(226, 34, 22) + q(244, 50, 14, .55)
    return w, b


@art('chemicals-show')
def _():
    w = wash((120, 120), 110, 70, 'sun', .3) + wash((70, 80), 45, 40, 'rose', .25)
    b = ground(8, 252, 188) + p.drum(38, 188, 1.1, 'leaf', label=False) + p.drum(92, 188, 1.9, 'sky')
    fw = Person('f', 190, 188, .82, arms=((-45, -80), (10, 4)), expr='focus', look=-.7, lean=-7, outfit='coat', top='leaf')
    hx, hy = fw.head_at()
    b += fw.draw()
    b += p.goggles(hx - 4.5, hy + 1, .82)
    return w, b


# ------------------------------------------------------------ commerce

@art('commerce-index')
def _():
    w = wash((110, 110), 100, 75, 'rose', .28) + wash((215, 90), 40, 45, 'sun', .3)
    b = ground(8, 252, 188) + p.market_stall(92, 188, 128, 'rose')
    b += p.folder(56, 148, .72, 'sun') + p.folder(94, 148, .72, 'sky') + p.folder(132, 148, .72, 'leaf', papers=False)
    b += rect(64, 110, 56, 4, 'sand', 1) + p.binder(76, 110, .8, 'grape') + p.binder(90, 110, .8, 'vest') + p.binder(104, 110, .8, 'sky')
    fw = Person('f', 206, 188, .82, arms=((-60, -95), (8, 2)), expr='curious', look=-.8, outfit='jacket', top='sky', hat=False)
    b += fw.draw() + p.bag(236, 188, .7, 'leaf', items=False)
    return w, b


@art('commerce-cart')
def _():
    w = wash((140, 120), 110, 65, 'sky', .3) + wash((200, 60), 40, 30, 'sun', .28)
    b = ground(8, 252, 188)
    m = Person('m', 76, 188, .82, legs=((-22, -8), (24, 6)), arms=((58, 92), (48, 88)), expr='happy', look=.7, lean=8, outfit='shirt', top='sky', hat=False)
    h = m.hand_at(1)
    s = (188 - h[1]) / 48
    cx = h[0] + 34 * s
    b += m.shadow() + m.back()
    b += p.folder(cx - 10 * s, 188 - 30 * s, .55 * s, 'sun') + p.folder(cx + 10 * s, 188 - 28 * s, .5 * s, 'rose')
    b += p.paper(cx + 2 * s, 188 - 44 * s, .5 * s, 12, 2) + p.paper(cx - 14 * s, 188 - 42 * s, .45 * s, -14, 2)
    b += p.cart(cx, 188, s, items=False) + m.front_()
    b += line('M28 118 L10 118 M32 132 L16 132', 1.6, op=.5)
    return w, b


@art('commerce-become-seller')
def _():
    w = wash((130, 110), 115, 75, 'sun', .3) + wash((60, 60), 40, 30, 'leaf', .25)
    b = ground(8, 252, 188)
    b += line('M62 42 L62 150 M198 42 L198 150', 2.4) + awning(130, 26, 150, 'leaf')
    fw = Person('f', 130, 188, .8, arms=((-118, -150), (118, 150)), expr='happy', look=0, outfit='shirt', top='rose', hat=False)
    b += fw.back() + fw.front_()
    b += rect(56, 144, 148, 44, 'sand', 2) + line('M60 156 L200 156', 1.4, op=.6)
    b += p.folder(84, 144, .6, 'sky') + p.folder(176, 144, .6, 'grape') + p.box(130, 172, .55, color='paper', label=False)
    b += p.sparkle(30, 70, .9) + p.sparkle(232, 64, .7, 'leaf') + p.flag(228, 144, .8, 'rose')
    return w, b


@art('commerce-purchases')
def _():
    w = wash((140, 110), 105, 75, 'leaf', .28) + wash((170, 50), 50, 30, 'sun', .3)
    b = ground(8, 252, 188)
    m = Person('m', 84, 188, .82, legs=((-6, -2), (8, 4)), arms=((40, 70), (55, 100)), expr='laugh', look=.6, outfit='jacket', top='leaf', hat=False)
    b += m.shadow() + m.back()
    b += p.paper(150, 84, .9, -24, 3) + p.paper(186, 70, .9, 14, 3) + p.folder(176, 116, .7, 'sun')
    b += p.paper(212, 104, .7, 32, 2)
    b += p.box(170, 188, 1.6, open_=True, color='sand')
    b += m.front_()
    b += p.sparkle(140, 44, .8) + p.sparkle(222, 52, .6, 'rose') + line('M160 128 L154 116 M180 126 L182 112', 1.4, op=.6)
    return w, b


@art('commerce-show')
def _():
    w = wash((110, 110), 100, 80, 'sky', .3)
    b = ground(8, 252, 188) + big_page(34, 32, 96, 150, lines=6)
    b += rect(48, 132, 30, 30, 'sun', 2, sw=1.6) + rect(84, 136, 30, 26, 'rose', 2, sw=1.6)
    fw = Person('f', 196, 188, .82, arms=((-72, -92), (10, 4)), expr='curious', look=-.8, lean=-8, outfit='vest', hat=True)
    h = fw.hand_at(0)
    s = 1.5
    b += fw.shadow() + fw.back() + p.magnifier(h[0] - 18 * s, h[1] - 18 * s, s) + fw.front_()
    return w, b


@art('commerce-product-create')
def _():
    w = wash((90, 70), 70, 50, 'sky', .32) + wash((170, 140), 80, 45, 'leaf', .25)
    b = ground(8, 252, 188) + p.cloud_upload(72, 60, 1.7)
    m = Person('m', 176, 188, .82, arms=((-150, -165), (12, 4)), expr='determined', look=-.6, outfit='shirt', top='shirt', hat=False)
    h = m.hand_at(0)
    b += m.shadow() + m.back() + p.paper(h[0] - 2, h[1] - 14, 1.1, -10, 3) + m.front_()
    b += dotted(f'M{f(h[0] - 18)} {f(h[1] - 10)} Q110 30 104 60')
    b += p.folder(70, 188, .8, 'sun')
    return w, b


@art('commerce-product-edit')
def _():
    w = wash((110, 115), 105, 70, 'grape', .28)
    b = ground(8, 252, 188)
    b += p.folder(82, 188, 3.1, 'sky')
    b += rect(52, 142, 40, 8, 'paper', 2, sw=1.5) + p.sparkle(46, 62, .8)
    fw = Person('f', 196, 188, .82, arms=((-62, -104), (-20, -110)), expr='focus', look=-.7, lean=-5, outfit='vest', hat=True)
    h = fw.hand_at(0)
    b += fw.shadow() + fw.back() + p.wrench(h[0] - 4, h[1] - 2, 1.5, 105) + fw.front_()
    return w, b


@art('commerce-vendor-products')
def _():
    w = wash((100, 110), 100, 80, 'sand', .35) + wash((200, 70), 40, 40, 'sky', .25)
    b = ground(8, 252, 188)
    b += p.shelf(78, 150, 120, 2, 56, items=False)
    b += p.box(44, 150, .75, color='sand') + p.box(80, 150, .75, color='rose') + p.box(116, 150, .75, color='sky')
    b += p.box(50, 94, .75, color='leaf')
    b += dashed_slot(70, 72, 30, 22)
    m = Person('m', 196, 188, .82, arms=((-60, -140), (12, 4)), expr='determined', look=-.6, lean=-4, outfit='vest', front=0)
    h1 = m.hand_at(0)
    b += m.shadow() + m.back() + p.box(h1[0] - 4, h1[1] + 12, .75, color='sun') + m.front_()
    b += p.box(236, 188, .6, color='sand', label=False)
    return w, b


@art('commerce-vendor-sales')
def _():
    w = wash((110, 110), 100, 75, 'leaf', .3) + wash((70, 50), 45, 30, 'sun', .28)
    b = ground(8, 252, 188) + p.chart_board(88, 188, 1.75, bars=(10, 18, 28, 42), colors=('sky', 'leaf', 'sun', 'vest'))
    b += p.rocket_arrow(40, 118, 142, 44, 'leaf') + p.star(160, 36, .8)
    fw = Person('f', 200, 188, .82, arms=((-28, 38), (28, -38)), expr='proud', look=-.3, outfit='jacket', top='grape', hat=False, hair_down=True)
    b += fw.draw()
    return w, b


@art('commerce-vendor-settlement')
def _():
    w = wash((140, 115), 110, 70, 'sun', .3)
    b = ground(8, 252, 188) + p.bank(204, 188, 1.15, 'sand')
    m = Person('m', 84, 188, .82, legs=((-20, -6), (22, 8)), arms=((-14, -6), (40, 115)), expr='smile', look=.7, lean=4, outfit='jacket', top='sky', hat=False)
    h2 = m.hand_at(1)
    b += m.shadow() + m.back() + p.coins_stack(h2[0] + 2, h2[1] + 4, 1.05, 5) + m.front_()
    b += dotted('M144 150 L160 150 M138 164 L156 164', .5)
    b += p.coin(150, 60, .9) + p.coin(170, 44, .7)
    return w, b


@art('commerce-checkout-success')
def _():
    w = wash((130, 110), 110, 80, 'leaf', .3) + wash((130, 40), 70, 26, 'sun', .28)
    b = ground(8, 252, 188) + p.confetti(24, 12, 212, 60, 4)
    fw = Person('f', 130, 188, .84, legs=((-10, -4), (10, 4)), arms=((-140, -160), (140, 158)), expr='laugh', look=0, outfit='shirt', top='leaf', hat=False)
    h = fw.hand_at(1)
    b += fw.shadow() + fw.back() + receipt(h[0] + 12, h[1] - 6, 1.05, 18) + fw.front_()
    b += p.check_badge(48, 132, 1.1) + p.bag(214, 188, .8, 'rose')
    return w, b


@art('commerce-checkout-failed')
def _():
    w = wash((130, 115), 105, 75, 'rose', .28)
    b = ground(8, 252, 188)
    m = Person('m', 130, 188, .84, arms=((-46, -128), (14, 6)), expr='oops', look=-.3, outfit='shirt', top='shirt2', hat=False, tilt=-4)
    h = m.hand_at(0)
    b += m.shadow() + m.back() + p.card(h[0] - 4, h[1] - 12, 1.25, -14, 'grape', broken=True) + m.front_()
    b += p.x_badge(206, 64, 1.1) + bang(52, 30, 24) + line('M60 56 L52 64 M76 52 L78 42', 1.6, op=.6)
    return w, b


# ------------------------------------------------------------ courses

@art('courses-index')
def _():
    w = wash((100, 90), 100, 70, 'sky', .3) + wash((210, 140), 45, 40, 'sun', .25)
    b = ground(8, 252, 188) + p.whiteboard(16, 30, 128, 72, 'chart')
    fw = Person('f', 196, 188, .82, arms=((-100, -115), (10, 4)), expr='call', look=-.2, outfit='shirt', top='sky', hat=False)
    h = fw.hand_at(0)
    tip = (h[0] - 34, h[1] - 16)
    b += fw.shadow() + fw.back() + line(f'M{f(h[0] + 4)} {f(h[1] + 2)} L{f(tip[0])} {f(tip[1])}', 2.6, 'sand') + fw.front_()
    b += line(f'M{f(h[0] + 4)} {f(h[1] + 2)} L{f(tip[0])} {f(tip[1])}', 1, INK, .6)
    return w, b


@art('courses-checkout-success')
def _():
    w = wash((130, 110), 105, 75, 'sun', .3) + wash((60, 60), 40, 30, 'leaf', .28)
    b = ground(8, 252, 188)
    m = Person('m', 128, 188, .84, arms=((10, 70), (-10, -70)), expr='happy', look=.3, outfit='shirt', top='leaf', hat=False)
    b += p.backpack(m.x - 20, 150, .9, 'vest')
    h1, h2 = m.hand_at(0), m.hand_at(1)
    b += m.shadow() + m.back()
    b += line(f'M{f(m.x - 10)} {f(m.at(m.sh[0])[1] - 2)} L{f(m.x - 12)} {f(m.at(m.hip[0])[1] - 10)}', 3.2, 'sand')
    b += p.book((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 - 2, 1.1, 'sky', open_=True) + m.front_()
    b += p.sparkle(60, 60, .9) + p.sparkle(200, 50, .7, 'rose') + p.sparkle(214, 96, .5)
    return w, b


@art('courses-checkout-failed')
def _():
    w = wash((120, 115), 110, 70, 'rose', .26) + wash((210, 60), 35, 30, 'sky', .25)
    b = ground(8, 252, 188) + p.desk(84, 124, 128, 64) + p.laptop(84, 124, 1.9, 'shirt', 'x')
    fw = Person('f', 200, 188, .82, arms=((-10, -3), (18, -170)), expr='worried', look=-.6, outfit='jacket', top='rose', hat=False, tilt=6)
    b += fw.draw() + p.mug(126, 124, .9, 'sand', steam=False) + line('M224 30 Q228 36 224 40', 1.4, 'sky')
    return w, b


@art('courses-course-create')
def _():
    w = wash((100, 100), 100, 75, 'grape', .26) + wash((200, 150), 45, 30, 'sky', .25)
    b = ground(8, 252, 188) + rect(16, 34, 130, 100, 'paper', 4)
    cols = (('sky', 'rose', 'leaf'), ('sun', 'grape', None))
    for r, row in enumerate(cols):
        for c, col in enumerate(row):
            x, y = 38 + c * 42, 62 + r * 44
            if col:
                b += tile(x, y, 1.05, col)
            else:
                b += dashed_slot(x - 15, y - 11, 30, 22)
    b += line('M26 84 L136 84', 1, op=.35) + line('M40 48 L50 40 L60 48', 1.4, op=.6)
    m = Person('m', 200, 188, .82, arms=((-112, -125), (10, 4)), expr='focus', look=-.6, outfit='shirt', top='sky', hat=False)
    h = m.hand_at(0)
    b += m.shadow() + m.back() + tile(h[0] - 8, h[1] - 6, 1.05, 'vest') + m.front_()
    return w, b


@art('courses-course-edit')
def _():
    w = wash((120, 110), 110, 75, 'sky', .28) + wash((50, 60), 30, 30, 'rose', .25)
    b = ground(8, 252, 188) + p.camera(104, 106, 1.8) + circle((122, 82), 3.2, 'rose', INK, 1.2)
    b += p.lamp(34, 188, 1.3)
    fw = Person('f', 184, 188, .82, arms=((-58, -80), (12, 4)), expr='wink', look=-.5, lean=-4, outfit='jacket', top='grape', hat=False, hair_down=True)
    b += fw.draw()
    b += line('M58 84 Q52 92 58 100 M50 78 Q40 92 50 106', 1.5, op=.55)
    return w, b


@art('courses-instructor-courses')
def _():
    w = wash((150, 90), 100, 65, 'sand', .32) + wash((60, 160), 50, 25, 'sky', .25)
    b = ground(8, 252, 188) + p.whiteboard(104, 22, 110, 62, 'text')
    b += p.chair(36, 188, .72, 1, 'sky') + p.chair(78, 188, .72, 1, 'sky') + p.chair(120, 188, .72, 1, 'sky')
    m = Person('m', 224, 188, .8, arms=((-70, -40), (10, 4)), expr='calm', look=-.7, outfit='coat', top='sky', hat=False)
    b += m.draw()
    return w, b


@art('courses-instructor-sales')
def _():
    w = wash((130, 110), 110, 75, 'leaf', .28)
    b = ground(8, 252, 188) + p.desk(78, 128, 120, 60, 'sand')
    b += receipt(42, 110, .9, -8) + receipt(62, 108, .9, 6) + p.coins_stack(106, 126, .9, 3) + p.mug(128, 128, .8, 'rose')
    fw = Person('f', 196, 188, .82, arms=((-16, -110), (12, 4)), expr='calm', look=-.5, outfit='shirt', top='shirt', hat=False)
    h = fw.hand_at(0)
    b += fw.shadow() + fw.back() + p.calculator(h[0] - 4, h[1] - 6, 1.05, '12') + fw.front_()
    return w, b


@art('courses-learn')
def _():
    w = wash((130, 110), 105, 75, 'grape', .26) + wash((200, 60), 35, 30, 'sun', .25)
    b = ground(8, 252, 188) + ellipse((120, 168), 48, 22, 'sand')
    m = Person('m', 116, 150, .82, legs=((105, 10), (100, 6)), arms=((38, 110), (48, 120)), expr='curious', look=.5, outfit='shirt', top='sky', hat=False, anchor='pelvis', lean=-6)
    hx, hy = m.head_at()
    h = m.hand_at(1)
    b += m.back() + p.laptop(h[0] - 2, h[1] + 6, 1.05, 'sky', 'play') + m.front_()
    b += p.headphones(hx + 2, hy - 2, 1.55)
    b += line('M200 56 Q206 62 200 68 M210 50 Q220 62 210 74', 1.5, op=.55)
    return w, b


@art('courses-mine')
def _():
    w = wash((130, 115), 110, 70, 'rose', .26) + wash((60, 60), 40, 30, 'sky', .25)
    b = ground(8, 252, 188)
    fw = Person('f', 124, 188, .84, legs=((-20, -8), (22, 6)), arms=((10, 72), (-10, -70)), expr='smile', look=.5, lean=3, outfit='jacket', top='leaf', hat=False)
    h1, h2 = fw.hand_at(0), fw.hand_at(1)
    b += fw.shadow() + fw.back() + p.book_stack((h1[0] + h2[0]) / 2, max(h1[1], h2[1]) + 8, .5, ('grape', 'sun', 'sky', 'rose')) + fw.front_()
    b += line('M70 120 L52 120 M74 136 L58 136', 1.6, op=.5) + p.plant(226, 188, .9, 'sky')
    return w, b


@art('courses-show')
def _():
    w = wash((100, 90), 100, 70, 'sun', .28) + wash((210, 150), 40, 35, 'leaf', .25)
    b = ground(8, 252, 188) + p.video_screen(14, 26, 148, 96, 'sky')
    b += line('M44 122 L36 188 M132 122 L140 188', 2.4)
    m = Person('m', 208, 188, .82, arms=((-78, -54), (10, 4)), expr='smile', look=-.4, outfit='jacket', top='rose', hat=False)
    b += m.draw()
    return w, b


# ------------------------------------------------------------ encyclopedia writing

@art('encyclopedia-writing-guide')
def _():
    w = wash((120, 110), 110, 75, 'sky', .28)
    b = ground(8, 252, 188) + big_page(24, 30, 100, 150, lines=0)
    b += line('M38 58 L104 58 M38 78 L94 78 M38 98 L100 98', 1.8, op=.75)
    fw = Person('f', 190, 188, .82, arms=((-54, -84), (-36, -70)), expr='determined', look=-.6, lean=-6, outfit='shirt', top='grape', hat=False)
    h1, h2 = fw.hand_at(0), fw.hand_at(1)
    tip = (78, 128)
    b += line(f'M38 118 L{f(tip[0])} {f(tip[1] - 2)}', 1.8)
    rot = math.degrees(math.atan2(h1[0] - tip[0], -(h1[1] - tip[1])))
    b += fw.shadow() + fw.back() + p.pencil(tip[0], tip[1], 2.1, rot, 44, 'sun') + fw.front_()
    return w, b


@art('encyclopedia-writing-form')
def _():
    w = wash((140, 110), 105, 75, 'sand', .34) + wash((60, 50), 40, 30, 'rose', .25)
    b = ground(8, 252, 188) + p.chair(66, 188, .95, 1, 'grape')
    m = Person('m', 70, 150, .82, legs=((90, 0), (86, 4)), arms=((40, 100), (50, 106)), expr='focus', look=.6, outfit='shirt', top='shirt2', hat=False, anchor='pelvis')
    b += m.back() + p.desk(160, 138, 120, 50) + p.typewriter(150, 138, 1.3) + m.front_()
    b += p.paper(214, 70, .8, 18, 3) + p.paper(172, 40, .7, -12, 2) + p.paper(226, 130, .7, 6, 2)
    return w, b


@art('encyclopedia-writing-index')
def _():
    w = wash((100, 100), 100, 75, 'leaf', .26) + wash((210, 150), 40, 30, 'sun', .25)
    b = ground(8, 252, 188) + rect(14, 28, 134, 96, 'sand', 4) + rect(20, 34, 122, 84, None, 2, sw=1.2, op=.5)
    b += p.paper(40, 62, .8, -6, 3) + pin(38, 48) + p.paper(76, 58, .8, 4, 3, 'sky') + pin(76, 44, 'sun')
    b += p.paper(52, 98, .7, 8, 2, 'shirt2') + pin(52, 86, 'leaf')
    fw = Person('f', 196, 188, .82, arms=((-120, -135), (10, 4)), expr='smile', look=-.6, outfit='jacket', top='sky', hat=False)
    h = fw.hand_at(0)
    b += fw.shadow() + fw.back() + p.paper(h[0] - 10, h[1] - 4, .85, -10, 3, 'paper') + pin(h[0] - 12, h[1] - 18, 'grape') + fw.front_()
    return w, b


# ------------------------------------------------------------ expert

@art('expert-create')
def _():
    w = wash((130, 110), 105, 75, 'sky', .28) + wash((190, 50), 50, 35, 'sun', .28)
    b = ground(8, 252, 188)
    m = Person('m', 110, 188, .84, arms=((-10, -4), (158, 172)), expr='call', look=.3, outfit='vest')
    h = m.hand_at(0)
    b += m.shadow() + m.back() + p.clipboard(h[0] + 4, h[1] - 6, .8, -10, 2) + m.front_()
    b += p.speech(158, 20, 60, 44, 'sun', -1, 'q')
    b += line('M150 20 L144 12 M156 12 L154 4', 1.6, op=.6)
    return w, b


@art('expert-mine')
def _():
    w = wash((130, 115), 105, 75, 'grape', .26) + wash((80, 50), 50, 30, 'leaf', .25)
    b = ground(8, 252, 188)
    fw = Person('f', 160, 188, .84, arms=((-10, -4), (-6, -138)), expr='happy', look=-.4, tilt=8, outfit='jacket', top='rose', hat=False, hair_down=True)
    h = fw.hand_at(1)
    b += fw.shadow() + fw.back() + p.phone(h[0] - 2, h[1] - 10, 1.2, -12, 'sky', 'bubble') + fw.front_()
    b += p.speech(30, 30, 58, 32, 'paper', 1, 'dots') + p.speech(66, 78, 50, 28, 'leaf', 1, 'check')
    return w, b


@art('expert-queue')
def _():
    w = wash((120, 110), 110, 75, 'sand', .32)
    b = ground(8, 252, 188) + p.chair(192, 188, .95, -1, 'leaf')
    m = Person('m', 188, 150, .82, legs=((-90, 0), (-86, -4)), arms=((-40, -100), (-50, -104)), expr='focus', look=-.6, outfit='coat', top='sky', hat=False, anchor='pelvis')
    b += m.back() + p.desk(92, 138, 128, 50) + p.tray(70, 138, 1.1, 4) + p.mug(126, 138, .95, 'rose') + m.front_()
    b += p.speech(36, 50, 32, 30, 'paper', 1, 'q') + p.speech(80, 30, 32, 30, 'sun', -1, 'q')
    return w, b


# ------------------------------------------------------------ identity

@art('identity-account')
def _():
    w = wash((130, 110), 105, 75, 'sky', .28) + wash((200, 60), 40, 30, 'rose', .25)
    b = ground(8, 252, 188)
    fw = Person('f', 124, 188, .84, arms=((12, 4), (150, 178)), expr='happy', look=.2, outfit='shirt', top='leaf', hat=False)
    b += fw.draw()
    n = fw.at(fw.neck)
    cx, cy = fw.at(fw.sc)[0] + 2, fw.at(fw.sc)[1] + 26
    b += line(f'M{f(n[0] - 8)} {f(n[1] + 4)} L{f(cx)} {f(cy - 8)} L{f(n[0] + 8)} {f(n[1] + 4)}', 1.8, 'rose')
    b += p.id_card(cx, cy, .72, 0, 'sky')
    h = fw.hand_at(1)
    b += line(f'M{f(h[0] + 12)} {f(h[1] - 6)} Q{f(h[0] + 16)} {f(h[1] + 2)} {f(h[0] + 12)} {f(h[1] + 8)} M{f(h[0] + 18)} {f(h[1] - 10)} Q{f(h[0] + 24)} {f(h[1] + 2)} {f(h[0] + 18)} {f(h[1] + 14)}', 1.6, op=.6)
    b += p.plant(36, 188, 1.1)
    return w, b


@art('identity-profiles')
def _():
    w = wash((120, 110), 110, 75, 'grape', .26) + wash((200, 60), 40, 35, 'sun', .25)
    b = ground(8, 252, 188) + p.hat_rack(78, 188, 1.35)
    m = Person('m', 190, 188, .82, arms=((-70, -110), (10, 4)), expr='curious', look=-.6, outfit='shirt', top='sand', hat=False)
    h = m.hand_at(0)
    b += m.shadow() + m.back() + hard_hat(h[0] - 4, h[1] + 4, 1.2, 'leaf') + m.front_()
    b += q(214, 36, 18, .6)
    return w, b
