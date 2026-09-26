"""
تصویرهای خطی سایت (x-art) را می‌سازد: شخصیت کارشناس (آقا و خانم) در هر حالت،
و صحنه‌های ساده (کارخانه، کتاب، آزمایشگاه و…).

اجرا:  python3 scripts/art/generate.py
خروجی: resources/views/components/art/{character,scene}/*.blade.php

طراحی در همین فایل است؛ فایل‌های Blade خروجی را دستی ویرایش نکنید. رنگ‌ها
currentColor و کلاس معنایی‌اند (fill-surface، fill-surface-2)، نه هگز.
حالت تازه: یک ردیف به POSES؛ اگر خانم پیش‌فرض آن است، نامش را به $women در
resources/views/components/art/index.blade.php اضافه کنید.
"""
import os, re, sys
sw=3.2
S=f'stroke="var(--ink)" stroke-width="{sw}" stroke-linecap="round" stroke-linejoin="round"'
def L(d,w=sw): return f'<path d="{d}" fill="none" stroke="var(--ink)" stroke-width="{w}" stroke-linecap="round" stroke-linejoin="round"/>'
def P(d,fill='var(--paper)',w=sw): return f'<path d="{d}" fill="{fill}" stroke="var(--ink)" stroke-width="{w}" stroke-linecap="round" stroke-linejoin="round"/>'
def H(x,y): return f'<circle cx="{x}" cy="{y}" r="5.5" fill="var(--paper)" {S}/>'
def R(x,y,w,h,rx=3,fill='var(--paper)'): return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}" fill="{fill}" {S}/>'
def C(x,y,r,fill='var(--paper)',w=sw): return f'<circle cx="{x}" cy="{y}" r="{r}" fill="{fill}" stroke="var(--ink)" stroke-width="{w}"/>'
def dot(x,y,r=2.5): return f'<circle cx="{x}" cy="{y}" r="{r}" fill="var(--ink)"/>'

def body(g):
    hem=204 if g=='f' else 188
    lx,rx=(70,130) if g=='f' else (72,128)
    out=f'<ellipse cx="100" cy="245" rx="46" ry="5" fill="var(--shadow)"/>'
    out+=L(f"M88 {hem} L86 236")+L(f"M112 {hem} L114 236")
    out+='<ellipse cx="81" cy="239" rx="11" ry="5" fill="var(--ink)"/><ellipse cx="119" cy="239" rx="11" ry="5" fill="var(--ink)"/>'
    if g=='m': out+=L("M100 96 L100 108")
    out+=P(f"M{lx} {hem} L76 126 Q78 108 100 108 Q122 108 124 126 L{rx} {hem} Z")
    out+=L(f"M89 111 L87 {hem}",2.4)+L(f"M111 111 L113 {hem}",2.4)
    out+='<path d="M75 146 L125 146 L125 154 L75 154 Z" fill="var(--ink)"/>'
    return out
def head(g):
    if g=='f':
        return ('<path d="M122 66 Q142 78 138 104 Q136 116 128 120 Q132 104 124 90 Z" fill="var(--ink)"/>'
                +L("M100 96 L100 108")+C(100,74,24)
                +'<path d="M76 60 Q70 80 76 96 Q82 97 84 92 Q79 78 83 62 Z" fill="var(--ink)"/>'
                +'<path d="M124 60 Q130 80 124 96 Q118 97 116 92 Q121 78 117 62 Z" fill="var(--ink)"/>'
                +'<path d="M84 62 Q94 67 104 63 Q111 66 116 62 Z" fill="var(--ink)"/>')
    return (C(100,74,24)+'<path d="M77 61 L77 73 Q80 66 85 61 Z" fill="var(--ink)"/><path d="M123 61 L123 73 Q120 66 115 61 Z" fill="var(--ink)"/>')
HAT=('<path d="M71 62 C71 36 86 26 100 26 C114 26 129 36 129 62 Z" fill="var(--ink)"/>'+L("M62 62 L138 62",5)
     +'<path d="M100 31 L100 52" stroke="var(--paper)" stroke-width="3" stroke-linecap="round"/>')
