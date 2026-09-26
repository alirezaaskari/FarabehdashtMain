"""صحنه‌های بسته B: پرداخت، پروژه، گزارش، ابزار، میزکار و صفحه‌های خطا."""
from engine import Person, wash, ground, rect, line, circle, path, poly, ellipse, INK, f
from registry import art
import props as p


# ------------------------------------------------------------ local helpers

def light(pts, op=.35):
    """پرتو نور نرم روی زمین یا دیوار."""
    return poly(pts, 'sun', stroke=None, op=op)


def thumb(x, y, s=1):
    """شست بالا روی دست؛ پیش از دست جلویی کشیده شود تا پایه‌اش زیر دست برود."""
    return p.at(x, y, s, rect(-2.6, -12, 5.6, 11, 'skin', 2.6, sw=1.5))


def tablet(x, y, s=1, rot=0, screen='shirt'):
    """تبلت با سه ردیف وضعیت؛ لنگر وسط."""
    o = rect(-17, -22, 34, 44, 'pants', 3.5) + rect(-14, -18, 28, 34, screen, 1.2, None)
    for i, c in enumerate(('leaf', 'leaf', 'sun')):
        yy = -12 + i * 10
        o += circle((-8, yy), 2.4, c, INK, 1) + line(f'M-3 {yy} L9 {yy}', 1.4, op=.7)
    return p.at(x, y, s, o, rot)


def result_card(x, y, w=46, color='leaf', n='0.8'):
    """کارت نتیجه ذخیره‌شده: نوار رنگی، عدد و یک خط."""
    o = rect(x, y, w, 20, 'paper', 3) + rect(x, y, 6, 20, color, 2, sw=1.4)
    o += f'<text x="{f(x + 11)}" y="{f(y + 9)}" font-size="7" font-family="monospace" fill="currentColor" data-numeric>{n}</text>'
    o += line(f'M{f(x + 11)} {f(y + 14)} L{f(x + w - 8)} {f(y + 14)}', 1.2, op=.6)
    return o


def open_gift(x, y, s=1, color='rose'):
    """جعبه هدیه باز با درِ پریده؛ لنگر وسط پایین."""
    o = rect(-22, -32, 44, 32, color, 2) + rect(-4, -32, 8, 32, 'sun', 0, sw=1.4)
    o += path('M-22 -32 L22 -32 L16 -40 L-16 -40 Z', INK, None, op=.25)
    lid = rect(-25, -9, 50, 10, color, 2) + rect(-4, -9, 8, 10, 'sun', 0, sw=1.4)
    lid += path('M0 -9 Q-14 -23 -12 -11 Z M0 -9 Q14 -23 12 -11 Z', 'sun', INK, 1.5)
    o += p.at(22, -70, 1, lid, 24)
    return p.at(x, y, s, o)


def big_doc(x, y, s=1):
    """سند ایستاده با کد QR؛ لنگر وسط پایین."""
    o = line('M-20 0 L-12 -20 M20 0 L12 -20', 2.2)
    o += path('M-30 -110 L20 -110 L30 -100 L30 -20 L-30 -20 Z', 'paper') + path('M20 -110 L20 -100 L30 -100', None, INK, 1.4)
    o += line('M-22 -98 L10 -98 M-22 -90 L16 -90 M-22 -82 L4 -82', 1.5, op=.8)
    o += p.qr(0, -50, .95)
    return p.at(x, y, s, o)


def checklist(x, y, s=1, done=2):
    """برگه چک‌لیست بزرگ؛ لنگر وسط."""
    o = rect(-24, -32, 48, 64, 'paper', 3) + rect(-10, -36, 20, 7, 'pants', 2, sw=1.6)
    for i in range(4):
        yy = -18 + i * 13
        o += rect(-17, yy - 4, 8, 8, 'paper', 1.5, sw=1.4) + line(f'M-4 {yy} L{16 - (i % 2) * 6} {yy}', 1.4, op=.8)
        if i < done:
            o += line(f'M-17 {yy - 1} L-13 {yy + 4} L-6 {yy - 8}', 2.4, 'leaf') + line(f'M-17 {yy - 1} L-13 {yy + 4} L-6 {yy - 8}', .9, INK, .6)
    return p.at(x, y, s, o)


