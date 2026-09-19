/**
 * شمارش معکوس دکمه‌های «ارسال دوباره».
 *
 * فقط بهبود تجربه است: تا وقتی سرور اجازه ندهد، دکمه غیرفعال می‌ماند تا کاربر
 * روی چیزی نزند که رد می‌شود. تصمیم واقعی همیشه سمت سرور گرفته می‌شود، پس
 * اگر جاوااسکریپت اجرا نشود، فرم همچنان کار می‌کند.
 *
 * قرارداد نشانه‌گذاری:
 *   [data-countdown="<ثانیه>"]  ریشه
 *     [data-countdown-button]    دکمه‌ای که تا پایان شمارش غیرفعال است
 *     [data-countdown-waiting]   متن حالت انتظار
 *     [data-countdown-ready]     متن حالت آماده
 *     [data-countdown-seconds]   جای عدد ثانیه
 */
export function initCountdowns(root = document) {
    root.querySelectorAll('[data-countdown]').forEach(start);
}

/** عدد داخل جمله فارسی با ارقام فارسی نوشته می‌شود؛ مثل دستور fa@ در Blade. */
function toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
}

function start(element) {
    const button = element.querySelector('[data-countdown-button]');
    const waiting = element.querySelector('[data-countdown-waiting]');
    const ready = element.querySelector('[data-countdown-ready]');
    const seconds = element.querySelector('[data-countdown-seconds]');

    let left = Number.parseInt(element.dataset.countdown, 10);

    if (!Number.isFinite(left) || left <= 0) {
        return render(0);
    }

    render(left);

    const timer = setInterval(() => {
        left -= 1;
        render(left);

        if (left <= 0) {
            clearInterval(timer);
        }
    }, 1000);

    function render(value) {
        const waitingNow = value > 0;

        if (button) {
            button.disabled = waitingNow;
        }

        if (waiting) {
            waiting.hidden = !waitingNow;
        }

        if (ready) {
            ready.hidden = waitingNow;
        }

        if (seconds) {
            seconds.textContent = toPersianDigits(Math.max(value, 0));
        }
    }
}
