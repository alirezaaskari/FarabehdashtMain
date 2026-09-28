/**
 * اسکریپت پنل مدیریت: فقط آموزش متحرک راهنمای صفحه‌ها.
 *
 * توکن‌های سایت حالت تاریک را از data-theme می‌خوانند و Filament از کلاس dark؛
 * این‌جا یکی را با دیگری هم‌گام می‌کنیم تا آموزش با پوسته پنل یک رنگ باشد.
 */
import { initMotion } from './motion';

const root = document.documentElement;
const syncTheme = () => {
    root.dataset.theme = root.classList.contains('dark') ? 'dark' : 'light';
};

syncTheme();
new MutationObserver(syncTheme).observe(root, { attributes: true, attributeFilter: ['class'] });

initMotion();