def face(g,look=(0,0),mouth='smile'):
    dx,dy=look; y=78+dy
    f=dot(92+dx,y)+dot(108+dx,y)
    if g=='f': f+=L(f"M{88+dx} {y-1} L{85.5+dx} {y-3}",1.6)+L(f"M{112+dx} {y-1} L{114.5+dx} {y-3}",1.6)
    m=87
    f+={'smile':L(f"M93 {m} Q100 {m+5} 107 {m}",2.6),'flat':L(f"M95 {m+2} L105 {m+2}",2.6),
        'o':C(100,m+2,2.6,'var(--ink)',1.5),
        'open':f'<path d="M92 {m-1} Q100 {m+10} 108 {m-1} Z" fill="var(--ink)" stroke="var(--ink)" stroke-width="2" stroke-linejoin="round"/>'}[mouth]
    return f
DOWN_L=L("M78 120 Q64 148 70 170")+H(70,172)
DOWN_R=L("M122 120 Q136 148 130 170")+H(130,172)
POSES={
'measure':(('3,-1','smile'), DOWN_L+L("M122 120 Q146 112 154 88"), R(147,42,17,38,4)+R(151,48,9,9,1.5,'var(--ink)')+L("M152 64 L159 64",2.2)+L("M152 70 L159 70",2.2)+dot(155.5,35,6)+L("M167 26 Q173 35 167 44",2.4)+L("M175 19 Q185 35 175 51",2.4)+H(155,85)),
'read':(('0,3','smile'), L("M78 120 Q68 150 82 162")+L("M122 120 Q132 150 118 162"), P("M72 146 L100 153 L128 146 L128 172 L100 179 L72 172 Z")+L("M100 153 L100 179")+L("M79 155 L93 158",2)+L("M79 162 L93 165",2)+L("M107 158 L121 155",2)+L("M107 165 L121 162",2)+H(78,166)+H(122,166)),
'report':(('-3,2','smile'), L("M78 120 Q62 140 72 158")+L("M122 120 Q122 150 100 156"), R(54,132,36,46,4)+R(64,127,16,8,2,'var(--ink)')+L("M61 146 L65 150 L71 142",2.4)+L("M75 147 L84 147",2.2)+L("M61 159 L65 163 L71 155",2.4)+L("M75 160 L84 160",2.2)+L("M62 171 L84 171",2.2)+H(72,160)+L("M99 156 L86 143",4.2)+H(100,157)),
'think':(('4,-3','flat'), L("M78 120 Q80 148 116 138")+L("M122 120 Q142 128 118 101"), H(116,99)+H(118,137)+L("M152 34 Q152 20 165 20 Q178 20 178 32 Q178 41 165 45 L165 53",3.6)+dot(165,62,3)),
'shrug':(('0,0','flat'), L("M78 120 Q54 132 52 110")+H(51,106)+L("M122 120 Q146 132 148 110")+H(149,106), ''),
'ok':(('0,-1','open'), L("M78 120 Q60 142 76 158")+H(78,158)+L("M122 120 Q148 118 150 94"), R(144,84,13,14,4)+L("M148 84 L148 72",4.2)+L("M160 138 L171 150 L192 122",4.4)),
'chemical':(('4,-2','o'), DOWN_L+L("M122 120 Q144 118 148 98"), P("M142 54 L142 70 L131 91 Q128 98 136 98 L160 98 Q168 98 165 91 L154 70 L154 54 Z")+L("M139 54 L157 54")+'<path d="M135 86 L161 86 L165 92 Q166 97 160 97 L136 97 Q130 97 131 92 Z" fill="var(--ink)"/>'+C(146,77,2.2,'var(--paper)',1.8)+C(152,68,1.6,'var(--paper)',1.6)+C(148,44,2.4,'var(--paper)',1.8)+C(154,36,1.8,'var(--paper)',1.6)+H(148,98)),
'teach':(('4,0','smile'), DOWN_L+L("M122 120 Q140 106 146 90"), R(142,34,52,44,3)+L("M149 46 L180 46",2.2)+L("M149 54 L172 54",2.2)+L("M149 70 L158 63 L166 67 L186 52",2.4)+L("M152 78 L148 96",2.4)+L("M184 78 L188 96",2.4)+H(147,88)),
'shop':(('0,2','smile'), L("M78 120 Q62 140 72 152")+L("M122 120 Q138 140 128 152"), R(72,134,56,42,3)+L("M100 134 L100 176",2.4)+L("M72 146 L128 146",2.4)+R(106,156,16,10,2)+H(72,154)+H(128,154)),
'ask':(('-3,-2','smile'), DOWN_L+L("M122 120 Q138 102 140 80"), H(140,76)+L("M136 70 L136 62",2.6)+L("M141 68 L142 59",2.6)+L("M146 71 L149 64",2.6)+P("M22 22 Q22 14 30 14 L64 14 Q72 14 72 22 L72 44 Q72 52 64 52 L56 52 L50 62 L48 52 L30 52 Q22 52 22 44 Z")+L("M40 26 Q40 21 47 21 Q54 21 54 27 Q54 32 47 34 L47 38",3)+dot(47,45,2.4)),
'laptop':(('0,4','smile'), L("M78 120 Q64 146 72 160")+L("M122 120 Q136 146 128 160"), R(70,118,60,40,3)+C(100,138,4,'var(--paper)',2.2)+'<path d="M62 158 L138 158 L132 166 L68 166 Z" fill="var(--ink)" stroke="var(--ink)" stroke-width="2" stroke-linejoin="round"/>'+H(70,163)+H(130,163)),
'wave':(('0,0','open'), DOWN_L+L("M122 120 Q146 108 150 82"), H(151,79)+L("M162 66 Q168 74 163 84",2.6)+L("M140 64 Q134 72 138 80",2.6)),
'key':(('3,-2','smile'), DOWN_L+L("M122 120 Q150 120 160 100"), C(162,70,10)+C(162,70,3.2,'var(--paper)',2.2)+L("M162 80 L162 110",3.6)+L("M162 103 L169 103",3.2)+L("M162 109 L168 109",3.2)+H(161,99)+L("M184 52 L184 64",2.2)+L("M178 58 L190 58",2.2)+L("M144 50 L144 58",2)+L("M140 54 L148 54",2)),
'calendar':(('0,3','smile'), L("M78 120 Q64 140 72 152")+L("M122 120 Q134 140 124 152"), R(70,128,56,48,3)+'<rect x="70" y="128" width="56" height="12" rx="3" fill="var(--ink)"/>'+L("M82 124 L82 132",3)+L("M114 124 L114 132",3)+''.join(dot(x,y,2) for x in (82,94,106,118) for y in (150,160,170) if not (x==106 and y==160))+C(106,160,5,'none',2.2)+H(72,154)+H(124,154)),
'search':(('5,-1','o'), DOWN_L+L("M122 120 Q142 118 146 104"), L("M152 92 L146 106",6)+C(158,76,15)+L("M151 70 Q154 66 159 65",2.2)+H(146,103)),
'verify':(('3,1','smile'), DOWN_L+L("M122 120 Q140 128 138 146"), R(122,110,44,58,3)+L("M130 122 L158 122",2.2)+L("M130 130 L154 130",2.2)+L("M130 138 L158 138",2.2)+C(152,154,9,'var(--ink)',2)+'<path d="M147 154 L151 158 L157 150" fill="none" stroke="var(--paper)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>'+H(128,148)),
}
# ---- new poses
POSES.update({
'writer':(('4,-1','smile'), DOWN_L+L("M122 120 Q146 118 156 98"), P("M168 50 L182 58 L144 132 L130 124 Z")+P("M130 124 L144 132 L134 144 Z")+'<path d="M134 144 L136 138 L139 140 Z" fill="var(--ink)"/>'+L("M166 54 L180 62",2.4)+H(156,96)),
'bell':(('3,-3','o'), DOWN_L+L("M122 120 Q146 124 152 110"), P("M136 90 Q136 64 152 60 Q168 64 168 90 L173 96 L131 96 Z")+dot(152,101,4.5)+C(152,56,3.5)+L("M125 68 Q120 78 125 88",2.6)+L("M179 68 Q184 78 179 88",2.6)+H(152,111)),
'wallet':(('0,3','smile'), L("M78 120 Q62 140 72 150")+L("M122 120 Q138 140 128 150"), R(70,130,60,38,5)+P("M104 140 L130 140 L130 158 L104 158 Z")+dot(116,149,3)+C(152,112,8)+L("M149 112 L155 112",2)+C(166,94,7)+L("M163 94 L169 94",2)+H(72,151)+H(128,151)),
'phone':(('5,-1','smile'), DOWN_L+L("M122 120 Q142 124 144 108"), R(133,70,24,40,4)+'<rect x="137" y="76" width="16" height="25" rx="1.5" fill="var(--ink)"/>'+'<path d="M141 89 L144 92 L150 84" fill="none" stroke="var(--paper)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>'+H(143,108)),
'idcard':(('3,1','smile'), DOWN_L+L("M122 120 Q134 138 126 148"), L("M100 108 L126 124",2)+R(116,118,50,34,4)+dot(131,133,6)+L("M143 128 L158 128",2.2)+L("M143 136 L155 136",2.2)+L("M123 145 L158 145",2.2)+H(124,148)),
'upload':(('4,0','smile'), DOWN_L+L("M122 120 Q134 134 140 130"), P("M136 92 L162 92 L172 102 L172 140 L136 140 Z")+L("M162 92 L162 102 L172 102",2.4)+L("M154 132 L154 110",3)+L("M146 118 L154 110 L162 118",3)+H(140,130)),
})
def svg(g,p):
    (look,mouth),arms,front=POSES[p]
    lx,ly=map(float,look.split(','))
    return f'{body(g)}{head(g)}{face(g,(lx,ly),mouth)}{HAT}{arms}{front}'
