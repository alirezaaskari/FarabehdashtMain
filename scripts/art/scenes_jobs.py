"""صحنه‌های کاریابی (بخش ۲۰-۱): فهرست آگهی، صفحه شرکت و میزکار کارفرما."""
from engine import Person, wash, ground
from registry import art
import props as p


@art('jobs-index')
def _():
    w = wash((130, 112), 116, 74, 'sky', .24) + wash((54, 48), 34, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.job_board(178, 150, 96, 70) + p.plant(240, 188, .9)
    f = Person('f', 76, 188, .84, arms=((-20, 40), (30, -70)), expr='curious', look=.8, outfit='jacket', top='grape', hat=False)
    b += f.draw() + p.sparkle(120, 40, .6, 'sun')
    return w, b


@art('empty-jobs-index', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'sky', .26)
    b = ground(10, 190, 140) + p.job_board(100, 118, 84, 62, pins=()) + p.cone(162, 140, .8)
    return w, b


@art('jobs-listing')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .22) + wash((212, 46), 34, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.signpost(196, 188, 1) + p.factory(58, 188, .7, 'sand', smoke=False)
    m = Person('m', 132, 188, .82, arms=((-14, 10), (50, -100)), expr='happy', look=.6, outfit='vest', top='sky', front=1)
    b += m.draw()
    return w, b


@art('empty-jobs-listing', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'leaf', .24)
    b = ground(10, 190, 140) + p.signpost(80, 140, .9, blank=True) + p.hourglass(144, 118, .9)
    return w, b


@art('job-show')
def _():
    w = wash((130, 112), 112, 74, 'sun', .24) + wash((210, 52), 34, 26, 'leaf', .26)
    b = ground(8, 252, 188) + p.paper(198, 94, 1.3, rot=6, lines=5) + p.magnifier(222, 120, .9, rot=-30)
    m = Person('m', 96, 188, .84, arms=((-14, 20), (20, -30)), expr='think', look=.7, outfit='shirt', top='leaf', hat=False)
    h = m.hand_at(1)
    b += m.shadow() + m.back() + p.briefcase(h[0] + 2, h[1] + 36, .8, 'pants') + m.front_()
    return w, b


@art('company-show')
def _():
    w = wash((130, 112), 112, 74, 'grape', .22) + wash((56, 48), 34, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.factory(118, 188, .8, 'sky', lit=True) + p.tree(228, 188, .7)
    f = Person('f', 70, 188, .82, arms=((-12, -6), (40, -110)), expr='proud', look=.5, outfit='vest', front=1)
    b += f.draw() + p.sparkle(212, 60, .7, 'sun')
    return w, b


@art('company-edit')
def _():
    w = wash((130, 112), 112, 74, 'sun', .24) + wash((212, 50), 34, 26, 'grape', .26)
    b = ground(8, 252, 188) + p.desk(170, 150, 110, 38) + p.laptop(176, 146, 1, 'sky', 'lines')
    b += p.id_card(206, 60, 1.3, rot=8, color='leaf') + p.folder(126, 146, .6, 'sun')
    m = Person('m', 76, 188, .84, arms=((20, 30), (50, 70)), expr='focus', look=.8, outfit='jacket', top='sand', hat=False)
    b += m.draw()
    return w, b


@art('employer-postings')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .24) + wash((56, 48), 34, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.chart_board(196, 188, 1, bars=(12, 20, 30, 38)) + p.envelope(60, 58, 1, rot=-8)
    f = Person('f', 116, 188, .84, arms=((-20, 40), (20, -40)), expr='happy', look=.6, outfit='coat', hat=False)
    h1, h2 = f.hand_at(0), f.hand_at(1)
    b += f.shadow() + f.back() + p.clipboard((h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2 + 2, .7, ticks=2, fill='sky') + f.front_()
    return w, b


@art('empty-employer-postings', '0 0 200 150')
def _():
    w = wash((100, 90), 70, 44, 'grape', .24)
    b = ground(10, 190, 140) + p.briefcase(86, 140, 1.3, 'sky', open_=True) + p.pencil(146, 120, .9, rot=30)
    return w, b


@art('posting-form')
def _():
    w = wash((130, 112), 112, 74, 'sky', .24) + wash((210, 48), 34, 26, 'sun', .26)
    b = ground(8, 252, 188) + p.paper(186, 110, 1.6, rot=-4, lines=6) + p.pencil(214, 96, 1, rot=35, color='leaf')
    m = Person('m', 88, 188, .84, arms=((-10, 20), (40, -60)), expr='determined', look=.6, outfit='vest', top='grape', vest='lime', front=1)
    b += m.draw() + p.speech(136, 34, 48, 28, 'paper', -1)
    return w, b


@art('posting-publish')
def _():
    w = wash((130, 112), 112, 74, 'sun', .24) + wash((56, 50), 34, 26, 'leaf', .26)
    b = ground(8, 252, 188) + p.job_board(186, 150, 84, 62, pins=('leaf', 'sun')) + p.coins_stack(236, 188, .9, 3)
    f = Person('f', 92, 188, .84, arms=((-14, 10), (30, -60)), expr='smile', look=.7, outfit='jacket', top='leaf', hat=False)
    h = f.hand_at(1)
    b += f.shadow() + f.back() + p.card(h[0] + 4, h[1] - 4, .8, rot=-14, color='sky') + f.front_()
    return w, b


@art('posting-published')
def _():
    w = wash((130, 110), 112, 74, 'leaf', .24)
    b = ground(8, 252, 188) + p.confetti(130, 50, 1.5) + p.job_board(196, 150, 80, 60, pins=('leaf',)) + p.check_badge(226, 70, .8)
    m = Person('m', 90, 188, .84, arms=((-50, -118), (50, -118)), expr='laugh', look=.3, outfit='shirt', top='sun', hat=False)
    b += m.draw() + p.sparkle(46, 58, .8, 'sun')
    return w, b


@art('jobs-checkout-failed')
def _():
    w = wash((130, 112), 112, 74, 'rose', .2) + wash((210, 52), 34, 26, 'sky', .26)
    b = ground(8, 252, 188) + p.card(196, 90, 1.4, rot=10, color='grape', broken=True) + p.x_badge(226, 60, .7)
    f = Person('f', 96, 188, .84, arms=((-30, 60), (30, -60)), expr='oops', look=.6, outfit='shirt', top='sky', hat=False)
    b += f.draw()
    return w, b
