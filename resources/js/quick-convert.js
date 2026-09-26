/**
 * محاسبه سریع صفحه اصلی: نتیجه هنگام تایپ.
 *
 * فرم بدون این فایل کامل است و به ابزار کامل می‌رود. این‌جا فقط ورودی‌ها به
 * `tools.preview` فرستاده و نتیجه‌ای که موتور محاسبه برگرداند نشان داده
 * می‌شود؛ هیچ فرمولی در مرورگر تکرار نمی‌شود.
 */

const DELAY = 250;

function attach(form) {
    const directions = JSON.parse(form.dataset.directions || '[]');
    const picker = form.querySelector('[data-direction-picker]');
    const output = form.querySelector('[data-result-value]');
    const concentration = form.querySelector('[name="concentration"]');
    const weight = form.querySelector('[name="molecular_weight"]');
    const unitLabel = form.querySelector(`label[for="${concentration?.id}"]`);
    const chips = [...form.querySelectorAll('[data-molecular-weight]')];

    if (!directions.length || !output || !concentration || !weight) {
        return;
    }

    let current = directions[0];
    let timer = null;
    let controller = null;

    const show = (text) => {
        output.textContent = text;
    };

    const markChip = () => {
        chips.forEach((chip) => chip.toggleAttribute('data-active', chip.dataset.molecularWeight === weight.value.trim()));
    };

    const load = async () => {
        controller?.abort();

        if (concentration.value.trim() === '' || weight.value.trim() === '') {
            show('—');
            return;
        }

        controller = new AbortController();

        try {
            const response = await fetch(current.preview, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });

            if (!response.ok) {
                show('—');
                return;
            }

            const { rows } = await response.json();
            const [row] = rows ?? [];
            show(row ? `${row.value}${row.unit ? ` ${row.unit}` : ''}` : '—');
        } catch (error) {
            if (error.name !== 'AbortError') {
                show('—');
            }
        }
    };

    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(load, DELAY);
    };

    const choose = (index) => {
        current = directions[index] ?? directions[0];
        form.action = current.action;
        chips.forEach((chip) => {
            const url = new URL(chip.href);
            url.pathname = new URL(current.tool).pathname;
            chip.href = url.toString();
        });

        if (unitLabel?.firstChild) {
            unitLabel.firstChild.textContent = `غلظت (${current.unit}) `;
        }

        schedule();
    };

    if (picker) {
        picker.hidden = false;
        picker.addEventListener('change', (event) => choose(Number(event.target.value)));
    }

    chips.forEach((chip) => {
        chip.addEventListener('click', (event) => {
            event.preventDefault();
            weight.value = chip.dataset.molecularWeight;
            markChip();
            schedule();
        });
    });

    form.addEventListener('input', (event) => {
        if (event.target === weight) {
            markChip();
        }

        if (event.target === concentration || event.target === weight) {
            schedule();
        }
    });

    markChip();
    load();
}

export function initQuickConvert() {
    document.querySelectorAll('form[data-quick-convert]').forEach(attach);
}
