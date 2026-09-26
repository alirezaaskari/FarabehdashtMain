"""صحنه‌های صفحه اصلی: قهرمان پهن، کارت‌های «امروز چه کاری داری؟»، بخش‌ها و قدم‌ها."""
from engine import Person, wash, ground, rect, line, circle, path, poly, ellipse, INK
from registry import art
import props as p


@art('home-hero', '0 0 1200 260')
def _():
    w = (wash((180, 170), 170, 80, 'sky', .3) + wash((560, 180), 200, 70, 'sun', .28)
         + wash((930, 170), 190, 80, 'leaf', .3) + wash((1110, 70), 60, 40, 'rose', .22))
    b = ground(10, 1190, 248)
    b += p.cloud(260, 44, 1) + p.cloud(720, 30, .8) + p.bird(420, 40) + p.bird(440, 52, .8) + p.bird(860, 36, .9) + p.sun(1110, 60, 1)
    b += p.factory(30, 248, 1.25) + p.crane(300, 248, 1)
    b += p.tank(430, 248, 1, 'sky') + p.tank(478, 248, .8, 'leaf') + p.pipe(452, 200, 478, 200)
    b += p.forklift(560, 248, 1.1) + p.drum(612, 222, .6, 'rose', False)
    b += p.drum(660, 248, .9, 'sky') + p.drum(700, 248, .9, 'leaf') + p.drum(680, 206, .9, 'sun')
    m = Person('m', 790, 248, .92, legs=((80, 0), (-12, -95)), arms=((-20, -4), (80, 125)), expr='focus', look=.7, lean=6)
    h = m.hand_at(1)
    b += p.machine(900, 248, 1.3, 'leaf') + m.shadow() + m.back() + p.sound_meter(h[0] + 3, h[1] - 12, 1.05) + m.front_()
    f = Person('f', 1020, 248, .92, legs=((-18, -6), (16, 0)), arms=((25, 100), (-14, -6)), expr='smile', look=-.5)
    hf = f.hand_at(0)
    b += f.shadow() + f.back() + p.clipboard(hf[0] - 6, hf[1] - 4, 1, -8) + p.air_pump(f.at(f.hip[1])[0] + 4, f.at(f.hip[1])[1] - 4, .9) + f.front_()
    b += p.cone(1100, 248, .9) + p.cone(1140, 248, .9) + line('M1100 232 L1140 232', 2)
    b += p.tree(1170, 248, 1) + p.tree(376, 248, .8, 'sky') + p.gas_cylinder(740, 248, .8, 'grape')
    return w, b


@art('home-hero-mobile', '0 0 420 230')
def _():
    w = wash((140, 150), 130, 60, 'sky', .3) + wash((310, 150), 100, 60, 'sun', .3)
    b = ground(8, 412, 218) + p.cloud(250, 40, .8) + p.bird(330, 30)
    b += p.factory(14, 218, .95)
    m = Person('m', 238, 218, .78, arms=((-12, -6), (130, 160)), expr='happy', look=.4)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.sound_meter(h[0] + 2, h[1] - 10, .95) + m.front_()
    f = Person('f', 340, 218, .78, arms=((30, 110), (-10, -4)), expr='smile', look=-.4)
    hf = f.hand_at(0)
    b += f.shadow() + f.back() + p.clipboard(hf[0] - 4, hf[1] - 4, .9, -10) + f.front_() + p.cone(396, 218, .7)
    return w, b


@art('home-task-measure', '0 0 180 160')
def _():
    w = wash((90, 100), 75, 55, 'sun', .32)
    b = ground(6, 174, 150) + p.ear_muffs(140, 60, .9)
    m = Person('m', 80, 150, .66, legs=((-6, -2), (10, 4)), arms=((20, 60), (110, 165)), expr='determined', look=.5, lean=4)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.sound_meter(h[0] + 2, h[1] - 10, .85) + m.front_()
    return w, b


@art('home-task-chemical', '0 0 180 160')
def _():
    w = wash((90, 100), 75, 55, 'leaf', .32)
    b = ground(6, 174, 150) + p.drum(140, 150, 1, 'sky') + p.gas_cylinder(166, 150, .6, 'rose')
    f = Person('f', 70, 150, .66, arms=((20, 100), (-10, -4)), expr='curious', look=.7, outfit='coat', hat=False, tilt=6)
    h = f.hand_at(0)
    b += f.shadow() + f.back() + p.paper(h[0] + 8, h[1] - 6, .7, 8, 3) + f.front_()
    return w, b


