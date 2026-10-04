"""صحنه صفحه منابع و روش کار بانک مواد."""
from engine import Person, wash, ground
from registry import art
import props as p


@art('chemicals-sources')
def _():
    w = wash((130, 112), 112, 74, 'grape', .24) + wash((64, 70), 40, 28, 'sky', .28)
    b = ground(8, 252, 188) + p.shelf(70, 94, 100, rows=2, gap=36, colors=('sky', 'rose', 'leaf', 'sun', 'grape'))
    b += p.desk(70, 150, 110, 38) + p.book(48, 146, .8, 'sky', open_=True) + p.beaker(96, 146, .7, 'leaf', 20)
    b += p.check_badge(132, 120, .6)
    f = Person('f', 196, 188, .82, arms=((-40, -110), (12, 5)), expr='focus', look=-.5, outfit='coat', hat=False, front=0)
    h = f.hand_at(0)
    return w, b + f.shadow() + f.back() + p.magnifier(h[0] - 6, h[1] - 6, .8, -20) + f.front_()
