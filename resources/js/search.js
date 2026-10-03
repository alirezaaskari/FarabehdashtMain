/**
 * پیشنهاد فوری جست‌وجو، کلید میان‌بر «/» و پنل فرمان (Ctrl+K).
 *
 * فرم بدون این فایل هم کامل است و به صفحه نتایج می‌رود. این‌جا فقط هنگام
 * تایپ، تکه HTML پیشنهادها از سرور گرفته و زیر کادر گذاشته می‌شود؛ گروه‌بندی
 * و منطق جست‌وجو فقط روی سرور است.
 */

const DELAY = 200;
const MIN_LENGTH = 2;

// اسکلت فقط وقتی پاسخ دیر است؛ پاسخ سریع بی چشمک می‌رسد.
const SKELETON_AFTER = 150;
const SKELETON = `<div class="flex flex-col gap-2.5 px-4 py-3" aria-hidden="true">${
    ['70%', '95%', '60%'].map((width) => `<span class="skeleton-bar" style="width:${width};height:0.875rem"></span>`).join('')
}</div>`;

function attach(form) {
    const input = form.querySelector('input[type="search"]');
    const panel = form.querySelector('[data-search-panel]');

    if (!input || !panel) {
        return;
    }

    // پنل فرمان بی عبارت هم کارها را نشان می‌دهد و هرگز بسته نمی‌شود؛ خود
    // پنجره‌اش باز و بسته می‌شود.
    const palette = form.hasAttribute('data-search-palette');

    panel.id ||= `${input.id}-suggestions`;
    // aria-expanded فقط روی combobox مجاز است و این پنل فهرست پیوند است، نه
    // listbox؛ پس فقط aria-controls، تا صفحه‌خوان مقصد را بشناسد.
    input.setAttribute('aria-controls', panel.id);
    input.setAttribute('autocomplete', 'off');

    let timer = null;
    let controller = null;

    const close = () => {
        panel.hidden = !palette;
    };

    const links = () => [...panel.querySelectorAll('[data-suggestion]')];

    const load = async () => {
        const term = input.value.trim();
        controller?.abort();

        if (term.length < MIN_LENGTH && !palette) {
            close();
            return;
        }

        const request = new AbortController();
        controller = request;
        const url = new URL(form.dataset.searchSuggest, window.location.origin);
        url.searchParams.set('q', term);

        if (palette) {
            url.searchParams.set('palette', '1');
        }

        const skeleton = setTimeout(() => {
            panel.innerHTML = SKELETON;
            panel.hidden = false;
        }, SKELETON_AFTER);
        panel.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, { signal: request.signal, headers: { Accept: 'text/html' } });

            if (!response.ok) {
                return;
            }

            panel.innerHTML = await response.text();
            panel.hidden = panel.innerHTML.trim() === '' && !palette;
        } catch {
            // تایپ تازه‌تر درخواست را لغو کرده یا شبکه قطع است؛ فرم همچنان کار می‌کند.
        } finally {
            clearTimeout(skeleton);

            // درخواست تازه‌تر خودش پنل را جمع می‌کند.
            if (controller === request) {
                panel.removeAttribute('aria-busy');

                if (panel.querySelector('.skeleton-bar')) {
                    panel.innerHTML = '';
                    close();
                }
            }
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(load, DELAY);
    });

    form.addEventListener('keydown', (event) => {
        // در کادر search، Esc اول متن را پاک می‌کند و پنجره را نمی‌بندد؛ پنل
        // فرمان با یک Esc بسته می‌شود.
        if (event.key === 'Escape' && palette) {
            event.preventDefault();
            form.closest('dialog')?.close();
            return;
        }

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

    // پنل فرمان با باز شدن، کارهای این کاربر را بی تایپ می‌گیرد.
    form.addEventListener('fbh:open', load);
}

/**
 * پنل فرمان: Ctrl+K (یا ⌘K) از هر صفحه، و دکمه جست‌وجوی سربرگ روی گوشی.
 *
 * کلید با event.code سنجیده می‌شود، نه event.key: با صفحه‌کلید فارسی همان
 * کلید «ن» است و کاربر نباید برای میان‌بر زبان را عوض کند.
 */
function initPalette() {
    const dialog = document.querySelector('dialog[data-palette]');

    if (!dialog || typeof dialog.showModal !== 'function') {
        return;
    }

    const form = dialog.querySelector('form');
    const input = dialog.querySelector('input[type="search"]');

    const open = () => {
        if (dialog.open) {
            input.select();
            return;
        }

        dialog.showModal();
        input.select();
        form.dispatchEvent(new Event('fbh:open'));
    };

    document.addEventListener('keydown', (event) => {
        if (event.code === 'KeyK' && (event.ctrlKey || event.metaKey) && !event.altKey && !event.shiftKey) {
            event.preventDefault();
            open();
        }
    });

    document.querySelectorAll('[data-palette-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            open();
        });
    });

    // کلیک روی پس‌زمینه تیره، بیرون از کادر، پنل را می‌بندد.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
}

function isTyping(target) {
    return target instanceof HTMLElement
        && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
}

export function initSearch() {
    document.querySelectorAll('form[data-search-suggest]').forEach(attach);
    initPalette();

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
