/** نسخه آفلاین فرمول‌ها؛ هم‌ارز packages/calc-engine/src/Formulas. */

const sum = (values) => values.reduce((total, value) => total + value, 0);

/** همان `log($x, 2)` در PHP: log(x) / log(2)، نه Math.log2. */
const phpLog2 = (value) => Math.log(value) / Math.log(2);

/** خطاهای مشترک فرمول‌هایی که فهرست تراز و فهرست مدت می‌گیرند. */
function pairedListErrors(inputs, itemName, totalMessage) {
    const { levels, durations } = inputs;
    const errors = [];

    if (levels.length !== durations.length) {
        errors.push({
            key: 'durations',
            message: `تعداد مدت‌ها (${durations.length}) با تعداد ترازها (${levels.length}) یکی نیست؛ هر ${itemName} باید هر دو را داشته باشد.`,
        });
    }

    if (sum(durations) <= 0) {
        errors.push({ key: 'durations', message: totalMessage });
    }

    return errors;
}

export default {
    // L_total = 10 × log₁₀( Σ 10^(Lᵢ/10) )
    'sound-pressure-sum@1.0.0': {
        compute({ levels }) {
            let energy = 0;

            for (const level of levels) {
                energy += 10 ** (level / 10);
            }

            const total = 10 * Math.log10(energy);
            const notes = [];

            if (total - Math.max(...levels) < 0.5) {
                notes.push('یک منبع بر تراز کل غالب است؛ کاهش بقیه منابع تغییر محسوسی در نتیجه نمی‌دهد.');
            }

            return { values: { total }, notes };
        },
    },

    // L_source = 10 × log₁₀( 10^(L_total/10) − 10^(L_background/10) )
    'background-noise-correction@1.0.0': {
        crossCheck({ total, background }) {
            if (total - background > 0) {
                return [];
            }

            return [{
                key: 'background',
                message: 'تراز زمینه باید کمتر از تراز کل باشد؛ با این دو عدد، سهم منبع منفی می‌شود.',
            }];
        },

        compute({ total, background }) {
            const difference = total - background;
            const notes = [];

            if (difference < 3) {
                notes.push('اختلاف تراز کل و زمینه کمتر از ۳ دسی‌بل است؛ نتیجه را حد بالای تراز منبع بدانید، نه مقدار آن.');
            } else if (difference > 10) {
                notes.push('صدای زمینه دست‌کم ۱۰ دسی‌بل پایین‌تر است و سهمش ناچیز؛ تصحیح عملاً چیزی را عوض نکرد.');
            }

            return {
                values: {
                    source: 10 * Math.log10(10 ** (total / 10) - 10 ** (background / 10)),
                    difference,
                },
                notes,
            };
        },
    },

    // Leq = 10 × log₁₀( Σ tᵢ·10^(Lᵢ/10) / Σ tᵢ )
    'equivalent-continuous-level@1.0.0': {
        crossCheck(inputs) {
            return pairedListErrors(inputs, 'بازه', 'مجموع مدت بازه‌ها باید بیشتر از صفر باشد.');
        },

        compute({ levels, durations }) {
            let energy = 0;

            levels.forEach((level, index) => {
                energy += durations[index] * 10 ** (level / 10);
            });

            const total = sum(durations);

            return {
                values: {
                    leq: 10 * Math.log10(energy / total),
                    covered_duration: total,
                },
                notes: [],
            };
        },
    },

    // D = 100 × Σ Cᵢ/Tᵢ ، Tᵢ = 8 / 2^((Lᵢ − Lc)/q) ؛ TWA = Lc + q × log₂(D/100)
    'noise-dose@1.0.0': {
        crossCheck(inputs) {
            return pairedListErrors(inputs, 'بازه', 'مجموع مدت بازه‌ها باید بیشتر از صفر باشد.');
        },

        compute({ levels, durations, criterion_level: criterion, exchange_rate: exchangeRate }) {
            let dose = 0;

            levels.forEach((level, index) => {
                const allowedHours = 8 / 2 ** ((level - criterion) / exchangeRate);
                dose += durations[index] / allowedHours;
            });

            dose *= 100;
            const total = sum(durations);
            const notes = [];

            if (total > 8) {
                notes.push('مجموع مدت بازه‌ها بیش از هشت ساعت است؛ مطمئن شوید بازه‌ها هم‌پوشانی ندارند.');
            }

            return {
                values: {
                    dose,
                    twa: criterion + exchangeRate * phpLog2(dose / 100),
                    covered_duration: total,
                },
                notes,
            };
        },
    },

    // L_EX,8h = 10 × log₁₀( Σ (tᵢ/8)·10^(0.1·Lᵢ) )
    'daily-noise-exposure@1.0.0': {
        crossCheck(inputs) {
            return pairedListErrors(inputs, 'کار', 'مجموع مدت کارها باید بیشتر از صفر باشد.');
        },

        compute({ levels, durations }) {
            let energy = 0;

            levels.forEach((level, index) => {
                energy += durations[index] / 8 * 10 ** (0.1 * level);
            });

            const total = sum(durations);
            const notes = [];

            if (total > 24) {
                notes.push('مجموع مدت کارها بیش از یک شبانه‌روز است؛ مطمئن شوید کارها هم‌پوشانی ندارند.');
            }

            return {
                values: {
                    daily_exposure: 10 * Math.log10(energy),
                    covered_duration: total,
                },
                notes,
            };
        },
    },

    // ΔL = −20 × log₁₀( r₂ / r₁ )
    'noise-distance-attenuation@1.0.0': {
        compute({ level, measured_distance: measured, target_distance: target }) {
            const change = -20 * Math.log10(target / measured);
            const notes = [];

            if (change > 0) {
                notes.push('فاصله مورد نظر از فاصله اندازه‌گیری کمتر است؛ تراز نزدیک‌تر به منبع برون‌یابی شده و در میدان نزدیک قابل اتکا نیست.');
            }

            return {
                values: {
                    level_at_target: level + change,
                    change,
                },
                notes,
            };
        },
    },
};
