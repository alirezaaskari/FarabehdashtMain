/**
 * منوی کشویی سربرگ.
 *
 * منو یک details است و بدون این فایل هم باز و بسته می‌شود. اینجا فقط رفتار
 * مورد انتظار یک منو اضافه می‌شود: بستن با Escape (و برگشت فوکوس به دکمه)،
 * و بستن با کلیک بیرون از منو.
 */

export function initMenus() {
    const menus = [...document.querySelectorAll('details[data-menu]')];

    if (menus.length === 0) {
        return;
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        for (const menu of menus.filter((m) => m.open)) {
            menu.open = false;
            menu.querySelector('summary')?.focus();
        }
    });

    document.addEventListener('click', (event) => {
        for (const menu of menus) {
            if (menu.open && !menu.contains(event.target)) {
                menu.open = false;
            }
        }
    });
}
