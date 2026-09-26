"""صحنه‌های نمونه (معیار سبک): آزمایشگاه، اندازه‌گیری، کتاب، خطای ۴۰۴ و…"""
from engine import Person, wash, ground, rect, line, circle, path, poly, INK
from registry import art
import props as p


@art('chemicals-index')
def _():
    w = wash((150, 110), 110, 80, 'leaf') + wash((60, 60), 50, 40, 'sky', .3)
    b = p.shelf(62, 96, 96, 2, 36) + ground(8, 252, 188)
    f = Person('f', 170, 188, .82, arms=((-14, -8), (150, 165)), expr='curious', look=.6, outfit='coat', hat=False, tilt=6)
    b += f.shadow() + f.back() + p.flask(f.hand_at(1)[0] + 6, f.hand_at(1)[1] + 8, .9, 'leaf') + f.front_()
    b += p.beaker(40, 188, .9, 'rose') + p.test_tubes(88, 188, .9)
    return w, b


@art('projects-index')
def _():
    w = wash((140, 120), 110, 70, 'sun') + wash((210, 60), 40, 30, 'sky', .3)
    b = ground(8, 252, 188) + p.machine(206, 188, .95) + p.cone(30, 188, .8)
    f = Person('m', 110, 188, .82, legs=((80, 0), (-12, -95)), arms=((-20, -4), (80, 120)), expr='focus', look=.7, lean=6)
    h = f.hand_at(1)
    b += f.shadow() + f.back() + p.sound_meter(h[0] + 2, h[1] - 12, .95) + f.front_()
    return w, b


@art('encyclopedia-index')
def _():
    w = wash((130, 110), 110, 80, 'sky')
    b = ground(8, 252, 188) + p.book_stack(120, 188, 1)
    f = Person('m', 120, 132, .82, legs=((88, 0), (82, -2)), arms=((20, 100), (40, 110)), expr='calm', look=.2, outfit='shirt', hat=False, anchor='pelvis', lean=-3)
    h1, h2 = f.hand_at(0), f.hand_at(1)
    b += f.back() + p.book((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 - 4, 1.05, 'rose', open_=True) + f.front_()
    b += p.plant(222, 188, 1) + p.lamp(34, 188, .9)
    return w, b


@art('errors-404', '0 0 240 240')
def _():
    w = wash((120, 140), 100, 90, 'rose', .28)
    b = ground(8, 232, 228) + p.signpost(196, 228, .75, blank=True)
    f = Person('m', 100, 228, .92, arms=((-40, 60), (40, -60)), expr='oops', look=-.3)
    b += f.shadow() + f.back()
    b += path('M50 132 L76 124 L100 132 L126 124 L152 132 L156 166 L130 174 L104 166 L78 174 L52 166 Z', 'sun') + line('M76 124 L78 174 M100 132 L104 166 M126 124 L130 174', 1.3)
    b += line('M62 156 Q80 140 94 152 Q110 162 120 146', 1.4, op=.7) + line('M136 142 L144 150 M144 142 L136 150', 1.8)
    b += f.front_()
    b += f'<text x="40" y="48" font-size="26" font-weight="700" fill="currentColor" opacity=".85">?</text><text x="170" y="36" font-size="18" font-weight="700" fill="currentColor" opacity=".6">?</text>'
    return w, b


@art('workspace-notifications')
def _():
    w = wash((130, 110), 100, 80, 'sun')
    b = ground(8, 252, 188) + p.bell(80, 60, 1.3, -12)
    f = Person('f', 170, 188, .82, arms=((-30, -130), (30, 150)), expr='surprised', look=-.6, hair_down=True, outfit='jacket', top='grape', hat=False)
    b += f.draw() + p.envelope(40, 150, .9, -10, 'sky') + p.sparkle(120, 40, .8)
    return w, b


@art('workspace-dashboard')
def _():
    w = wash((140, 110), 110, 80, 'sky') + wash((220, 40), 40, 30, 'sun', .3)
    b = ground(8, 252, 188) + p.window(30, 30, 60, 52)
    f = Person('f', 130, 150, .82, legs=((90, 0), (86, 4)), arms=((30, 100), (40, 110)), expr='smile', look=.5, outfit='shirt', top='shirt', hat=False, anchor='pelvis')
    b += p.chair(126, 188, .95, 1, 'rose') + f.back() + p.desk(192, 138, 110, 50) + p.laptop(186, 138, .95, 'sky', 'chart', flip=True) + f.front_()
    b += p.mug(226, 138, .9, 'leaf') + p.plant(236, 138, .6)
    return w, b



@art('reports-verify-form')
def _():
    w = wash((130, 110), 100, 80, 'leaf', .3)
    b = ground(8, 252, 188) + p.paper(60, 110, 1.9, -6, 3) + p.qr(62, 138, .7)
    f = Person('f', 170, 188, .82, arms=((-15, -5), (-70, -110)), expr='focus', look=-.7, outfit='vest', hat=True)
    h = f.hand_at(1)
    b += f.shadow() + f.back() + p.phone(h[0] - 4, h[1] - 8, 1.1, 10, 'sky', 'qr') + f.front_()
    b += line('M128 118 L92 128 M128 118 L96 146', 1.2, 'leaf') + p.check_badge(226, 50, .9)
    return w, b


@art('errors-500', '0 0 240 240')
def _():
    w = wash((120, 140), 100, 90, 'sky', .3)
    b = ground(8, 232, 228) + p.machine(70, 228, 1.2, 'leaf', broken=True) + p.smoke(80, 140, 1)
    f = Person('f', 170, 228, .9, arms=((-40, -120), (-70, -100)), expr='determined', look=-.7, lean=-4)
    h = f.hand_at(1)
    b += f.shadow() + f.back() + p.wrench(h[0], h[1], 1, 60) + f.front_()
    return w, b


@art('expert-index')
def _():
    w = wash((130, 120), 115, 75, 'grape', .28)
    b = ground(8, 252, 188)
    m = Person('m', 70, 188, .78, arms=((-10, -4), (70, 140)), expr='curious', look=.7)
    f = Person('f', 190, 188, .78, arms=((-70, -130), (12, 4)), expr='happy', look=-.7, outfit='coat', hat=False)
    b += m.draw() + f.draw()
    b += p.speech(20, 8, 56, 32, 'paper', 1, 'q') + p.speech(160, 4, 70, 36, 'sun', -1, 'lines')
    return w, b


@art('tools-calculation')
def _():
    w = wash((130, 110), 110, 80, 'sun', .3)
    b = ground(8, 252, 188)
    f = Person('m', 110, 188, .84, arms=((20, 110), (40, 130)), expr='proud', look=.5, outfit='jacket', top='sky', hat=False)
    h = f.hand_at(1)
    b += f.shadow() + f.back() + p.calculator(h[0] + 6, h[1] - 6, 1.3, '85.2') + f.front_()
    b += p.sound_meter(206, 120, 1.1, 15) + p.sparkle(226, 60, .9) + p.sparkle(40, 50, .6, 'leaf')
    return w, b