def char_group(g,p,tx,ty,s): return f'<g transform="translate({tx} {ty}) scale({s})">{svg(g,p)}</g>'
# ---- scenes
def win(x,y,w=12,h=14): return R(x,y,w,h,1.5)
G=lambda y,x1,x2: L(f"M{x1} {y} L{x2} {y}",2.6)
SCENES={}
FACTORY=(P("M14 186 L14 118 L48 98 L48 118 L82 98 L82 118 L116 98 L116 186 Z")
  +''.join(win(x,134) for x in (26,48,70,92))+R(56,158,20,28,1.5)
  +P("M124 186 L126 58 L142 58 L144 186 Z")+'<path d="M125 72 L143 72 L143 80 L125 80 Z" fill="var(--ink)"/>'
  +C(136,44,7,'var(--paper)',2.4)+C(150,32,9,'var(--paper)',2.4)+C(168,24,10,'var(--paper)',2.4)
  +P("M152 186 L152 136 Q152 128 172 128 Q192 128 192 136 L192 186 Z")+L("M152 150 L192 150",2.2)+L("M152 164 L192 164",2.2))
def cone(x): return P(f"M{x} 186 L{x+8} 150 L{x+16} 186 Z",'var(--paper)',2.4)+f'<path d="M{x+3.6} 170 L{x+12.4} 170 L{x+11.2} 164 L{x+4.8} 164 Z" fill="var(--ink)"/>'+L(f"M{x-4} 186 L{x+20} 186",3)
SCENES['site']=('0 0 420 200', G(186,6,414)+FACTORY+char_group('m','measure',196,26,.62)+char_group('f','report',292,26,.62))
SCENES['yard']=('0 0 1200 200', G(186,6,1194)+FACTORY
  +P("M232 186 L232 150 L300 150 L300 186 Z")+L("M232 162 L300 162",2)+L("M244 150 L244 186",2)+L("M288 150 L288 186",2)
  +P("M330 186 L330 124 L380 124 L380 186 Z")+''.join(win(x,y,10,10) for x in (338,358) for y in (134,154))
  +cone(430)+cone(470)+L("M430 168 L486 168",2)
  +L("M560 186 L560 140",2.6)+C(560,132,12)+L("M600 186 L600 148",2.6)+C(600,138,16)
  +R(680,150,56,36,2)+L("M680 162 L736 162",2)+R(690,138,36,12,2)
  +char_group('f','measure',880,26,.62)+char_group('m','report',990,26,.62)+char_group('f','chemical',1080,26,.62))
