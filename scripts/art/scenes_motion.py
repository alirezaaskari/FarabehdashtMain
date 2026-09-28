"""صحنه‌های آموزش‌های متحرک: کنار هر کارت آموزش یک تصویر یکتا."""
from engine import Person, wash, ground, rect, line
from registry import art
import props as p

HOLD = ((12, 112), (12, 5))      # دست راست جلوی سینه، چپ آویزان
POINT = ((-60, -110), (12, 5))    # اشاره به سمت چپ تصویر


def holding(person, prop_at):
    """آدم با شیئی در دست راست؛ شیء بین تن و بازوی جلو می‌نشیند."""
    h = person.hand_at(0)
    return person.shadow() + person.back() + prop_at(h) + person.front_()


@art('motion-reports-intro')
def _():
    w = wash((130, 112), 112, 74, 'sky', .26)
    b = ground(8, 252, 188) + p.desk(96, 138, 130, 50) + p.laptop(76, 136, .85, 'leaf', 'chart')
    b += p.paper(122, 128, .7, -6, 4, chart=True)
    f = Person('f', 206, 188, .8, arms=POINT, expr='focus', look=-.6, outfit='coat', hat=False, front=0)
    return w, b + f.draw() + p.rocket_arrow(142, 70, 176, 56, 'leaf')


@art('motion-reports-verify')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .26)
    b = ground(8, 252, 188) + p.paper(70, 150, 1.4, 0, 3, fold=False) + p.qr(70, 150, .8)
    m = Person('m', 180, 188, .82, arms=((-40, -100), (12, 5)), expr='curious', look=-.6, outfit='shirt', top='sand', hat=False, front=0)
    return w, b + holding(m, lambda h: p.phone(h[0] - 6, h[1] - 8, .8, -10, 'sky')) + p.check_badge(120, 44, .8)


@art('motion-reports-outro')
def _():
    w = wash((130, 112), 112, 74, 'sun', .3)
    b = ground(8, 252, 188)
    f = Person('f', 76, 188, .8, arms=((10, 5), (60, 90)), expr='proud', look=.5, outfit='coat', hat=False, front=1)
    m = Person('m', 188, 188, .8, arms=((-60, -90), (12, 5)), expr='happy', look=-.5, outfit='vest', front=0)
    hf = f.hand_at(1)
    return w, b + f.draw() + m.draw() + p.folder(hf[0] + 20, hf[1] + 14, .8, 'sky') + p.sparkle(132, 40, .7)


@art('motion-projects-intro')
def _():
    w = wash((130, 112), 112, 74, 'sand', .32)
    b = ground(8, 252, 188) + p.factory(58, 188, .7) + p.machine(120, 188, .6)
    for x, n in ((40, '۱'), (110, '۲')):
        b += p.flag(x, 188, .5, 'rose')
    m = Person('m', 200, 188, .8, arms=HOLD, expr='determined', look=-.4, outfit='vest')
    return w, b + holding(m, lambda h: p.clipboard(h[0] - 4, h[1] + 2, .7, -6))


@art('motion-tool-intro')
def _():
    w = wash((130, 112), 112, 74, 'grape', .26)
    b = ground(8, 252, 188) + p.whiteboard(20, 30, 110, 76, 'text')
    f = Person('f', 190, 188, .82, arms=HOLD, expr='think', look=-.4, outfit='vest', hat=True)
    return w, b + holding(f, lambda h: p.calculator(h[0] - 4, h[1] + 2, .7))


@art('motion-chemicals-intro')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .28)
    b = ground(8, 252, 188) + p.drum(46, 188, 1, 'sky') + p.gas_cylinder(90, 188, .9, 'rose') + p.flask(120, 188, .8, 'sun')
    m = Person('m', 196, 188, .82, arms=HOLD, expr='focus', look=-.3, outfit='coat', hat=False)
    return w, b + holding(m, lambda h: p.paper(h[0] - 6, h[1] + 2, .7, -8, 5))


@art('motion-expert-intro')
def _():
    w = wash((130, 112), 112, 74, 'sky', .26)
    b = ground(8, 252, 188) + p.speech(100, 24, 64, 36, 'sun', -1, 'q')
    f = Person('f', 60, 188, .8, arms=((40, 150), (10, 5)), expr='curious', look=.5, outfit='shirt', top='rose', hat=False, front=0)
    m = Person('m', 206, 188, .8, arms=((-10, -5), (-40, -150)), expr='smile', look=-.4, outfit='coat', hat=False, front=1)
    return w, b + holding(f, lambda h: p.phone(h[0] + 4, h[1] - 8, .8, 8, 'sky')) + m.draw()


@art('motion-expert-queue-intro')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .26)
    b = ground(8, 252, 188) + p.speech(16, 20, 54, 30, 'paper', 1, 'q') + p.speech(20, 60, 50, 28, 'paper', 1, 'q')
    m = Person('m', 150, 150, .8, legs=((90, 0), (86, 4)), arms=((60, 110), (50, 100)), expr='focus', look=.4, outfit='shirt', top='sky', hat=False, anchor='pelvis')
    b += p.chair(146, 188, .9, 1, 'sand') + m.draw() + p.desk(212, 138, 80, 50) + p.laptop(206, 136, .75, 'leaf')
    return w, b + p.check_badge(212, 40, .9)


