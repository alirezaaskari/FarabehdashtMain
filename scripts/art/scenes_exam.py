"""صحنه‌های بخش آمادگی آزمون: بسته‌ها، تمرین، آزمون زمان‌دار، کارنامه و نوشتن سؤال."""
from engine import Person, wash, ground, rect, line, circle, path, poly, INK
from registry import art
import props as p


def answer_sheet(x, y, s=1, marks=(1, 3, 0, 2), rot=0):
    """پاسخ‌نامه چهارگزینه‌ای؛ لنگر وسط."""
    o = rect(-18, -24, 36, 48, 'paper', 2)
    for r, m in enumerate(marks):
        for c in range(4):
            cx, cy = -10 + c * 7, -14 + r * 10
            o += circle((cx, cy), 2.4, 'sky' if c == m else None, INK, 1)
    return p.at(x, y, s, o, rot)


def option_card(x, y, w, color='paper', mark=None):
    o = rect(x, y, w, 14, color, 3, sw=1.6) + circle((x + 8, y + 7), 3, 'paper', INK, 1.2)
    o += line(f'M{x + 16} {y + 7} L{x + w - 8} {y + 7}', 1.3, op=.7)
    if mark == 'ok':
        o += line(f'M{x + 6} {y + 7} L{x + 8} {y + 9} L{x + 11} {y + 5}', 1.4)
    return o


@art('exam-index')
def _():
    w = wash((130, 112), 112, 74, 'grape', .26) + wash((60, 50), 40, 30, 'sun', .28)
    b = ground(8, 252, 188) + p.book_stack(60, 188, .9, ('grape', 'sun', 'sky'))
    b += answer_sheet(60, 110, 1.1, rot=-6)
    f = Person('f', 170, 188, .82, arms=((-14, -6), (150, 170)), expr='determined', look=-.4, outfit='jacket', top='leaf', hat=False)
    b += f.shadow() + f.back() + f.front_() + p.star(222, 40, .8)
    return w, b


@art('exam-show')
def _():
    w = wash((130, 112), 112, 74, 'sky', .28)
    b = ground(8, 252, 188) + p.whiteboard(22, 30, 110, 76, 'text')
    b += option_card(34, 118, 84, 'sun', 'ok')
    m = Person('m', 190, 188, .82, arms=((-60, -110), (12, 5)), expr='curious', look=-.5, outfit='shirt', top='grape', hat=False, front=0)
    return w, b + m.draw()


@art('exam-attempt-question')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .26)
    b = ground(8, 252, 188)
    b += option_card(24, 40, 100) + option_card(24, 62, 100, 'leaf', 'ok') + option_card(24, 84, 100) + option_card(24, 106, 100)
    f = Person('f', 180, 188, .82, arms=((-60, -100), (20, 150)), expr='think', look=-.6, outfit='shirt', top='sand', hat=False, tilt=-6, front=0)
    return w, b + f.draw()


@art('exam-attempt-exam')
def _():
    w = wash((130, 112), 112, 74, 'rose', .24) + wash((210, 50), 36, 28, 'sun', .3)
    b = ground(8, 252, 188) + p.stopwatch(210, 48, 1.4)
    m = Person('m', 110, 150, .8, legs=((90, 0), (86, 4)), arms=((40, 110), (50, 116)), expr='focus', look=.4, outfit='jacket', top='sky', hat=False, anchor='pelvis', lean=6)
    b += p.chair(104, 188, .9, 1, 'sand') + m.back() + p.desk(180, 138, 100, 50) + answer_sheet(176, 132, .7, rot=-80)
    b += p.pencil(m.hand_at(1)[0] + 3, m.hand_at(1)[1] + 4, .6, 30, 40) + m.front_()
    return w, b


@art('exam-attempt-feedback')
def _():
    w = wash((130, 112), 112, 74, 'leaf', .28)
    b = ground(8, 252, 188) + p.speech(26, 26, 86, 50, 'leaf', 1, 'check') + p.book(70, 150, 1.2, 'sky', open_=True)
    f = Person('f', 178, 188, .82, arms=((-40, -150), (14, 6)), expr='happy', look=-.4, outfit='jacket', top='rose', hat=False)
    return w, b + f.draw() + p.sparkle(128, 20, .7)


