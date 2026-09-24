/**
 * پنل زنده «نتیجه پیشنهادی» دستیار انتخاب ابزار.
 *
 * دستیار بدون این فایل هم کامل کار می‌کند: هر سؤال یک فرم GET است و پنل
 * پس از «سؤال بعدی» به‌روز می‌شود. این‌جا فقط با هر انتخاب، همان صفحه برای
 * پاسخ‌های تازه گرفته و پنلش جایگزین می‌شود — همان پروتوتایپ که پیشنهاد
 * را هم‌زمان با انتخاب نشان می‌دهد. منطق پیشنهاد فقط روی سرور است.
 */

export function initAdvisor() {
    const form = document.querySelector('form[data-advisor]');
    const panel = document.querySelector('[data-advisor-result]');

    if (!form || !panel) {
        return;
    }

    let controller = null;

    form.addEventListener('change', async () => {
        controller?.abort();
        controller = new AbortController();

        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();

        try {
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'text/html' } });

            if (!response.ok) {
                return;
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const fresh = page.querySelector('[data-advisor-result]');

            if (fresh) {
                panel.innerHTML = fresh.innerHTML;
            }
        } catch {
            // انتخاب تازه‌تر درخواست را لغو کرده یا شبکه قطع است؛ پنل فعلی
            // می‌ماند و «سؤال بعدی» همچنان کار می‌کند.
        }
    });
}
