"""خوش‌آمد اولین ورود: دو کارشناس کنار تابلوی راهنما، یکی دست تکان می‌دهد."""
from engine import Person, wash, ground
from registry import art
import props as p


@art('identity-welcome')
def _():
    w = wash((130, 112), 116, 74, 'leaf', .24) + wash((208, 46), 38, 26, 'sun', .28)
    b = ground(8, 252, 188) + p.signpost(132, 188, 1.05) + p.sparkle(214, 40, .7, 'sun') + p.sparkle(46, 58, .5, 'sky')
    f = Person('f', 70, 188, .82, arms=((-46, -128), (18, 40)), expr='laugh', look=.5, outfit='vest', top='leaf')
    m = Person('m', 194, 188, .82, arms=((-14, 30), (40, -60)), expr='smile', look=-.5, outfit='jacket', top='sky', hat=False)
    b += f.draw() + m.draw()
    return w, b
