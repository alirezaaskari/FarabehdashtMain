"""صحنه‌های تازه صفحه اصلی: کارهای روزانه تازه، کاریابی، مشاوره، یادگیری و پرسش‌های پرتکرار."""
from engine import Person, wash, ground, rect, line
from registry import art
import props as p

TASK = '0 0 180 160'
TILE = '0 0 200 130'


@art('home-task-exam', TASK)
def _():
    w = wash((90, 100), 75, 55, 'grape', .3)
    b = ground(6, 174, 150) + p.book_stack(40, 150, .6, ('grape', 'sun', 'sky')) + p.stopwatch(150, 44, .9)
    f = Person('f', 104, 150, .66, arms=((-14, -6), (26, 150)), expr='determined', look=-.3, outfit='shirt', top='sky', hat=False)
    h = f.hand_at(1)
    return w, b + f.shadow() + f.back() + p.book(h[0] + 4, h[1] + 2, .6, 'grape', 8) + f.front_()


@art('home-task-job', TASK)
def _():
    w = wash((90, 100), 75, 55, 'sky', .3)
    b = ground(6, 174, 150) + p.job_board(52, 104, 64, 48)
    m = Person('m', 124, 150, .66, arms=((-60, -110), (8, 4)), expr='curious', look=-.6, outfit='jacket', top='leaf', hat=False, front=0)
    h = m.hand_at(1)
    return w, b + m.shadow() + m.back() + p.briefcase(h[0] + 2, h[1] + 12, .55) + m.front_()


@art('home-task-hire', TASK)
def _():
    w = wash((90, 100), 75, 55, 'leaf', .3)
    b = ground(6, 174, 150) + p.speech(64, 10, 50, 28, 'sun', 1, 'check')
    m = Person('m', 48, 150, .62, arms=((10, 5), (40, 110)), expr='smile', look=.5, outfit='shirt', top='sand', hat=False, front=1)
    f = Person('f', 134, 150, .62, arms=((-40, -110), (-10, -4)), expr='happy', look=-.5, outfit='coat', hat=False, front=0)
    return w, b + m.draw() + f.draw()


@art('home-task-event', TASK)
def _():
    w = wash((90, 100), 75, 55, 'rose', .28)
    b = ground(6, 174, 150) + p.video_screen(14, 22, 92, 60, 'sky')
    f = Person('f', 136, 150, .66, arms=((10, 5), (-50, -150)), expr='think', look=-.5, outfit='vest', hat=False)
    return w, b + f.draw()


@art('home-career', '0 0 260 200')
def _():
    w = wash((130, 118), 110, 72, 'sky', .28) + wash((200, 60), 40, 28, 'sun', .3)
    b = ground(8, 252, 188) + p.stairs(20, 188, 3, 32, 22, 'sky') + p.flag(118, 122, .7, 'leaf')
    m = Person('m', 88, 122, .52, arms=((-150, -170), (20, 8)), expr='laugh', look=.2, outfit='jacket', top='sun', hat=False)
    b += m.draw()
    f = Person('f', 206, 188, .78, arms=((20, 150), (14, 6)), expr='smile', look=-.4, outfit='coat', hat=False, front=0)
    h = f.hand_at(0)
    b += f.shadow() + f.back() + p.id_card(h[0] - 6, h[1] - 6, .9, -8, 'leaf') + f.front_()
    return w, b


@art('home-consult', '0 0 260 200')
def _():
    w = wash((130, 118), 110, 72, 'leaf', .26)
    b = ground(8, 252, 188) + p.desk(130, 138, 120, 50) + p.laptop(112, 136, .8) + p.check_badge(150, 40, 1)
    m = Person('m', 60, 150, .8, legs=((90, 0), (86, 4)), arms=((40, 100), (30, 80)), expr='smile', look=.5, outfit='shirt', top='sky', hat=False, anchor='pelvis')
    f = Person('f', 210, 188, .8, arms=((-40, -110), (12, 5)), expr='calm', look=-.6, outfit='coat', hat=False, front=0)
    return w, b + p.chair(56, 188, .9, 1, 'sand') + m.draw() + f.draw()


@art('home-grow-exam', TILE)
def _():
    w = wash((100, 80), 70, 40, 'grape', .28)
    b = ground(10, 190, 120) + p.clipboard(70, 70, 1.1, 0, 4) + p.check_badge(124, 56, .8, 'sun')
    return w, b


@art('home-grow-events', TILE)
def _():
    w = wash((100, 80), 70, 40, 'rose', .26)
    b = ground(10, 190, 120) + p.video_screen(52, 26, 96, 62, 'leaf') + p.calendar(150, 80, .55)
    return w, b


@art('home-grow-bundles', TILE)
def _():
    w = wash((100, 80), 70, 40, 'sun', .3)
    b = ground(10, 190, 120) + p.book(78, 70, .8, 'sky', -10) + p.clipboard(104, 62, .6, 6)
    return w, b + p.box(94, 120, 1.05, open_=True, color='sand') + p.sparkle(150, 34, .7)


@art('home-role-consult', '0 0 120 100')
def _():
    w = wash((60, 58), 50, 36, 'leaf', .3)
    b = ground(4, 116, 94) + p.check_badge(26, 30, .7)
    m = Person('m', 80, 94, .44, arms=((-30, -150), (10, 4)), expr='proud', look=-.3, outfit='shirt', top='grape', hat=False, front=0)
    h = m.hand_at(0)
    return w, b + m.shadow() + m.back() + p.id_card(h[0] - 4, h[1] - 6, .6, -8, 'sun') + m.front_()


@art('home-role-hire', '0 0 120 100')
def _():
    w = wash((60, 58), 50, 36, 'sky', .3)
    b = ground(4, 116, 94) + p.job_board(30, 60, 42, 30)
    f = Person('f', 82, 94, .44, arms=((-60, -110), (10, 4)), expr='smile', look=-.5, outfit='jacket', top='rose', hat=False, front=0)
    return w, b + f.draw()


@art('home-faq', '0 0 240 200')
def _():
    w = wash((120, 118), 100, 70, 'sand', .32)
    b = ground(8, 232, 188) + p.speech(10, 8, 58, 34, 'sun', 1, 'q') + p.speech(120, 12, 62, 36, 'leaf', -1, 'check')
    m = Person('m', 70, 188, .74, arms=((20, 150), (10, 5)), expr='curious', look=.4, outfit='vest', front=0)
    f = Person('f', 172, 188, .74, arms=((-40, -120), (-10, -4)), expr='smile', look=-.4, outfit='coat', hat=False, front=0)
    return w, b + m.draw() + f.draw()