SCENES['books']=('0 0 260 170', G(160,10,250)
  +R(40,138,120,22,3)+L("M52 138 L52 160",2.2)+R(50,116,104,22,3)+'<rect x="50" y="116" width="14" height="22" rx="3" fill="var(--ink)"/>'+R(46,94,112,22,3)+L("M140 94 L140 116",2.2)+L("M144 94 L144 116",2.2)
  +P("M166 160 L172 60 L190 60 L186 160 Z")+L("M170 80 L188 80",2.2)+L("M169 96 L187 96",2.2)+P("M192 160 L200 70 L216 72 L210 160 Z")+'<path d="M198 88 L214 90 L213 100 L197 98 Z" fill="var(--ink)"/>'
  +P("M52 94 L80 86 L106 94 L106 70 L80 62 L52 70 Z")+L("M80 62 L80 86")+L("M58 74 L74 70",2)+L("M86 70 L100 74",2)
  +P("M222 160 L226 138 L246 138 L250 160 Z")+L("M236 138 Q230 118 220 112",2.4)+L("M236 138 Q240 116 252 108",2.4)+L("M236 138 L236 110",2.4))
SCENES['lab']=('0 0 260 170', G(160,10,250)
  +P("M40 60 L40 92 L18 150 Q15 160 26 160 L90 160 Q101 160 98 150 L76 92 L76 60 Z")+L("M36 60 L80 60")
  +'<path d="M29 128 L87 128 L96 150 Q99 158 90 158 L26 158 Q17 158 20 150 Z" fill="var(--ink)"/>'+C(52,112,3,'var(--paper)',2)+C(62,100,2.2,'var(--paper)',2)+C(58,46,3,'var(--paper)',2)+C(66,34,2.4,'var(--paper)',2)
  +P("M112 96 L112 158 Q112 160 114 160 L156 160 Q158 160 158 158 L158 96 Z")+L("M108 96 L116 96",2.6)+'<path d="M114 128 L156 128 L156 158 L114 158 Z" fill="var(--ink)"/>'+L("M142 104 L150 104",2)+L("M144 112 L150 112",2)+L("M142 120 L150 120",2)
  +R(172,120,78,12,2)+L("M178 132 L178 160",2.6)+L("M244 132 L244 160",2.6)
  +''.join(P(f"M{x} 96 L{x} 142 Q{x} 150 {x+6} 150 Q{x+12} 150 {x+12} 142 L{x+12} 96 Z")+(f'<path d="M{x} {h} L{x+12} {h} L{x+12} 142 Q{x+12} 148 {x+6} 148 Q{x} 148 {x} 142 Z" fill="var(--ink)"/>') for x,h in ((182,126),(204,116),(226,132))))