def tool_on_hook(x, y, inner):
    return line(f'M{f(x)} {f(y)} L{f(x)} {f(y + 4)}', 1.6) + inner


# ------------------------------------------------------------ monetization

@art('monetization-checkout-success')
def _():
    w = wash((150, 112), 110, 76, 'sun', .3) + wash((200, 60), 40, 36, 'leaf', .25)
    b = light([(170, 186), (214, 186), (150, 196), (60, 196)], .3)
    b += p.door(192, 188, 46, 104, 'sky', open_=True) + ground(8, 252, 188)
    b += line('M160 70 L150 64 M160 96 L146 96 M160 122 L150 128', 1.6, 'sun')
    m = Person('m', 84, 188, .84, legs=((-4, -2), (8, 4)), arms=((-14, -6), (110, 95)), expr='happy', look=.6)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.key(h[0] + 3, h[1], 1.35, 4) + m.front_()
    b += p.sparkle(150, 40, .8) + p.sparkle(30, 70, .6, 'leaf')
    return w, b


@art('monetization-checkout-failed')
def _():
    w = wash((126, 116), 104, 74, 'rose', .28) + wash((60, 50), 38, 30, 'sky', .28)
    b = ground(8, 252, 188) + p.card(200, 172, 1, -14, 'grape', broken=True)
    fm = Person('f', 116, 188, .84, arms=((-22, 40), (22, -40)), expr='sad', look=.2, outfit='jacket', top='sky', hat=False, hair_down=True)
    h1, h2 = fm.hand_at(0), fm.hand_at(1)
    cx = (h1[0] + h2[0]) / 2
    b += fm.shadow() + fm.back() + p.padlock(cx, (h1[1] + h2[1]) / 2 + 10, 1.3) + fm.front_()
    b += p.x_badge(196, 70, .8) + line('M48 48 L50 58 M60 40 L62 52 M38 62 L40 70', 1.4, 'sky')
    return w, b


@art('monetization-plans')
def _():
    w = wash((150, 110), 110, 78, 'grape', .28) + wash((222, 44), 30, 26, 'sun', .32)
    b = p.stairs(60, 188, 5, 30, 16, 'sky') + ground(8, 252, 188)
    fm = Person('f', 108, 156, .74, legs=((-6, 2), (60, -8)), arms=((-40, -20), (150, 160)), expr='determined', look=.8, lean=8, hat=False, outfit='jacket', top='sky')
    b += fm.draw()
    b += p.star(226, 40, 1.35) + p.sparkle(200, 22, .6) + p.sparkle(246, 70, .5, 'leaf')
    b += line('M188 58 Q198 50 206 48', 1.4, op=.6)
    return w, b


@art('monetization-upgrade')
def _():
    w = wash((140, 112), 112, 76, 'sun', .3) + wash((176, 50), 40, 32, 'grape', .28)
    b = ground(8, 252, 188) + open_gift(170, 188, 1.15, 'grape')
    m = Person('m', 88, 188, .82, legs=((-6, -2), (10, 6)), arms=((70, 150), (40, 130)), expr='surprised', look=.8, outfit='shirt', top='shirt2', hat=False, lean=4)
    b += m.draw()
    b += p.star(172, 96, .9) + p.sparkle(146, 70, .7) + p.sparkle(200, 64, .6, 'leaf') + p.sparkle(160, 44, .5, 'rose')
    b += p.confetti(140, 50, 70, 50, 4)
    return w, b


# ------------------------------------------------------------ projects

@art('projects-calendar')
def _():
    w = wash((150, 96), 104, 74, 'rose', .26) + wash((60, 150), 44, 30, 'leaf', .26)
    b = ground(8, 252, 188) + p.calendar(128, 22, 1.8, marked=((2, 1),))
    fm = Person('f', 96, 188, .84, arms=((-14, -6), (140, 150)), expr='focus', look=.8, outfit='shirt', top='grape', hat=False)
    h = fm.hand_at(1)
    b += fm.shadow() + fm.back() + p.pencil(h[0] + 12, h[1] - 10, .9, -135, 34, 'sun') + fm.front_()
    return w, b


