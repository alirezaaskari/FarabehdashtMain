/**
 * آموزش متحرک کنار راهنمای هر بخش (x-motion).
 *
 * زمان‌بندی را سرور حساب می‌کند (App\Support\Help\Motion\MotionScript) و این‌جا
 * فقط پخش می‌شود. هر فریم از روی «زمان» ساخته می‌شود، نه با انیمیشن CSS؛ پس
 * جلو و عقب رفتن، توقف و پرش به صحنه همیشه همان تصویر را می‌دهد.
 *
 * بدون جاوااسکریپت متن همه صحنه‌ها به‌صورت فهرست دیده می‌شود. با «کاهش حرکت»
 * هر صحنه به‌صورت تصویر ثابت پایانش نشان داده می‌شود و دکمه پخش صحنه بعد را می‌آورد.
 *
 * قرارداد نشانه‌گذاری روی اجزای صحنه (همه زمان‌ها به ثانیه):
 *   data-show="a b"        از a پیدا و در b ناپدید می‌شود
 *   data-enter="dx dy s"   هنگام پیدا شدن از این جابه‌جایی و مقیاس می‌آید
 *   data-dim="a b"         در این بازه تا ۳۰٪ کم‌رنگ می‌شود
 *   data-type="a b"        متن data-text در این بازه تایپ می‌شود
 *   data-focus="a b"       حالت فوکوس و نشانگر تایپ
 *   data-when="on@a pressed@a-b"   کلاس در این بازه‌ها
 *   data-count="a"         شمارش تا data-value با ارقام فارسی (با data-latin لاتین)
 *   data-progress="a b"    متغیر --p از ۰ تا ۱
 *   [data-motion-cursor] با data-path='[[t, "هدف" یا [x,y], کلیک؟], …]'
 */

const FADE = 0.4;
const STAGE_WIDTH = 640;

const clamp = (v) => Math.max(0, Math.min(1, v));
const ease = (v) => (v < 0.5 ? 2 * v * v : 1 - Math.pow(-2 * v + 2, 2) / 2);
const ramp = (t, a, b) => ease(clamp((t - a) / (b - a)));
const windowed = (t, a, b) => Math.min(ramp(t, a, a + FADE), 1 - ramp(t, b - FADE, b));
const pair = (value) => (value ?? '').trim().split(/\s+/).map(Number);
const persian = (value) => String(value).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[Number(d)]);
const clock = (s) => persian(`${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`);

export function initMotion(root = document) {
    root.querySelectorAll('[data-motion]').forEach(start);
}