SCENES['instruments']=('0 0 260 170', G(160,10,250)
  +R(30,54,40,100,8)+R(38,66,24,22,2)+'<rect x="41" y="69" width="18" height="16" rx="1" fill="var(--ink)"/>'+L("M40 100 L60 100",2.4)+L("M40 110 L60 110",2.4)+C(50,130,6)+dot(50,38,11)+L("M50 49 L50 54",4)+L("M74 26 Q84 38 74 50",2.4)+L("M84 18 Q98 38 84 58",2.4)
  +P("M96 132 Q96 96 132 96 Q168 96 168 132 Z")+L("M88 132 L176 132",5)+L("M132 98 L132 118",3)
  +L("M190 124 Q190 86 216 86 Q242 86 242 124",4)+R(180,110,20,34,8)+R(232,110,20,34,8))
SCENES['report']=('0 0 260 170', G(160,10,250)
  +P("M70 20 L170 20 L190 40 L190 158 L70 158 Z")+L("M170 20 L170 40 L190 40",2.4)
  +L("M86 42 L140 42",3)+L("M86 56 L126 56",2.2)+L("M86 66 L132 66",2.2)
  +L("M88 128 L88 84",2.2)+L("M88 128 L170 128",2.2)+'<path d="M96 128 L96 112 L108 112 L108 128 Z M116 128 L116 100 L128 100 L128 128 Z M136 128 L136 92 L148 92 L148 128 Z" fill="var(--ink)"/>'
  +L("M98 104 L122 92 L142 84 L162 78",2.4)+C(168,142,11,'var(--ink)',2)+'<path d="M162 142 L166 146 L174 138" fill="none" stroke="var(--paper)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>'
  +P("M198 150 L236 64 L246 68 L208 154 Z")+'<path d="M198 150 L208 154 L200 162 Z" fill="var(--ink)"/>')