@art('projects-compare')
def _():
    w = wash((130, 112), 116, 72, 'sky', .3)
    b = ground(8, 252, 188)
    b += p.chart_board(56, 188, 1.05, bars=(34, 22, 28, 12), colors=('rose', 'rose', 'rose', 'rose'))
    b += p.chart_board(204, 188, 1.05, bars=(14, 24, 32, 42), colors=('leaf', 'leaf', 'leaf', 'leaf'))
    m = Person('m', 130, 188, .8, arms=((-24, 40), (30, 170)), expr='think', look=-.3, outfit='coat', hat=False)
    b += m.draw()
    b += f'<text x="116" y="18" font-size="16" font-weight="700" fill="currentColor" opacity=".7">?</text>'
    return w, b


@art('projects-equipment')
def _():
    w = wash((120, 104), 110, 74, 'leaf', .28)
    b = rect(22, 78, 118, 4, 'sand', 1) + line('M30 82 L36 90 M132 82 L126 90', 1.6)
    b += p.lux_meter(40, 64, .8) + p.gauge(76, 64, .8) + p.dosimeter(104, 70, .9) + p.thermometer(126, 78, .8)
    b += ground(8, 252, 188) + p.cone(232, 188, .7)
    m = Person('m', 180, 188, .84, legs=((-8, 0), (10, 4)), arms=((-6, -2), (16, 6)), expr='proud', look=-.5)
    h = m.hand_at(0)
    b += m.shadow() + m.back() + p.toolbox(h[0] - 2, h[1] + 30, 1, color='rose') + m.front_()
    return w, b


@art('projects-show')
def _():
    w = wash((130, 118), 114, 66, 'sun', .28) + wash((50, 60), 36, 28, 'sky', .28)
    b = ground(8, 252, 188)
    b += line('M28 176 Q80 162 128 172 Q180 182 236 166', 1.4, op=.45)
    b += p.flag(30, 176, .7, 'rose') + p.flag(232, 166, .6, 'leaf') + p.flag(186, 180, .8, 'vest')
    fm = Person('f', 110, 188, .8, legs=((-20, -6), (24, 8)), arms=((-24, -10), (40, 130)), expr='smile', look=.7, lean=3)
    h = fm.hand_at(1)
    b += fm.shadow() + fm.back() + p.clipboard(h[0] + 6, h[1] - 4, .75, -8, 3) + fm.front_()
    return w, b


# ------------------------------------------------------------ reports

@art('reports-create')
def _():
    w = wash((126, 104), 110, 76, 'grape', .26) + wash((60, 40), 36, 26, 'sun', .28)
    b = ground(8, 252, 188) + p.paper(64, 50, 1.3, -4, 1, chart=True) + p.pie(110, 44, .8)
    fm = Person('f', 170, 150, .82, legs=((-88, 0), (-84, -4)), arms=((-40, -85), (8, 60)), expr='focus', look=-.7, outfit='shirt', top='rose', hat=False, anchor='pelvis', hair_down=True, front=0)
    b += p.chair(176, 188, .95, -1, 'leaf') + fm.back() + p.desk(88, 140, 110, 48)
    b += p.paper(98, 132, .8, 86, 3) + p.mug(52, 140, .9, 'sky')
    h = fm.hand_at(0)
    b += p.pencil(h[0] - 4, h[1] + 3, .7, 30, 30, 'sun') + fm.front_()
    return w, b


