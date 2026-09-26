/**
 * دکمه توقف و پخش تصویرهای متحرک (WCAG 2.2.2).
 *
 * هر حرکتی که بیش از پنج ثانیه ادامه دارد باید قابل توقف باشد. انیمیشن‌ها
 * فقط CSS‌اند؛ این‌جا فقط `data-paused` روی صحنه جابه‌جا می‌شود. با
 * «کاهش حرکت» در سیستم، CSS خودش حرکت را خاموش می‌کند و دکمه پنهان می‌ماند.
 */

function attach(scene) {
    const button = scene.querySelector('[data-motion-toggle]');

    if (!button || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const label = button.querySelector('[data-motion-label]');

    const render = () => {
        // برچسب خود کار بعدی را می‌گوید؛ aria-pressed کنارش فقط دوباره‌گویی بود.
        if (label) {
            label.textContent = scene.hasAttribute('data-paused') ? 'پخش انیمیشن' : 'توقف انیمیشن';
        }
    };

    button.hidden = false;
    button.addEventListener('click', () => {
        scene.toggleAttribute('data-paused');
        render();
    });

    render();
}

export function initMotion() {
    document.querySelectorAll('[data-motion]').forEach(attach);
}