function start(element) {
    const stage = element.querySelector('[data-motion-stage]');
    const viewport = element.querySelector('[data-motion-viewport]');
    const caption = element.querySelector('[data-motion-caption]');
    const play = element.querySelector('[data-motion-play]');
    const seek = element.querySelector('[data-motion-seek]');
    const time = element.querySelector('[data-motion-time]');
    const chapters = [...element.querySelectorAll('[data-motion-chapter]')];
    const scenes = [...element.querySelectorAll('[data-motion-scenes] > li')].map((li) => ({
        start: Number(li.dataset.start),
        end: Number(li.dataset.end),
        text: li.textContent.trim(),
    }));
    const total = Number(element.dataset.duration);
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

    const tracks = compile(stage);
    const cursor = stage.querySelector('[data-motion-cursor]');
    const ripple = stage.querySelector('[data-motion-ripple]');
    const path = cursor ? JSON.parse(cursor.dataset.path) : [];
    const clicks = path.filter((p) => p[2]).map((p) => p[0] + 0.15);

    let t = reduced ? scenes[0].end - 0.05 : Number(element.dataset.poster || 0);
    let playing = false;
    let last = 0;
    let scale = 1;

    element.dataset.motionReady = '';

    const pointOf = (target) => {
        if (Array.isArray(target)) {
            return target;
        }

        const node = stage.querySelector(`[data-motion-target="${target}"]`);

        if (!node) {
            return [320, 220];
        }

        const r = node.getBoundingClientRect();
        const s = stage.getBoundingClientRect();

        // صحنه راست‌به‌چپ است؛ نشانگر از لبه چپ صحنه اندازه می‌گیرد.
        return [(r.left - s.left + r.width / 2) / scale, (r.top - s.top + r.height * 0.55) / scale];
    };

    function render() {
        for (const track of tracks) {
            track(t);
        }

        if (cursor && path.length > 0) {
            renderCursor();
        }
    }

    function renderCursor() {
        const first = path[0][0];
        const lastTime = path[path.length - 1][0];

        if (t < first || t > lastTime + 0.5) {
            cursor.style.opacity = '0';
            ripple.style.opacity = '0';

            return;
        }

        let i = 0;

        while (i < path.length - 1 && t >= path[i + 1][0]) {
            i++;
        }

        // هدف null یعنی نشانگر پنهان است (صحنه‌های بی‌کلیک).
        const next = path[Math.min(i + 1, path.length - 1)];
        const hidden = path[i][1] === null;
        const to = next[1] === null ? pointOf(path[i][1] ?? [320, 220]) : pointOf(next[1]);
        const from = hidden ? to : pointOf(path[i][1]);
        const span = Math.min(next[0] - path[i][0], 1) || 1;
        const p = ramp(t, next[0] - span, next[0]);
        const x = from[0] + (to[0] - from[0]) * p;
        const y = from[1] + (to[1] - from[1]) * p;
        const shown = hidden ? 0 : 1;
        const leaving = next[1] === null ? 1 - ramp(t, next[0] - FADE, next[0]) : 1;

        cursor.style.opacity = String(shown * leaving * ramp(t, first, first + FADE));
        cursor.style.transform = `translate(${x - 4}px, ${y - 2}px)`;

        const click = clicks.find((c) => t >= c && t < c + 0.45);

        if (click === undefined) {
            ripple.style.opacity = '0';

            return;
        }

        const q = (t - click) / 0.45;
        ripple.style.opacity = String(1 - q);
        ripple.style.transform = `translate(${x}px, ${y}px) scale(${0.4 + q})`;
    }

    let current = -1;

    function sync() {
        const index = Math.max(0, scenes.findIndex((s) => t >= s.start && t < s.end));
        const scene = t >= total - 0.05 ? scenes.length - 1 : index;

        if (scene !== current) {
            current = scene;
            caption.textContent = scenes[scene].text;
        }

        chapters.forEach((button) => {
            button.setAttribute('aria-current', String(Number(button.dataset.motionChapter) === scene));
        });

        seek.value = String(Math.round((t / total) * 1000));
        time.textContent = `${clock(t)} / ${clock(total)}`;
        play.dataset.state = playing ? 'playing' : 'paused';
        play.setAttribute('aria-label', playing ? 'توقف' : reduced ? 'صحنه بعد' : 'پخش');
        render();
    }

    function frame(now) {
        if (!playing) {
            return;
        }

        t += (now - last) / 1000;
        last = now;

        if (t >= total) {
            t = total - 0.01;
            playing = false;
        }

        sync();

        if (playing) {
            requestAnimationFrame(frame);
        }
    }

    function resume() {
        if (t >= total - 0.05) {
            t = 0;
        }

        playing = true;
        last = performance.now();
        requestAnimationFrame(frame);
        sync();
    }

    function pause() {
        playing = false;
        sync();
    }

    function goScene(index) {
        const scene = scenes[Math.max(0, Math.min(index, scenes.length - 1))];
        playing = false;
        t = reduced ? scene.end - 0.05 : scene.start;

        if (reduced) {
            sync();
        } else {
            resume();
        }
    }

    play.addEventListener('click', () => {
        if (playing) {
            pause();
        } else if (reduced) {
            goScene(current + 1 >= scenes.length ? 0 : current + 1);
        } else {
            resume();
        }
    });

    element.querySelector('[data-motion-restart]').addEventListener('click', () => goScene(0));
    chapters.forEach((button) => button.addEventListener('click', () => goScene(Number(button.dataset.motionChapter))));
    seek.addEventListener('input', () => {
        t = (Number(seek.value) / 1000) * total;
        sync();
    });

    // وقتی راهنما بسته یا برگه پنهان شد، پخش می‌ایستد.
    element.closest('details')?.addEventListener('toggle', (event) => {
        if (!event.target.open) {
            pause();
        }
    });
    document.addEventListener('visibilitychange', () => document.hidden && pause());

    new ResizeObserver(() => {
        scale = viewport.clientWidth / STAGE_WIDTH;

        if (scale > 0) {
            stage.style.transform = `scale(${scale})`;
            render();
        }
    }).observe(viewport);

    sync();
}

