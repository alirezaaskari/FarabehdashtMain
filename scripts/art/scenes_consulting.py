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


# ------------------------------------------------------------------ services and orders (19-3)

@art('consulting-services')
def _():
    w = wash((130, 112), 112, 74, 'sky', .24) + wash((210, 46), 34, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.whiteboard(150, 50, 96, 60, 'chart') + p.sound_meter(226, 150, .8, -8)
    m = Person('m', 80, 188, .84, arms=((14, 6), (54, 112)), expr='proud', look=.5, outfit='vest', front=1)
    b += m.draw() + p.clipboard(206, 176, .6, 12)
    return w, b


@art('empty-consulting-services', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sky', .26)
    b = ground(10, 190, 140) + p.clipboard(80, 100, 1.3, -6, 0) + p.pencil(132, 112, 1.1, 30)
    return w, b


@art('consulting-order-create')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .24)
    b = ground(8, 252, 188) + p.calendar(200, 70, .8, ((1, 2), (3, 1)))
    f = Person('f', 104, 188, .84, arms=((-20, 40), (20, -40)), expr='smile', look=.5, outfit='shirt', top='grape', hat=False)
    h = f.hand_at(0)
    b += f.shadow() + f.back() + p.phone(h[0] + 2, h[1] - 6, 1, -8, 'sky', 'check') + f.front_()
    b += p.shield(206, 160, .8, 'leaf', 'check')
    return w, b


@art('consulting-orders-mine')
def _():
    w = wash((130, 110), 112, 74, 'sun', .24) + wash((210, 60), 34, 26, 'leaf', .26)
    b = ground(8, 252, 188) + p.shield(206, 92, 1, 'leaf', 'check') + p.coins_stack(214, 188, 1)
    m = Person('m', 104, 188, .84, arms=((-24, 40), (24, -40)), expr='happy', look=.4, outfit='jacket', top='sky', hat=False)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    b += m.shadow() + m.back() + p.clipboard((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 + 2, .8, ticks=3) + m.front_()
    return w, b


@art('consulting-orders-incoming')
def _():
    w = wash((130, 112), 112, 74, 'grape', .22) + wash((60, 48), 36, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.bell(58, 58, 1, 0, True) + p.envelope(208, 64, 1.1, 8, 'paper')
    f = Person('f', 140, 188, .84, arms=((-24, 40), (24, -40)), expr='happy', look=-.3, outfit='coat', hat=False)
    h1, h2 = f.hand_at(0), f.hand_at(1)
    b += f.shadow() + f.back() + p.clipboard((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 + 2, .8, ticks=2) + f.front_()
    return w, b


@art('empty-consulting-orders', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sun', .26)
    b = ground(10, 190, 140) + p.calendar(76, 64, 1.1, blank=True) + p.hourglass(142, 140, .9)
    return w, b


@art('empty-consulting-incoming', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'grape', .24)
    b = ground(10, 190, 140) + p.envelope(92, 92, 1.5, -6) + p.zzz(146, 50, .8)
    return w, b


# ------------------------------------------------------------------ 19-4 بررسی گزارش

@art('consulting-reviews-pick')
def _():
    w = wash((130, 112), 112, 74, 'sand', .30) + wash((206, 50), 36, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.paper(190, 100, 2.1, -4, lines=5, chart=True)
    m = Person('m', 92, 188, .84, arms=((14, 6), (54, 112)), expr='focus', look=.6, outfit='shirt', top='leaf', hat=False)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + m.front_() + p.magnifier(h[0] + 24, h[1] - 24, 1.1, 90)
    return w, b


@art('empty-consulting-reviews', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sand', .30)
    b = ground(10, 190, 140) + p.paper(84, 96, 1.6, -6, lines=4) + p.magnifier(134, 70, 1, -10)
    return w, b


# ------------------------------------------------------------------ 19-5 خدمات تخصصی و آزمایشگاه‌ها

@art('directory-index')
def _():
    w = wash((130, 112), 114, 74, 'sky', .24) + wash((60, 50), 38, 28, 'leaf', .26)
    b = ground(8, 252, 188) + p.signpost(52, 188, 1.1) + p.factory(222, 188, .7, 'sand', 'rose', False)
    f = Person('f', 128, 188, .84, arms=((14, 6), (54, 112)), expr='happy', look=-.5, outfit='vest', top='sky', front=1)
    b += f.draw() + p.test_tubes(200, 110, .8)
    return w, b


@art('directory-listing')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .24) + wash((206, 50), 36, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.desk(170, 146, 110, 42) + p.flask(146, 140, .9, 'leaf') + p.beaker(186, 140, .9, 'sky')
    m = Person('m', 76, 188, .84, arms=((-24, 40), (24, -40)), expr='focus', look=.6, outfit='coat', hat=False)
    h1, h2 = m.hand_at(0), m.hand_at(1)
    b += m.shadow() + m.back() + p.clipboard((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 + 2, .8, ticks=2) + m.front_()
    b += p.lux_meter(210, 140, .8, -6) + p.sparkle(214, 40, .6, 'sun')
    return w, b


@art('empty-directory', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sky', .26)
    b = ground(10, 190, 140) + p.signpost(84, 140, 1, blank=True) + p.magnifier(140, 90, 1, -20)
    return w, b


@art('empty-directory-listing', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'leaf', .26)
    b = ground(10, 190, 140) + p.test_tubes(84, 140, 1.2, ('paper', 'paper', 'paper')) + p.zzz(140, 56, .8)
    return w, b


@art('directory-contacts')
def _():
    w = wash((130, 112), 112, 74, 'sun', .24) + wash((212, 52), 34, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.envelope(200, 70, 1.1, 8, 'paper') + p.speech(190, 110, 50, 30, 'leaf', -1, 'lines')
    m = Person('m', 104, 188, .84, arms=((-20, 40), (20, -40)), expr='smile', look=.5, outfit='jacket', top='grape', hat=False)
    h = m.hand_at(0)
    b += m.shadow() + m.back() + p.phone(h[0] + 2, h[1] - 6, 1, -8, 'sky', 'lines') + m.front_()
    return w, b


@art('empty-directory-contacts', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sun', .26)
    b = ground(10, 190, 140) + p.envelope(88, 94, 1.4, -8) + p.pencil(140, 112, 1, 30)
    return w, b


@art('lab-contacts')
def _():
    w = wash((130, 112), 112, 74, 'grape', .22) + wash((56, 50), 36, 26, 'leaf', .26)
    b = ground(8, 252, 188) + p.desk(148, 146, 150, 42) + p.test_tubes(92, 140, .8) + p.laptop(160, 142, 1.2, 'paper', 'lines')
    f = Person('f', 222, 188, .84, arms=((-50, -118), (14, 6)), expr='happy', look=-.5, outfit='coat', hat=False, front=0)
    b += f.draw() + p.bell(196, 48, .8, 0, True)
    return w, b


@art('empty-lab-contacts', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'grape', .24)
    b = ground(10, 190, 140) + p.flask(78, 136, 1.2, 'grape') + p.id_card(132, 88, 1.2, 8, 'paper')
    return w, b
