"""صحنه‌های وبینار، بسته‌های راه‌حل و صفحه بدون اینترنت (بخش ۱۸-۸ تا ۱۸-۱۰)."""
from engine import Person, wash, ground, rect, line, circle, path
from registry import art
import props as p


def live_dot(x, y):
    """نشان «زنده»: دایره قرمز با دو موج."""
    o = circle((x, y), 4, 'rose', None)
    return o + path(f'M{x + 8} {y - 6} Q{x + 12} {y} {x + 8} {y + 6} M{x + 13} {y - 10} Q{x + 19} {y} {x + 13} {y + 10}', None, 'currentColor', 1.4)


# ------------------------------------------------------------------ webinars

@art('webinars-index')
def _():
    w = wash((120, 100), 110, 72, 'sky', .26) + wash((212, 44), 34, 26, 'rose', .26)
    b = ground(8, 252, 188) + p.video_screen(18, 30, 132, 84, 'sky') + live_dot(34, 20)
    b += line('M42 114 L34 188 M126 114 L134 188', 2.4) + p.calendar(210, 126, .66, ((2, 1),))
    f = Person('f', 176, 188, .82, arms=((-54, -112), (12, 5)), expr='happy', look=-.5, outfit='jacket', top='grape', hat=False, front=0)
    return w, b + f.draw()


@art('webinars-show')
def _():
    w = wash((130, 110), 112, 74, 'sun', .26)
    b = ground(8, 252, 188)
    m = Person('m', 100, 150, .8, legs=((90, 0), (86, 4)), arms=((40, 104), (-30, -40)), expr='focus', look=.5, outfit='shirt', top='sky', hat=False, anchor='pelvis')
    hx, hy = m.head_at()
    b += p.chair(94, 188, .9, 1, 'leaf') + m.back() + p.desk(176, 138, 110, 50) + p.laptop(180, 134, 1.3, 'sky', 'play')
    b += m.front_() + p.headphones(hx, hy - 4, 1.35) + p.mug(222, 134, .7, 'rose')
    b += p.speech(150, 22, 64, 34, 'leaf', -1, 'lines')
    return w, b


@art('empty-webinars-index', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sky', .28)
    b = ground(10, 190, 140) + p.calendar(46, 58, 1.2, blank=True) + p.headphones(146, 118, 1.2)
    return w, b


# ------------------------------------------------------------------ bundles

@art('bundles-index')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .26) + wash((60, 50), 40, 30, 'sun', .26)
    b = ground(8, 252, 188) + p.box(78, 188, 1.35, open_=True, color='sand', label=False)
    b += p.folder(62, 118, .7, 'sun') + p.flask(96, 118, .6, 'sky', bubbles=False) + p.star(112, 92, .55, 'grape')
    m = Person('m', 190, 188, .82, arms=((-64, -120), (12, 5)), expr='smile', look=-.5, outfit='vest', front=0)
    return w, b + m.draw()


@art('bundles-show')
def _():
    w = wash((130, 112), 112, 74, 'grape', .24)
    b = ground(8, 252, 188) + p.gift(206, 188, 1.25, 'leaf') + p.sparkle(232, 108, .7)
    f = Person('f', 104, 188, .82, legs=((-16, -4), (16, 4)), arms=((-24, 40), (24, -40)), expr='proud', look=.4, outfit='coat', hat=False)
    h1, h2 = f.hand_at(0), f.hand_at(1)
    cx, cy = (h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2
    b += f.shadow() + f.back() + p.clipboard(cx, cy + 2, .8, ticks=3) + f.front_()
    return w, b


@art('empty-bundles-index', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'leaf', .28)
    b = ground(10, 190, 140) + p.box(84, 140, 1.25, open_=True, color='sand', label=False) + p.sparkle(140, 58, .7, 'sky')
    return w, b


# ------------------------------------------------------------------ offline

@art('tools-offline')
def _():
    w = wash((130, 112), 112, 74, 'sand', .32) + wash((200, 44), 40, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.cloud(196, 44, 1.1) + line('M178 30 L214 60', 2.4, 'rose')
    f = Person('f', 104, 188, .84, arms=((-20, 40), (-60, -110)), expr='calm', look=.5, outfit='vest')
    b += f.shadow() + f.back() + p.phone(f.hand_at(0)[0] + 2, f.hand_at(0)[1] - 6, 1, -8, 'leaf', 'check') + f.front_()
    b += p.sound_meter(206, 150, .9, -10)
    return w, b
