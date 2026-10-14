/**
 * حرکت نرم صفحه (همراه resources/css/soft-motion.css).
 *
 * ۱. بخش‌ها و کارت‌هایی که زیر لبه صفحه‌اند هنگام رسیدن پیمایش آرام پیدا
 *    می‌شوند و خانه‌های یک شبکه پشت سر هم می‌آیند. آنچه در صفحه اول دیده
 *    می‌شود هرگز پنهان نمی‌شود، پس صفحه بی‌جاوااسکریپت و پیش از اجرای این
 *    فایل همان صفحه کامل است.
 * ۲. نتیجه‌ای که زنده عوض می‌شود (ناحیه aria-live، مثل مبدل صفحه اصلی و
 *    دستیار انتخاب روش) آرام جا می‌افتد؛ هنگام تایپ نه، تا عدد نلرزد.
 *
 * با «کاهش حرکت» هیچ‌کدام اجرا نمی‌شود.
 */

const BLOCKS = 'section, article, figure, .rounded-xl';
// خانه‌های شبکه کارت و فهرست، نه ستون‌های چیدمان یا خانه‌های یک ردیف.
const ITEMS = ':is(ul, ol).grid > li, .grid > :is(a, article, section, .rounded-xl)';
const SKIP = 'details, dialog, form, [data-motion]';
const STAGGER = 0.04;
const MAX_STEPS = 8;
// شبکه بزرگ (مثل فهرست ابزارها) یک‌جا با بخشش می‌آید؛ پشت‌سرهم آمدن ده‌ها خانه کند است.
const MAX_ITEMS = 12;

export function initSoftMotion() {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    revealOnScroll();
    settleLiveRegions();
}

function revealOnScroll() {
    const main = document.getElementById('main');

    if (!main || !('IntersectionObserver' in window)) {
        return;
    }

    // اول همه اندازه‌ها خوانده می‌شود، بعد نشانه‌ها نوشته؛ یک بار چیدمان.
    const fold = window.innerHeight;
    const chosen = new Set();

    for (const element of main.querySelectorAll(`${BLOCKS}, ${ITEMS}`)) {
        const item = element.matches(ITEMS);

        if (element.closest(SKIP) || (!item && insideAny(element, chosen, main))) {
            continue;
        }

        // شبکه بزرگ، و شبکه‌ای که خط جداکننده‌اش رنگ پس‌زمینه شبکه است (هنگام
        // محو شدن خانه‌ها آن رنگ دیده می‌شود)، یک‌جا با بخشش می‌آید.
        if (
            item &&
            (element.parentElement.childElementCount > MAX_ITEMS ||
                getComputedStyle(element.parentElement).backgroundColor !== 'rgba(0, 0, 0, 0)')
        ) {
            continue;
        }

        const { top, height } = element.getBoundingClientRect();

        if (height > 0 && top >= fold) {
            chosen.add(element);
        }
    }

    if (chosen.size === 0) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            const steps = new Map();

            for (const { target, isIntersecting } of entries) {
                if (!isIntersecting) {
                    continue;
                }

                const step = steps.get(target.parentElement) ?? 0;
                steps.set(target.parentElement, step + 1);
                target.style.setProperty('--reveal-delay', `${Math.min(step, MAX_STEPS) * STAGGER}s`);
                show(target);
            }
        },
        { rootMargin: '0px 0px -8% 0px' },
    );

    const show = (element) => {
        observer.unobserve(element);
        element.dataset.reveal = 'in';

        const done = (event) => {
            if (event.target !== element || event.propertyName !== 'transform') {
                return;
            }

            element.removeEventListener('transitionend', done);
            delete element.dataset.reveal;
            element.style.removeProperty('--reveal-delay');
        };

        element.addEventListener('transitionend', done);
    };

    for (const element of chosen) {
        element.dataset.reveal = '';
        observer.observe(element);
    }

    // فوکوس صفحه‌کلید هرگز روی چیز نادیده نمی‌نشیند.
    document.addEventListener('focusin', (event) => {
        let hidden = event.target.closest?.('[data-reveal=""]');

        while (hidden) {
            show(hidden);
            hidden = hidden.parentElement.closest('[data-reveal=""]');
        }
    });
}

function insideAny(element, set, root) {
    for (let node = element.parentElement; node && node !== root; node = node.parentElement) {
        if (set.has(node)) {
            return true;
        }
    }

    return false;
}

function settleLiveRegions() {
    const regions = document.querySelectorAll('[aria-live]:not(.sr-only, [data-motion-caption])');

    if (regions.length === 0) {
        return;
    }

    const observer = new MutationObserver((records) => {
        const typing = document.activeElement?.matches('textarea, input:not([type="radio"], [type="checkbox"])');

        if (typing) {
            return;
        }

        const changed = new Set(records.map(({ target }) => (target.nodeType === Node.ELEMENT_NODE ? target : target.parentElement)));

        for (const element of changed) {
            element?.animate(
                [
                    { opacity: 0.4, transform: 'translateY(4px)' },
                    { opacity: 1, transform: 'none' },
                ],
                { duration: 250, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' },
            );
        }
    });

    for (const region of regions) {
        observer.observe(region, { childList: true, subtree: true, characterData: true });
    }
}
