/**
 * مقاله دانشنامه: نوار پیشرفت خواندن و برجسته‌کردن بخش جاری در «در این مقاله».
 *
 * فقط نمایش است؛ بدون این فایل مقاله و فهرستش همان‌طور کار می‌کنند.
 */

export function initReading() {
    const article = document.querySelector('[data-article]');

    if (!article) {
        return;
    }

    const bar = document.querySelector('[data-reading-progress]');
    const links = [...document.querySelectorAll('a[data-section-link]')];
    const headings = [...article.querySelectorAll('section > h2[id]')];
    let current = null;

    const progress = () => {
        const box = article.getBoundingClientRect();
        const total = box.height - window.innerHeight;
        const read = total <= 0 ? 1 : Math.min(1, Math.max(0, -box.top / total));
        bar.style.inlineSize = `${read * 100}%`;
    };

    // بخش جاری آخرین عنوانی است که از یک‌سوم بالای صفحه گذشته.
    const section = () => {
        const line = window.innerHeight / 3;
        const heading = headings.filter((h) => h.getBoundingClientRect().top <= line).pop() ?? headings[0];

        if (heading.id === current) {
            return;
        }

        current = heading.id;
        links.forEach((link) => {
            if (link.hash === `#${current}`) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    let queued = false;

    const update = () => {
        queued = false;

        if (bar) {
            progress();
        }

        if (links.length > 0 && headings.length > 0) {
            section();
        }
    };

    const schedule = () => {
        if (!queued) {
            queued = true;
            requestAnimationFrame(update);
        }
    };

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule);
    update();
}
