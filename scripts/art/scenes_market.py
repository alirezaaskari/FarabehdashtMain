"""صحنه‌های بازار پروژه (بخش ۲۱): فهرست پروژه، صفحه پروژه و میزکار کارفرما."""
from engine import Person, wash, ground
from registry import art
import props as p


@art('market-index')
def _():
    w = wash((130, 112), 116, 74, 'leaf', .22) + wash((214, 46), 34, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.factory(194, 188, .4, 'sand', smoke=False) + p.job_board(180, 104, 60, 46, pins=('leaf', 'sky'))
    f = Person('f', 48, 188, .8, arms=((-16, 30), (10, 20)), expr='happy', look=.8, outfit='coat', top='sky', hat=False)
    m = Person('m', 116, 188, .8, arms=((-12, 20), (40, -84)), expr='happy', look=.7, outfit='vest', top='leaf', front=1)
    hm = m.hand_at(1)
    b += f.draw() + m.shadow() + m.back() + p.sound_meter(hm[0] + 4, hm[1] - 8, .8, rot=-14) + m.front_()
    return w, b


@art('empty-market-index', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'leaf', .24)
    b = ground(10, 190, 140) + p.job_board(92, 116, 80, 58, pins=()) + p.balance(158, 140, .55)
    return w, b


@art('market-listing')
def _():
    w = wash((130, 112), 112, 74, 'sky', .22) + wash((56, 48), 34, 26, 'grape', .24)
    b = ground(8, 252, 188) + p.signpost(60, 188, .9, colors=('leaf', 'sun', 'sky')) + p.tank(222, 188, .7, 'sky')
    m = Person('m', 150, 188, .82, arms=((-12, 10), (30, -50)), expr='think', look=.4, outfit='jacket', top='sun', hat=False)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.clipboard(h[0] + 4, h[1] + 2, .7, ticks=2, fill='leaf') + m.front_()
    return w, b


@art('empty-market-listing', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sky', .24)
    b = ground(10, 190, 140) + p.clipboard(84, 104, 1.3, ticks=0, fill='paper') + p.lux_meter(150, 120, .9, rot=-12)
    return w, b


@art('market-show')
def _():
    w = wash((130, 112), 112, 74, 'sun', .22) + wash((60, 52), 34, 26, 'leaf', .24)
    b = ground(8, 252, 188) + p.paper(70, 92, 1.4, rot=-6, lines=5) + p.stairs(118, 188, 3, 26, 18, 'leaf') + p.check_badge(226, 62, .7)
    f = Person('f', 214, 188, .78, arms=((-46, -70), (14, 16)), expr='determined', look=-.7, outfit='vest', top='grape', front=0)
    b += f.draw()
    return w, b


@art('client-projects')
def _():
    w = wash((130, 112), 112, 74, 'grape', .2) + wash((214, 50), 34, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.desk(186, 150, 108, 38, 'sky') + p.folder(164, 146, .65, 'leaf') + p.folder(196, 146, .65, 'sun')
    b += p.binder(224, 146, .8, 'rose') + p.calendar(204, 58, .7)
    m = Person('m', 86, 188, .84, arms=((-10, 24), (36, -30)), expr='proud', look=.7, outfit='shirt', top='rose', hat=False)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.phone(h[0] + 2, h[1] - 6, .8, rot=-6, screen='leaf') + m.front_()
    return w, b


@art('empty-client-projects', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sun', .24)
    b = ground(10, 190, 140) + p.folder(90, 132, 1.4, 'sky', empty=True, papers=False) + p.factory(150, 140, .32, 'leaf', smoke=False)
    return w, b


@art('client-project-form')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .22) + wash((60, 46), 34, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.whiteboard(168, 124, 70, 48, content='chart') + p.pencil(226, 60, .9, rot=40, color='grape')
    f = Person('f', 92, 188, .84, arms=((-14, 20), (50, -80)), expr='focus', look=.9, outfit='jacket', top='leaf', hat=False)
    b += f.draw() + p.sparkle(48, 60, .7, 'sun')
    return w, b


@art('bid-form')
def _():
    w = wash((130, 112), 112, 74, 'sun', .22) + wash((216, 50), 34, 26, 'leaf', .26)
    b = ground(8, 252, 188) + p.desk(172, 150, 116, 38, 'sand') + p.laptop(156, 132, .8, screen='leaf', content='lines')
    b += p.coins_stack(206, 146, .7, n=3) + p.calculator(232, 142, .6, blank=True) + p.stopwatch(212, 54, .7)
    m = Person('m', 70, 188, .84, arms=((-10, 24), (44, -40)), expr='focus', look=.8, outfit='coat', top='sky', hat=False)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.paper(h[0] + 6, h[1] - 4, .55, rot=-10, lines=3, chart=True) + m.front_()
    return w, b


@art('provider-bids')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .2) + wash((54, 50), 34, 26, 'grape', .24)
    b = ground(8, 252, 188) + p.tray(196, 150, 1.1, papers=3) + p.envelope(228, 70, .9, rot=10, color='sun') + p.envelope(196, 86, .7, rot=-8)
    f = Person('f', 96, 188, .84, arms=((-14, 18), (40, -36)), expr='happy', look=.8, outfit='vest', top='rose', front=1)
    h = f.hand_at(1)
    b += f.shadow() + f.back() + p.clipboard(h[0] + 4, h[1] + 2, .7, ticks=3, fill='leaf') + f.front_()
    return w, b


@art('empty-provider-bids', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'grape', .22)
    b = ground(10, 190, 140) + p.tray(94, 136, 1.4, papers=0) + p.magnifier(150, 96, .9, rot=-20)
    return w, b


@art('bid-thread')
def _():
    w = wash((130, 112), 116, 74, 'sky', .22) + wash((130, 44), 40, 24, 'sun', .24)
    b = ground(8, 252, 188) + p.speech(104, 50, 58, 30, 'paper', tail=-1) + p.speech(124, 96, 46, 24, 'leaf', tail=1)
    b += p.shield(132, 158, .45, 'leaf', mark='check')
    f = Person('f', 52, 188, .8, arms=((-10, 22), (16, 8)), expr='curious', look=.9, outfit='jacket', top='sun', hat=False)
    m = Person('m', 212, 188, .8, arms=((-14, 20), (12, 10)), expr='smile', look=-.9, outfit='vest', top='sky', front=0)
    b += f.draw() + m.draw()
    return w, b


@art('client-project-bids')
def _():
    w = wash((130, 112), 112, 74, 'grape', .2) + wash((60, 50), 34, 26, 'leaf', .26)
    b = ground(8, 252, 188) + p.paper(150, 96, .9, rot=-8, lines=3) + p.paper(186, 90, .9, rot=4, lines=3, fill='sun') + p.paper(222, 98, .9, rot=10, lines=3)
    b += p.check_badge(190, 58, .55) + p.balance(204, 188, .55, left='leaf', right='sky', tilt=-5)
    m = Person('m', 84, 188, .84, arms=((-12, 22), (34, -44)), expr='think', look=.8, outfit='shirt', top='grape', hat=False)
    b += m.draw()
    return w, b


@art('empty-client-project-bids', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'leaf', .22)
    b = ground(10, 190, 140) + p.hourglass(80, 110, 1.1) + p.envelope(140, 112, 1.1, rot=-6, color='paper')
    return w, b


@art('market-invite')
def _():
    w = wash((130, 112), 112, 74, 'sun', .2) + wash((214, 48), 34, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.envelope(150, 70, 1.1, rot=-10, color='sun') + p.sparkle(176, 50, .6, 'leaf') + p.door(206, 188, 40, 86, 'leaf', open_=True)
    f = Person('f', 82, 188, .84, arms=((-12, 20), (44, -64)), expr='happy', look=.8, outfit='coat', top='leaf', hat=False)
    b += f.draw()
    return w, b