SCENES['shop']=('0 0 260 170', G(160,10,250)
  +R(20,30,150,130,2)+L("M20 76 L170 76",2.6)+L("M20 118 L170 118",2.6)
  +''.join(R(x,y,14,34,1.5) for x,y in ((30,42),(46,42),(62,48)))+'<rect x="80" y="44" width="14" height="32" rx="1.5" fill="var(--ink)"/>'+R(104,52,40,24,2)+L("M124 52 L124 76",2)
  +R(30,90,34,28,2)+L("M47 90 L47 100",2)+R(74,84,26,34,2)+L("M80 94 L94 94",2)+L("M80 102 L90 102",2)+'<rect x="110" y="96" width="40" height="22" rx="2" fill="var(--ink)"/>'
  +P("M188 96 L238 96 L232 148 L194 148 Z")+L("M200 96 Q200 76 213 76 Q226 76 226 96",2.6)+dot(204,108,2.4)+dot(222,108,2.4))
SCENES['class']=('0 0 260 170', G(160,10,250)
  +R(40,20,150,90,3)+L("M58 42 L120 42",3)+L("M58 56 L106 56",2.2)+L("M58 66 L112 66",2.2)+L("M130 88 L146 70 L160 78 L176 50",2.6)+L("M130 92 L178 92",2)+L("M130 92 L130 46",2)
  +L("M70 110 L60 160",2.6)+L("M160 110 L170 160",2.6)+L("M196 110 L240 60",3)+dot(240,60,3.5)
  +R(196,134,40,26,2)+L("M196 146 L236 146",2))
SCENES['chat']=('0 0 260 170', G(160,10,250)
  +P("M30 30 Q30 18 42 18 L140 18 Q152 18 152 30 L152 78 Q152 90 140 90 L78 90 L58 108 L60 90 L42 90 Q30 90 30 78 Z")
  +L("M78 40 Q78 32 90 32 Q102 32 102 42 Q102 50 90 53 L90 60",3.4)+dot(90,72,3)
  +P("M108 84 Q108 74 118 74 L218 74 Q230 74 230 84 L230 126 Q230 136 218 136 L204 136 L208 152 L186 136 L118 136 Q108 136 108 126 Z")
  +L("M124 94 L210 94",2.4)+L("M124 106 L198 106",2.4)+L("M124 118 L180 118",2.4)+C(214,118,8,'var(--ink)',2)+'<path d="M209 118 L213 122 L220 114" fill="none" stroke="var(--paper)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>')
def blade(inner, vb):
    t=inner.replace('var(--ink)','currentColor')
    t=t.replace('fill="var(--paper)"','class="fill-surface"').replace('stroke="var(--paper)"','class="stroke-surface"').replace('fill="var(--shadow)"','class="fill-surface-2"')
    assert 'var(--' not in t, t[:200]
    assert not re.search(r'#[0-9a-fA-F]{3,6}\b', t)
    return ('<svg viewBox="'+vb+'" xmlns="http://www.w3.org/2000/svg" {{ $attributes->class([\'text-ink\'])->merge([\'aria-hidden\' => \'true\', \'focusable\' => \'false\']) }}>'
            +t+'</svg>\n')
out=sys.argv[1] if len(sys.argv)>1 else os.path.join(os.path.dirname(os.path.abspath(__file__)),'../../resources/views/components/art')
os.makedirs(out+'/character',exist_ok=True); os.makedirs(out+'/scene',exist_ok=True)
for p in POSES:
    for g in 'mf':
        open(f'{out}/character/{p}-{g}.blade.php','w').write(blade(svg(g,p),'0 0 200 260'))
for k,(vb,inner) in SCENES.items():
    open(f'{out}/scene/{k}.blade.php','w').write(blade(inner,vb))
print(len(POSES), 'poses', len(SCENES),'scenes')
