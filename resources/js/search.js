/**
 * پیشنهاد فوری جست‌وجو و کلید میان‌بر «/».
 *
 * فرم بدون این فایل هم کامل است و به صفحه نتایج می‌رود. این‌جا فقط هنگام
 * تایپ، تکه HTML پیشنهادها از سرور گرفته و زیر کادر گذاشته می‌شود؛ گروه‌بندی
 * و منطق جست‌وجو فقط روی سرور است.
 */

const DELAY = 200;
const MIN_LENGTH = 2;

function attach(form) {
    const input = form.querySelector('input[type="search"]');
    const panel = form.querySelector('[data-search-panel]');

    if (!input || !panel) {
        return;
    }

    panel.id ||= `${input.id}-suggestions`;
    input.setAttribute('aria-controls', panel.id);
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('autocomplete', 'off');

    let timer = null;
    let controller = null;

    const close = () => {
        panel.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    };

    const links = () => [...panel.querySelectorAll('[data-suggestion]')];

    const load = async () => {
        const term = input.value.trim();
        controller?.abort();

        if (term.length < MIN_LENGTH) {
            close();
            return;
        }

        controller = new AbortController();
        const url = new URL(form.dataset.searchSuggest, window.location.origin);
        url.searchParams.set('q', term);

        try {
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'text/html' } });

            if (!response.ok) {
                return;
            }

            panel.innerHTML = await response.text();
            panel.hidden = panel.innerHTML.trim() === '';
            input.setAttribute('aria-expanded', String(!panel.hidden));
        } catch {
            // تایپ تازه‌تر درخواست را لغو کرده یا شبکه قطع است؛ فرم همچنان کار می‌کند.
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(load, DELAY);
    });

    form.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            close();
            input.focus();
            return;
        }

        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return;
        }

        const items = links();

        if (panel.hidden || items.length === 0) {
            return;
        }

        event.preventDefault();
        const index = items.indexOf(document.activeElement);
        const next = event.key === 'ArrowDown' ? index + 1 : index - 1;

        if (next < 0) {
            input.focus();
        } else {
            items[Math.min(next, items.length - 1)].focus();
        }
    });

    form.addEventListener('focusout', (event) => {
        if (!form.contains(event.relatedTarget)) {
            close();
        }
    });
}

function isTyping(target) {
    return target instanceof HTMLElement
        && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
}

export function initSearch() {
    document.querySelectorAll('form[data-search-suggest]').forEach(attach);

    // «/» از هر صفحه جست‌وجو را باز می‌کند: کادر دیده‌شده اگر هست، وگرنه صفحه جست‌وجو.
    document.addEventListener('keydown', (event) => {
        if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey || isTyping(event.target)) {
            return;
        }

        const visible = [...document.querySelectorAll('form[role="search"] input[type="search"]')]
            .find((input) => input.offsetParent !== null);

        if (visible) {
            event.preventDefault();
            visible.focus();
            return;
        }

        const fallback = document.querySelector('a[href$="/search"]');

        if (fallback) {
            event.preventDefault();
            window.location.assign(fallback.href);
        }
    });
}
