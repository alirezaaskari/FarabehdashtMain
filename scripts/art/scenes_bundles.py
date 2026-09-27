"""صحنه‌های بسته‌های راه‌حل، تاریخچه تغییرات ماده و حالت بدون اینترنت."""
from engine import Person, wash, ground, line, path
from registry import art
import props as p


@art('bundles-index')
def _():
    w = wash((130, 112), 112, 74, 'sun', .28) + wash((50, 60), 36, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.book_stack(52, 188, .8, ('leaf', 'sky', 'rose'))
    m = Person('m', 160, 188, .82, legs=((-10, -4), (10, 4)), arms=((-26, 44), (26, -44)), expr='happy', look=-.3)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    cx, cy = (h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2
    items = p.book(cx - 8, cy - 4, .8, 'grape', -12) + p.clipboard(cx + 10, cy - 8, .55, 10)
    b += m.shadow() + m.back() + items + p.box(cx, cy + 28, .95, open_=True, color='sand') + m.front_()
    return w, b + p.sparkle(214, 40, .7)


@art('bundles-show')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .26)
    b = ground(8, 252, 188) + p.book(50, 136, .9, 'sky', -14) + p.clipboard(80, 128, .7, 8)
    b += p.box(66, 188, 1.1, open_=True, color='sun') + p.calendar(124, 152, .7)
    f = Person('f', 184, 188, .82, arms=((-60, -110), (12, 5)), expr='smile', look=-.5, outfit='jacket', top='grape', hat=False, front=0)
    return w, b + f.draw()


@art('empty-bundles-index', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'grape', .28)
    return w, ground(10, 190, 140) + p.gift(100, 140, 1.3, 'grape') + p.sparkle(146, 56, .7, 'sun')


@art('chemicals-history')
def _():
    w = wash((130, 112), 112, 74, 'sky', .26) + wash((60, 60), 36, 26, 'sand', .3)
    b = ground(8, 252, 188) + p.calendar(56, 70, 1.1, marked=((0, 1), (2, 2), (4, 3)))
    b += p.flask(46, 188, 1, 'leaf') + p.drum(96, 188, .8, 'sky')
    m = Person('m', 188, 188, .82, arms=((-30, -150), (12, 5)), expr='focus', look=-.3, outfit='coat', hat=False, front=0)
    h = m.hand_at(0)
    b += m.shadow() + m.back() + p.clipboard(h[0] - 4, h[1] - 12, .8, -8) + m.front_()
    return w, b


@art('empty-chem-history', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sand', .32)
    return w, ground(10, 190, 140) + p.calendar(84, 80, 1.1, blank=True) + p.stopwatch(146, 110, .9)


@art('tools-offline')
def _():
    w = wash((130, 112), 112, 74, 'sky', .26) + wash((70, 56), 40, 28, 'paper', .5)
    b = ground(8, 252, 188) + p.cloud(70, 58, 1.2)
    b += line('M50 36 L92 80', 2.4)
    f = Person('f', 170, 188, .82, arms=((-30, -150), (14, 6)), expr='calm', look=-.2, outfit='vest', front=0)
    h = f.hand_at(0)
    b += f.shadow() + f.back() + p.phone(h[0] - 2, h[1] - 10, .8, 0, 'leaf') + f.front_()
    b += p.chart_board(234, 188, .6, (10, 18, 26), ('leaf', 'sun', 'sky'))
    return w, b
