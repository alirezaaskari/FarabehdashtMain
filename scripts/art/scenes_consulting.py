"""صحنه‌های مشاوره (بخش ۱۹): فهرست مشاوران، صفحه مشاور و ویرایش آن."""
from engine import Person, wash, ground
from registry import art
import props as p


@art('consultants-index')
def _():
    w = wash((130, 110), 114, 74, 'leaf', .24) + wash((56, 46), 38, 28, 'sky', .26)
    b = ground(8, 252, 188) + p.window(22, 40, 58, 52) + p.plant(236, 188, 1.1)
    f = Person('f', 86, 188, .82, legs=((-6, -2), (6, 2)), arms=((-24, 40), (24, -40)), expr='smile', look=.4, outfit='coat', hat=False)
    h1, h2 = f.hand_at(0), f.hand_at(1)
    b += f.shadow() + f.back() + p.folder((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 + 4, .72, 'sky') + f.front_()
    m = Person('m', 172, 188, .84, arms=((-50, -118), (14, 6)), expr='happy', look=-.4, outfit='jacket', top='sand', hat=False, front=0)
    b += m.draw() + p.id_card(214, 60, .9, 8, 'leaf') + p.sparkle(232, 40, .6, 'sun')
    return w, b


@art('consultant-profile-edit')
def _():
    w = wash((130, 112), 112, 74, 'sun', .24)
    b = ground(8, 252, 188)
    m = Person('m', 118, 150, .8, legs=((90, 0), (86, 4)), arms=((60, 96), (40, 86)), expr='calm', look=.5, outfit='shirt', top='leaf', hat=False, anchor='pelvis')
    b += p.chair(112, 188, .9, 1, 'sky') + m.back() + p.desk(176, 138, 110, 50) + p.laptop(184, 134, 1.3, 'paper', 'lines')
    b += m.front_() + p.id_card(60, 70, 1.1, -6, 'sun') + p.mug(222, 134, .7, 'grape')
    b += p.speech(150, 20, 70, 36, 'paper', -1, 'lines') + p.check_badge(214, 44, .7)
    return w, b


@art('empty-consultants-index', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'leaf', .26)
    b = ground(10, 190, 140) + p.id_card(78, 96, 1.6, -8, 'sky') + p.magnifier(128, 108, 1.1, -20)
    return w, b


@art('empty-consultant-answers', '0 0 200 150')
def _():
    w = wash((100, 88), 70, 44, 'grape', .24)
    b = ground(10, 190, 140) + p.speech(52, 36, 72, 40, 'paper', 1, 'lines') + p.speech(112, 70, 60, 34, 'sun', -1, 'lines')
    b += p.star(158, 122, .7, 'leaf') + p.sparkle(40, 110, .6, 'sky')
    return w, b