/** هر جزء صحنه یک تابع «زمان ← ظاهر» می‌شود. */
function compile(stage) {
    const tracks = [];

    stage.querySelectorAll('[data-show]').forEach((node) => {
        const [a, b] = pair(node.dataset.show);
        const [dx = 0, dy = 8, s = 1] = pair(node.dataset.enter || '0 8 1');
        const dim = node.dataset.dim ? pair(node.dataset.dim) : null;
        const end = Number.isFinite(b) ? b : Infinity;

        tracks.push((t) => {
            let o = end === Infinity ? ramp(t, a, a + FADE) : windowed(t, a, end);

            if (dim) {
                o *= 1 - 0.7 * ramp(t, dim[0], dim[1]);
            }

            const q = 1 - ramp(t, a, a + FADE + 0.2);
            node.style.opacity = String(o);
            node.style.visibility = o < 0.01 ? 'hidden' : 'visible';
            node.style.transform = `translate(${dx * q}px, ${dy * q}px) scale(${1 - (1 - s) * q})`;
        });
    });

    stage.querySelectorAll('[data-type]').forEach((node) => {
        const [a, b] = pair(node.dataset.type);
        const text = node.dataset.text ?? '';
        const value = node.querySelector('[data-motion-value]') ?? node;

        tracks.push((t) => {
            const count = Math.round(text.length * clamp((t - a) / (b - a)));
            value.textContent = text.slice(0, count);
        });
    });

    stage.querySelectorAll('[data-focus]').forEach((node) => {
        const [a, b] = pair(node.dataset.focus);
        tracks.push((t) => node.classList.toggle('is-focus', t >= a && t < b));
    });

    stage.querySelectorAll('[data-when]').forEach((node) => {
        // یک کلاس می‌تواند چند بازه داشته باشد؛ در هر کدام بود، روشن است.
        const rules = new Map();

        for (const rule of node.dataset.when.trim().split(/\s+/)) {
            const [name, range] = rule.split('@');
            const [a, b] = range.split('-').map(Number);
            rules.set(name, [...(rules.get(name) ?? []), [a, Number.isFinite(b) ? b : Infinity]]);
        }

        tracks.push((t) => {
            rules.forEach((ranges, name) => {
                node.classList.toggle(name, ranges.some(([a, b]) => t >= a && t < b));
            });
        });
    });

    stage.querySelectorAll('[data-count]').forEach((node) => {
        const a = Number(node.dataset.count);
        const target = Number(node.dataset.value);
        const format = 'latin' in node.dataset ? String : persian;
        tracks.push((t) => {
            node.textContent = format(Math.round(target * clamp((t - a) / 0.8)));
        });
    });

    stage.querySelectorAll('[data-progress]').forEach((node) => {
        const [a, b] = pair(node.dataset.progress);
        tracks.push((t) => node.style.setProperty('--p', String(clamp((t - a) / (b - a)))));
    });

    return tracks;
}
