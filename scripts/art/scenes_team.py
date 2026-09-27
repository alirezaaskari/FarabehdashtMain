"""صحنه‌های تیم (بخش ۱۹-۶): خرید تیم، صفحه تیم و کتابخانه مشترک."""
from engine import Person, wash, ground
from registry import art
import props as p


@art('team-buy')
def _():
    w = wash((130, 112), 116, 74, 'leaf', .24) + wash((210, 48), 36, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.calculator(222, 176, .9)
    m = Person('m', 70, 188, .8, arms=((-24, 40), (24, -40)), expr='happy', look=.5, outfit='jacket', top='sky', hat=False)
    f = Person('f', 128, 188, .8, arms=((14, 6), (54, 112)), expr='smile', look=-.3, outfit='shirt', top='grape', hat=False)
    m2 = Person('m', 184, 188, .76, arms=((-12, -6), (12, 6)), expr='wink', look=-.5, outfit='vest', top='leaf', hat=False)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    b += m.shadow() + m.back() + p.clipboard((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 + 2, .7, ticks=3) + m.front_()
    b += f.draw() + m2.draw() + p.sparkle(150, 36, .6, 'sun')
    return w, b


@art('team-paid')
def _():
    w = wash((130, 110), 112, 74, 'sun', .24)
    b = ground(8, 252, 188) + p.confetti(130, 50, 1.6) + p.check_badge(206, 70, .9)
    f = Person('f', 96, 188, .84, arms=((-50, -118), (50, -118)), expr='laugh', look=.3, outfit='coat', hat=False)
    b += f.draw() + p.sparkle(60, 60, .8, 'sun') + p.gift(200, 188, .9)
    return w, b


@art('team-hub')
def _():
    w = wash((130, 112), 112, 74, 'sky', .24) + wash((56, 50), 34, 26, 'leaf', .26)
    b = ground(8, 252, 188) + p.whiteboard(170, 52, 90, 60, 'chart') + p.plant(236, 188, 1)
    m = Person('m', 70, 188, .84, arms=((14, 6), (54, 112)), expr='proud', look=.5, outfit='vest', front=1)
    b += m.draw()
    return w, b


@art('empty-team', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sky', .26)
    b = ground(10, 190, 140) + p.chair(64, 140, .8, 1, 'sky') + p.chair(104, 140, .8, 1, 'sun') + p.chair(144, 140, .8, -1, 'leaf')
    return w, b


@art('team-library')
def _():
    w = wash((130, 112), 112, 74, 'grape', .22) + wash((212, 50), 34, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.shelf(186, 70, 100, 2, 38) + p.cloud_upload(64, 52, .9)
    f = Person('f', 104, 188, .84, arms=((-20, 40), (20, -40)), expr='focus', look=.6, outfit='jacket', top='sand', hat=False)
    h = f.hand_at(0)
    b += f.shadow() + f.back() + p.folder(h[0] + 4, h[1] - 2, .7, 'sun') + f.front_()
    return w, b


@art('empty-team-library', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'grape', .24)
    b = ground(10, 190, 140) + p.folder(84, 112, 1.3, 'grape') + p.padlock(142, 100, 1)
    return w, b