@art('motion-consultants-intro')
def _():
    w = wash((130, 112), 112, 74, 'sun', .28)
    b = ground(8, 252, 188) + p.shield(130, 70, 1.2, 'leaf', 'lock') + p.coins_stack(130, 120, .8)
    f = Person('f', 52, 188, .8, arms=((40, 100), (10, 5)), expr='smile', look=.5, outfit='jacket', top='grape', hat=False, front=0)
    m = Person('m', 208, 188, .8, arms=((-10, -5), (-40, -100)), expr='happy', look=-.5, outfit='coat', hat=False)
    return w, b + f.draw() + m.draw()


@art('motion-jobs-intro')
def _():
    w = wash((130, 112), 112, 74, 'sky', .28)
    b = ground(8, 252, 188) + p.job_board(78, 120, 120, 84, ('sun', 'leaf', 'rose', 'sky'))
    m = Person('m', 204, 188, .82, arms=POINT, expr='curious', look=-.6, outfit='jacket', top='leaf', hat=False, front=0)
    return w, b + m.draw()


@art('motion-employer-intro')
def _():
    w = wash((130, 112), 112, 74, 'rose', .24)
    b = ground(8, 252, 188) + p.desk(80, 138, 120, 50) + p.id_card(50, 126, .8, -6, 'sky') + p.id_card(84, 126, .8, 4, 'sun') + p.id_card(118, 126, .8, -3, 'leaf')
    f = Person('f', 200, 188, .82, arms=POINT, expr='think', look=-.5, outfit='jacket', top='rose', hat=False, front=0)
    return w, b + p.job_board(80, 76, 70, 48) + f.draw()


@art('motion-sell-intro')
def _():
    w = wash((130, 112), 112, 74, 'sun', .3)
    b = ground(8, 252, 188) + p.cloud_upload(80, 60, 1.1) + p.paper(60, 150, 1, -6, 4) + p.coins_stack(110, 188, .8)
    m = Person('m', 200, 188, .82, arms=POINT, expr='happy', look=-.5, outfit='shirt', top='grape', hat=False, front=0)
    return w, b + m.draw()


@art('motion-team-intro')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .26) + wash((130, 40), 40, 24, 'sun', .3)
    b = ground(8, 252, 188) + p.star(130, 30, .9)
    people = [
        Person('m', 48, 188, .66, arms=((10, 5), (40, 110)), expr='smile', look=.4, outfit='vest', front=1),
        Person('f', 104, 188, .66, arms=((-40, -150), (40, 150)), expr='happy', look=.2, outfit='coat', hat=False),
        Person('m', 160, 188, .66, arms=((-20, -8), (20, 8)), expr='laugh', look=-.1, outfit='shirt', top='sky', hat=False),
        Person('f', 214, 188, .66, arms=((-40, -110), (-10, -4)), expr='smile', look=-.4, outfit='vest', front=0),
    ]
    return w, b + ''.join(q.draw() for q in people)


@art('motion-writing-intro')
def _():
    w = wash((130, 112), 112, 74, 'sand', .32)
    f = Person('f', 104, 150, .8, legs=((90, 0), (86, 4)), arms=((40, 108), (50, 100)), expr='focus', look=.5, outfit='shirt', top='leaf', hat=False, anchor='pelvis', front=1)
    b = ground(8, 252, 188) + p.book_stack(30, 188, .7, ('rose', 'sky', 'sun'))
    b += p.chair(100, 188, .9, 1, 'sky') + f.back() + p.desk(180, 138, 110, 50) + p.laptop(176, 136, .8, 'paper', 'lines') + f.front_()
    return w, b + p.lamp(228, 138, .8)


@art('motion-course-intro')
def _():
    w = wash((130, 112), 112, 74, 'grape', .26)
    b = ground(8, 252, 188) + p.camera(60, 150, 1) + p.video_screen(100, 30, 86, 56, 'sky')
    m = Person('m', 214, 188, .8, arms=((-40, -150), (12, 5)), expr='happy', look=-.4, outfit='jacket', top='sun', hat=False)
    return w, b + m.draw()


@art('motion-product-intro')
def _():
    w = wash((130, 112), 112, 74, 'sun', .28)
    b = ground(8, 252, 188) + p.paper(66, 132, .9, -10, 4) + p.clipboard(94, 128, .7, 8)
    b += p.box(84, 188, 1.1, open_=True, color='sand')
    f = Person('f', 196, 188, .82, arms=POINT, expr='proud', look=-.5, outfit='vest', front=0)
    return w, b + f.draw()


@art('motion-exam-questions-intro')
def _():
    w = wash((130, 112), 112, 74, 'sky', .26)
    b = ground(8, 252, 188) + p.whiteboard(18, 26, 120, 84, None)
    for i in range(4):
        y = 40 + i * 16
        b += rect(30, y, 10, 10, 'leaf' if i == 2 else 'paper', 2, sw=1.2) + line(f'M46 {y + 5} L118 {y + 5}', 1.3, op=.7)
    m = Person('m', 196, 188, .82, arms=POINT, expr='think', look=-.6, outfit='coat', hat=False, front=0)
    return w, b + m.draw()


@art('motion-webinars-intro')
def _():
    w = wash((130, 112), 112, 74, 'rose', .24)
    b = ground(8, 252, 188) + p.video_screen(76, 10, 108, 64, 'leaf')
    viewers = [
        Person('f', 60, 188, .5, arms=((10, 5), (-10, -5)), expr='curious', look=.3, outfit='shirt', top='sky', hat=False),
        Person('m', 130, 188, .5, arms=((10, 5), (-10, -5)), expr='smile', look=0, outfit='vest'),
        Person('f', 200, 188, .5, arms=((10, 5), (-10, -5)), expr='happy', look=-.3, outfit='coat', hat=False),
    ]
    return w, b + ''.join(q.draw() for q in viewers)