@art('reports-index')
def _():
    w = wash((130, 116), 104, 72, 'sky', .28)
    b = ground(8, 252, 188)
    m = Person('m', 130, 188, .84, legs=((-18, -4), (20, 6)), arms=((-26, 44), (26, -44)), expr='worried', look=.3, lean=-3)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    cx, cy = (h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2
    stack = ''
    for i, c in enumerate(('sky', 'rose', 'leaf', 'grape')):
        dx = (-4, 3, -2, 8)[i]
        stack += p.binder(cx + dx - 15, cy + 6 - i * 15, 1, c, 90 if i < 3 else 100)
    b += m.shadow() + m.back() + stack + m.front_()
    b += line('M164 60 Q172 54 174 46 M168 70 Q178 68 182 62', 1.4, op=.7)
    b += p.binder(210, 188, 1, 'vest') + p.binder(226, 188, 1, 'sky')
    return w, b


@art('reports-show')
def _():
    w = wash((126, 110), 110, 76, 'leaf', .28) + wash((90, 40), 40, 26, 'sky', .26)
    b = ground(8, 252, 188) + big_doc(88, 188, 1.25)
    fm = Person('f', 184, 188, .82, arms=((-88, -96), (14, 6)), expr='happy', look=-.6, outfit='coat', hat=False)
    b += fm.draw() + p.sparkle(40, 50, .6)
    return w, b


@art('reports-step-details')
def _():
    w = wash((130, 116), 108, 70, 'sand', .32) + wash((200, 50), 36, 28, 'sky', .26)
    b = ground(8, 252, 188) + p.plant(226, 188, 1, 'sky')
    m = Person('m', 124, 188, .84, arms=((20, 80), (10, -40)), expr='focus', look=.5, outfit='shirt', top='sky', hat=False)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    b += m.shadow() + m.back() + p.clipboard(h1[0] + 8, h1[1] - 4, 1, 6, 2, 'sand')
    b += p.pencil(h2[0] + 8, h2[1] - 8, .75, -135, 30, 'rose') + m.front_()
    return w, b


@art('reports-step-findings')
def _():
    w = wash((128, 110), 110, 74, 'sky', .28) + wash((70, 80), 44, 36, 'sun', .26)
    b = ground(8, 252, 188) + p.chart_board(76, 188, 1.35, bars=(16, 24, 42, 20), colors=('sky', 'sky', 'vest', 'sky'))
    fm = Person('f', 186, 188, .82, legs=((-8, -2), (6, 2)), arms=((-80, -110), (12, 6)), expr='curious', look=-.8, lean=-6, outfit='vest')
    h = fm.hand_at(0)
    b += fm.shadow() + fm.back() + p.magnifier(h[0] - 18, h[1] - 12, 1.3, 0) + fm.front_()
    return w, b


@art('reports-step-review')
def _():
    w = wash((130, 110), 110, 76, 'leaf', .3)
    b = ground(8, 252, 188) + p.fire_ext(30, 188, .9)
    m = Person('m', 150, 188, .84, legs=((-6, -2), (8, 2)), arms=((-50, -100), (-20, -130)), expr='determined', look=-.6, outfit='vest', front=1)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    b += m.shadow() + m.back() + checklist(h1[0] - 20, h1[1] - 6, 1.1, 2)
    b += p.pencil(h2[0] - 8, h2[1] + 2, .8, -150, 32, 'sun') + m.front_()
    return w, b


@art('reports-verify-show')
def _():
    w = wash((120, 108), 112, 76, 'leaf', .3) + wash((74, 70), 46, 40, 'sun', .26)
    b = ground(8, 252, 188) + p.shield(78, 100, 2.6, 'leaf')
    b += line('M78 26 L78 16 M30 44 L22 38 M126 44 L134 38', 1.8, 'sun')
    m = Person('m', 180, 188, .84, arms=((-10, -4), (160, 175)), expr='proud', look=-.4, outfit='jacket', top='leaf', hat=False)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + thumb(h[0] - 1, h[1] - 2) + m.front_()
    b += p.sparkle(228, 30, .6)
    return w, b


# ------------------------------------------------------------ tools

@art('tools-advisor')
def _():
    w = wash((136, 108), 112, 76, 'sky', .28) + wash((170, 50), 44, 30, 'sun', .28)
    b = ground(8, 252, 188) + p.signpost(170, 188, 1.1, colors=('leaf', 'sun', 'sky')) + p.tree(236, 188, .7)
    fm = Person('f', 82, 188, .82, arms=((-18, 20), (30, 170)), expr='think', look=.7, outfit='jacket', top='sand', hat=False)
    b += fm.draw()
    b += f'<text x="112" y="34" font-size="18" font-weight="700" fill="currentColor" opacity=".7">?</text>'
    return w, b


@art('tools-calculations')
def _():
    w = wash((136, 110), 110, 76, 'leaf', .28)
    b = ground(8, 252, 188)
    b += result_card(160, 40, 56, 'leaf', '0.42') + result_card(172, 70, 56, 'sun', '86') + result_card(160, 100, 56, 'sky', '1.3')
    fm = Person('f', 100, 188, .84, arms=((10, 4), (40, 150)), expr='calm', look=.6, outfit='jacket', top='grape', hat=False, hair_down=True)
    h = fm.hand_at(1)
    b += fm.shadow() + fm.back() + p.phone(h[0] + 2, h[1] - 10, 1.2, 8, 'sky', 'lines') + fm.front_()
    return w, b


@art('tools-index')
def _():
    w = wash((130, 100), 112, 78, 'sun', .28)
    pb = p.pegboard(28, 28, 118, 74)
    pb += p.wrench(48, 44, .9) + p.magnifier(80, 50, .8, 0) + p.thermometer(112, 80, .9)
    pb += p.tape(126, 44, .6) + line('M44 38 L44 42 M80 36 L80 38 M112 42 L112 46', 1.4)
    b = pb + ground(8, 252, 188) + p.toolbox(58, 188, 1.1, color='sky')
    m = Person('m', 176, 188, .84, arms=((-150, -170), (14, 4)), expr='curious', look=-.7, outfit='vest', lean=-4)
    h = m.hand_at(0)
    b += m.shadow() + m.back() + p.sound_meter(h[0] - 2, h[1] - 6, .8, -10, waves=False) + m.front_()
    return w, b


# ------------------------------------------------------------ workspace

@art('workspace-legal')
def _():
    w = wash((126, 110), 110, 76, 'grape', .26) + wash((210, 80), 36, 36, 'sand', .3)
    b = ground(8, 252, 188) + p.desk(210, 136, 70, 52) + p.balance(210, 136, .85, 'leaf', 'rose', -6)
    m = Person('m', 110, 188, .84, arms=((10, 60), (-10, -60)), expr='focus', look=.1, outfit='shirt', top='shirt', hat=False)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    b += m.shadow() + m.back() + p.scroll_doc((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 + 6, 1.05) + m.front_()
    return w, b


@art('workspace-search')
def _():
    w = wash((130, 118), 112, 70, 'sky', .28)
    b = ground(8, 252, 188)
    b += ellipse((170, 184), 5, 2.6, INK, None, op=.35) + ellipse((194, 180), 5, 2.6, INK, None, op=.25) + ellipse((222, 184), 5, 2.6, INK, None, op=.18)
    m = Person('m', 90, 188, .84, legs=((-14, -4), (22, 6)), arms=((40, 70), (60, 80)), expr='curious', look=.8, lean=22, tilt=6)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.magnifier(h[0] + 30, h[1] + 24, 2, 180) + m.front_()
    b += f'<text x="206" y="120" font-size="16" font-weight="700" fill="currentColor" opacity=".6">?</text>'
    return w, b


@art('workspace-status')
def _():
    w = wash((126, 110), 112, 76, 'leaf', .28) + wash((70, 60), 40, 34, 'sky', .26)
    b = ground(8, 252, 188) + p.server(70, 188, 1.4) + p.server(118, 188, 1.05)
    b += line('M44 30 Q50 24 56 30 M40 22 Q50 12 60 22', 1.6, 'leaf')
    fm = Person('f', 190, 188, .82, arms=((-30, -130), (-20, -110)), expr='calm', look=-.5, outfit='vest', front=0)
    h1, h2 = fm.hand_at(0), fm.hand_at(1)
    b += fm.shadow() + fm.back() + tablet((h1[0] + h2[0]) / 2 - 2, (h1[1] + h2[1]) / 2 - 12, .8, -8) + fm.front_()
    b += p.check_badge(230, 40, .7)
    return w, b


@art('workspace-wallet')
def _():
    w = wash((130, 112), 110, 74, 'sun', .32)
    b = ground(8, 252, 188) + p.coins_stack(212, 188, 1.1, 5) + p.coins_stack(234, 188, .9, 3)
    m = Person('m', 110, 188, .84, arms=((-40, -110), (40, 130)), expr='wink', look=.4, outfit='jacket', top='sky', hat=False, front=0)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    b += m.shadow() + m.back() + p.wallet(h1[0] - 6, h1[1] - 2, 1) + p.coin(h2[0] + 4, h2[1] - 8, 1.2) + m.front_()
    b += p.coin(170, 52, .9) + p.coin(186, 76, .7) + p.sparkle(160, 30, .5)
    return w, b


# ------------------------------------------------------------ errors

@art('errors-403', '0 0 240 240')
def _():
    w = wash((130, 136), 100, 88, 'grape', .26)
    b = p.door(166, 228, 66, 146, 'sky') + p.padlock(166, 150, 1.25) + ground(8, 232, 228)
    fm = Person('f', 76, 228, .9, arms=((-12, -4), (80, 150)), expr='worried', look=.8, hat=True, outfit='vest')
    b += fm.draw()
    b += line('M124 104 Q128 100 124 96 M130 108 Q136 100 130 92', 1.4, op=.6)
    return w, b


@art('errors-419', '0 0 240 240')
def _():
    w = wash((120, 146), 100, 80, 'sand', .3) + wash((60, 80), 40, 34, 'sky', .26)
    b = ground(8, 232, 228) + p.hourglass(70, 228, 2.4)
    m = Person('m', 150, 212, .9, legs=((80, 90), (70, 100)), arms=((-20, 20), (20, 40)), expr='sleepy', look=-.3, tilt=-12, outfit='jacket', top='grape', hat=False, anchor='pelvis', lean=-6)
    b += m.draw() + p.zzz(166, 64, 1.3)
    return w, b


@art('errors-429', '0 0 240 240')
def _():
    w = wash((120, 136), 104, 88, 'rose', .26)
    b = ground(8, 232, 228)
    fm = Person('f', 120, 228, .9, legs=((-10, -4), (14, 6)), arms=((-140, -170), (140, 170)), expr='oops', look=0, outfit='coat', hat=False)
    b += fm.draw()
    b += p.paper(40, 60, .7, -24, 2) + p.paper(70, 26, .6, 18, 2) + p.paper(200, 44, .7, 30, 2) + p.paper(176, 16, .55, -14, 2) + p.paper(210, 110, .6, -36, 2)
    b += p.paper(34, 170, .7, -30, 2) + p.paper(208, 176, .7, 40, 2)
    return w, b


@art('errors-503', '0 0 240 240')
def _():
    w = wash((120, 140), 104, 86, 'sun', .3)
    b = ground(8, 232, 228) + p.maint_sign(196, 228, 1.2) + p.cone(30, 228, .9)
    m = Person('m', 104, 228, .92, legs=((-6, -2), (8, 4)), arms=((-14, -6), (60, 20)), expr='calm', look=.6)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.cone(h[0] + 4, h[1] + 30, .75) + m.front_()
    return w, b


@art('login-welcome', '0 0 220 240')
def _():
    w = wash((112, 132), 96, 90, 'sun', .3) + wash((160, 60), 36, 30, 'leaf', .26)
    b = light([(78, 228), (142, 228), (196, 236), (30, 236)], .3)
    b += p.door(110, 228, 76, 170, 'sky', open_=True) + ground(8, 212, 228)
    fm = Person('f', 116, 228, .9, arms=((-12, -4), (140, 170)), expr='happy', look=.3, outfit='shirt', top='leaf', hat=False)
    b += fm.draw()
    b += line('M172 58 Q178 64 176 72 M180 50 Q190 60 186 76', 1.6, op=.7)
    return w, b
