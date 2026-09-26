/**
 * زمان‌سنج آزمون آمادگی.
 *
 * زمان واقعی را سرور نگه می‌دارد؛ این اسکریپت فقط باقی‌مانده را نشان می‌دهد و
 * در پایان فرم را خودش می‌فرستد. پایان از روی ساعت حساب می‌شود، نه شمارش
 * تیک‌ها، تا برگه پس‌زمینه‌ای که مرورگر کندش کرده عقب نماند.
 *
 * قرارداد نشانه‌گذاری:
 *   form[data-exam-timer="<ثانیه باقی‌مانده>"]
 *     [data-exam-timer-clock]   جای «دقیقه:ثانیه»
 *     [data-exam-timer-notice]  ناحیه aria-live برای هشدار پنج و یک دقیقه
 */
export function initExamTimers(root = document) {
    root.querySelectorAll('form[data-exam-timer]').forEach(start);
}

function toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
}

function start(form) {
    const clock = form.querySelector('[data-exam-timer-clock]');
    const notice = form.querySelector('[data-exam-timer-notice]');
    const seconds = Number.parseInt(form.dataset.examTimer, 10);

    if (!Number.isFinite(seconds)) {
        return;
    }

    const end = Date.now() + seconds * 1000;
    const warned = new Set();
    let submitted = false;

    form.addEventListener('submit', () => {
        submitted = true;
    });

    const tick = () => {
        const left = Math.max(0, Math.round((end - Date.now()) / 1000));
        const minutes = Math.floor(left / 60);
        const rest = String(left % 60).padStart(2, '0');

        if (clock) {
            clock.textContent = toPersianDigits(`${minutes}:${rest}`);
        }

        for (const mark of [5, 1]) {
            if (notice && left <= mark * 60 && left > 0 && !warned.has(mark)) {
                warned.add(mark);
                notice.textContent = `${toPersianDigits(mark)} دقیقه به پایان آزمون مانده است.`;
            }
        }

        if (left === 0) {
            clearInterval(timer);

            if (!submitted) {
                submitted = true;
                form.requestSubmit();
            }
        }
    };

    const timer = setInterval(tick, 1000);
    tick();
}