@art('home-task-report', '0 0 180 160')
def _():
    w = wash((90, 100), 75, 55, 'sky', .32)
    b = ground(6, 174, 150) + p.chart_board(142, 150, .75)
    m = Person('m', 70, 150, .66, arms=((-30, 20), (30, 120)), expr='proud', look=.5, outfit='shirt', top='sky', hat=False)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.paper(h[0] + 8, h[1] - 10, .95, 10, 3, chart=True) + m.front_()
    return w, b


@art('home-task-read', '0 0 180 160')
def _():
    w = wash((90, 100), 75, 55, 'grape', .28)
    b = ground(6, 174, 150)
    b += path('M40 150 L40 104 Q40 94 52 94 L110 94 Q122 94 122 104 L122 150', 'rose') + rect(34, 112, 94, 20, 'rose', 6)
    f = Person('f', 82, 112, .66, legs=((88, 0), (84, 2)), arms=((-28, 55), (28, -55)), expr='smile', look=.3, outfit='jacket', top='leaf', hat=False, hair_down=True, anchor='pelvis')
    h0, h1 = f.hand_at(0), f.hand_at(1)
    b += f.back() + p.book((h0[0] + h1[0]) / 2, (h0[1] + h1[1]) / 2 - 3, .8, 'sky', open_=True) + f.front_()
    b += p.mug(150, 150, .9, 'sun') + rect(138, 128, 26, 4, 'sand', 1)
    return w, b


@art('home-section-tools', '0 0 200 130')
def _():
    w = wash((100, 80), 88, 48, 'sun', .3)
    b = ground(6, 194, 122) + p.toolbox(60, 122, 1) + p.sound_meter(118, 96, .9, -10, False) + p.lux_meter(150, 94, .9) + p.dosimeter(182, 110, .9) + p.tape(22, 108, .8)
    return w, b


@art('home-section-chemicals', '0 0 200 130')
def _():
    w = wash((100, 80), 88, 48, 'leaf', .3)
    b = ground(6, 194, 122) + p.flask(50, 122, 1.1, 'leaf') + p.beaker(96, 122, 1, 'rose', 30) + p.test_tubes(148, 122, .95, ('sun', 'grape', 'sky')) + p.respirator(176, 60, .8)
    return w, b


@art('home-section-encyclopedia', '0 0 200 130')
def _():
    w = wash((100, 80), 88, 48, 'sky', .3)
    b = ground(6, 194, 122) + p.lamp(28, 122, .9) + p.book_stack(88, 122, .7, ('rose', 'sun', 'leaf')) + p.book(88, 62, 1.2, 'grape', open_=True) + p.plant(170, 122, .9, 'sky')
    return w, b


@art('home-section-courses', '0 0 200 130')
def _():
    w = wash((100, 80), 88, 48, 'grape', .26)
    b = ground(6, 194, 122) + p.video_screen(28, 30, 110, 72) + p.headphones(166, 104, 1.1) + p.book(162, 116, .9, 'sun', 6)
    return w, b


@art('home-section-shop', '0 0 200 130')
def _():
    w = wash((100, 80), 88, 48, 'rose', .26)
    b = ground(6, 194, 122) + p.bag(70, 122, 1.4, 'leaf') + p.folder(140, 122, 1, 'sun') + p.coin(176, 104) + p.coin(186, 92, .8)
    return w, b


@art('home-section-expert', '0 0 200 130')
def _():
    w = wash((100, 80), 88, 48, 'sky', .28)
    b = ground(6, 194, 122) + p.chair(60, 122, .8, 1, 'sun') + p.chair(142, 122, .8, -1, 'rose')
    b += p.speech(20, 10, 58, 34, 'paper', 1, 'q') + p.speech(114, 20, 66, 36, 'leaf', -1, 'check') + p.mug(100, 122, .8, 'grape')
    return w, b