@art('exam-attempt-result')
def _():
    w = wash((130, 112), 112, 74, 'sun', .3)
    b = ground(8, 252, 188) + p.chart_board(70, 188, 1.25, (12, 20, 30, 42), ('rose', 'sun', 'leaf', 'leaf'))
    m = Person('m', 188, 188, .82, arms=((-140, -170), (150, 170)), legs=((-10, -4), (10, 4)), expr='laugh', look=-.2)
    return w, b + m.draw() + p.confetti(120, 12, 120, 40, 5)


@art('exam-mine')
def _():
    w = wash((130, 112), 112, 74, 'grape', .26)
    b = ground(8, 252, 188) + p.shelf(70, 150, 100, 2, 44, colors=('grape', 'sky', 'sun', 'leaf', 'rose'))
    f = Person('f', 180, 188, .82, legs=((-18, -4), (18, 4)), arms=((-26, 44), (26, -44)), expr='proud', look=-.3, outfit='coat', hat=False)
    h1, h2 = f.hand_at(0), f.hand_at(1)
    cx, cy = (h1[0] + h2[0]) / 2, (h1[1] + h2[1]) / 2
    b += f.shadow() + f.back() + answer_sheet(cx, cy + 4, .8) + f.front_()
    return w, b


@art('exam-writer-create')
def _():
    w = wash((130, 112), 112, 74, 'sky', .26)
    b = ground(8, 252, 188)
    m = Person('m', 96, 150, .8, legs=((90, 0), (86, 4)), arms=((40, 108), (-10, 20)), expr='think', look=.5, outfit='shirt', top='leaf', hat=False, anchor='pelvis', front=0)
    b += p.chair(90, 188, .9, 1, 'rose') + m.back() + p.desk(172, 138, 110, 50) + p.paper(160, 128, .9, -84, 3)
    b += p.pencil(m.hand_at(0)[0] + 4, m.hand_at(0)[1] + 4, .6, 30, 40) + m.front_()
    b += p.speech(176, 40, 54, 32, 'sun', -1, 'q') + p.lamp(226, 138, .8)
    return w, b


@art('exam-writer-index')
def _():
    w = wash((130, 112), 112, 74, 'sand', .34)
    b = ground(8, 252, 188) + p.whiteboard(18, 22, 120, 84, None)
    for i, c in enumerate(('sun', 'sky', 'rose', 'leaf')):
        x, y = 28 + (i % 2) * 54, 32 + (i // 2) * 36
        b += rect(x, y, 44, 28, c, 2, sw=1.4) + line(f'M{x + 6} {y + 9} L{x + 34} {y + 9} M{x + 6} {y + 17} L{x + 26} {y + 17}', 1.2, op=.7)
    f = Person('f', 190, 188, .82, arms=((-70, -120), (12, 5)), expr='smile', look=-.6, outfit='vest', front=0)
    return w, b + f.draw()


VB = '0 0 200 150'


@art('empty-exam-index', VB)
def _():
    w = wash((100, 90), 70, 44, 'grape', .28)
    b = ground(10, 190, 140) + p.box(80, 140, 1.2, open_=True, color='sand')
    return w, b + f'<text x="118" y="70" font-size="18" font-weight="700" fill="currentColor" opacity=".7">?</text>'


@art('empty-exam-mine', VB)
def _():
    w = wash((100, 90), 70, 44, 'sky', .28)
    b = ground(10, 190, 140) + answer_sheet(80, 96, 1.3, marks=(-1, -1, -1, -1))
    return w, b


@art('empty-exam-writer-create', VB)
def _():
    w = wash((100, 90), 70, 44, 'sun', .3)
    b = ground(10, 190, 140) + p.folder(76, 140, 1.4, 'sun', empty=True) + p.sparkle(130, 60, .7, 'sky')
    return w, b


@art('empty-exam-writer-index', VB)
def _():
    w = wash((100, 90), 70, 44, 'leaf', .28)
    b = ground(10, 190, 140) + p.whiteboard(40, 30, 90, 60, None, legs=False)
    m = Person('m', 160, 140, .55, arms=((-60, -130), (10, 4)), expr='curious', look=-.5, outfit='vest', front=0)
    return w, b + m.draw()
