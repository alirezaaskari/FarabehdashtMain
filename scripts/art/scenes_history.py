"""صحنه‌های تاریخچه تغییرات ماده شیمیایی."""
from engine import Person, wash, ground
from registry import art
import props as p


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