@art('home-workflow', '0 0 260 150')
def _():
    w = wash((130, 90), 118, 55, 'leaf', .26)
    b = ground(6, 254, 140) + p.sound_meter(36, 96, 1.1, -8) + p.rocket_arrow(62, 96, 100, 96, 'sun')
    b += p.calculator(130, 98, 1.1, '85') + p.rocket_arrow(158, 96, 192, 96, 'sun') + p.paper(224, 96, 1.3, 4, 3) + p.check_badge(238, 118, .7)
    return w, b


@art('home-pro-band', '0 0 240 200')
def _():
    w = wash((120, 120), 105, 70, 'sun', .3)
    b = ground(8, 232, 188) + p.padlock(186, 188, 1.3, open_=True) + p.sparkle(210, 110, .8) + p.sparkle(56, 40, .6, 'leaf')
    f = Person('f', 100, 188, .82, arms=((-12, -6), (130, 160)), expr='wink', look=.5, outfit='jacket', top='grape', hat=False)
    h = f.hand_at(1)
    b += f.shadow() + f.back() + p.key(h[0] - 6, h[1] - 4, 1, -30) + f.front_()
    return w, b


@art('home-writer', '0 0 240 200')
def _():
    w = wash((120, 120), 105, 70, 'rose', .26)
    b = ground(8, 232, 188) + p.lamp(196, 126, .9)
    m = Person('m', 88, 144, .8, legs=((90, 0), (86, 4)), arms=((40, 110), (50, 110)), expr='think', look=.5, outfit='shirt', top='sand', hat=False, anchor='pelvis', lean=6)
    b += p.chair(84, 188, .9, 1, 'sky') + m.back() + p.desk(160, 126, 110, 62) + p.paper(146, 118, .9, -80, 3) + p.pencil(m.hand_at(1)[0] + 4, m.hand_at(1)[1] + 6, .7, 30) + m.front_()
    b += p.book_stack(214, 126, .45, ('leaf', 'sun'))
    return w, b


@art('home-step-input', '0 0 160 110')
def _():
    w = wash((80, 60), 68, 42, 'sky', .28)
    b = ground(6, 154, 102) + p.phone(60, 60, 1.8, -6, 'sky', 'lines') + p.sound_meter(118, 64, 1, 12)
    return w, b


@art('home-step-result', '0 0 160 110')
def _():
    w = wash((80, 60), 68, 42, 'sun', .3)
    b = ground(6, 154, 102) + p.gauge(64, 58, 1.4, 20) + p.book(126, 94, .8, 'leaf', -4) + p.book(126, 84, .75, 'rose', 3)
    return w, b


@art('home-step-report', '0 0 160 110')
def _():
    w = wash((80, 60), 68, 42, 'leaf', .28)
    b = ground(6, 154, 102) + p.paper(62, 60, 1.8, -4, 4, chart=True) + p.qr(118, 74, .8) + p.check_badge(124, 36, .7)
    return w, b


@art('home-role-teach', '0 0 120 100')
def _():
    w = wash((60, 58), 50, 36, 'grape', .28)
    b = ground(4, 116, 94) + p.whiteboard(8, 14, 58, 40, 'text', legs=False)
    f = Person('f', 88, 94, .44, arms=((-110, -150), (10, 4)), expr='happy', look=-.5, outfit='jacket', top='sky', hat=False)
    return w, b + f.draw()


@art('home-role-sell', '0 0 120 100')
def _():
    w = wash((60, 58), 50, 36, 'sun', .3)
    stall = p.market_stall(46, 94, 72, 'leaf') + p.folder(34, 54, .55, 'sky') + p.folder(60, 54, .55, 'rose')
    b = ground(4, 116, 94) + p.group(stall, 'translate(9.2 18.8) scale(.8)')
    m = Person('m', 98, 94, .42, arms=((-60, -130), (12, 4)), expr='smile', look=-.4)
    return w, b + m.draw()


@art('home-role-answer', '0 0 120 100')
def _():
    w = wash((60, 58), 50, 36, 'sky', .3)
    b = ground(4, 116, 94) + p.speech(8, 6, 50, 28, 'leaf', 1, 'check')
    f = Person('f', 84, 94, .44, arms=((20, 120), (-10, -4)), expr='think', look=-.3, outfit='coat', hat=False)
    h = f.hand_at(0)
    return w, b + f.shadow() + f.back() + p.phone(h[0] + 4, h[1] - 4, .7, 0, 'sky', 'bubble') + f.front_()
