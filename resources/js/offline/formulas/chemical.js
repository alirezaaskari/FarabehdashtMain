/** نسخه آفلاین فرمول‌ها؛ هم‌ارز packages/calc-engine/src/Formulas/Chemical. */

// MolarVolume.php
const GAS_CONSTANT = 8.314462618;
const ABSOLUTE_ZERO_CELSIUS = -273.15;
const CONVENTIONAL_LITRES_PER_MOLE = 24.45;
const REFERENCE_KELVIN = 298.15;
const REFERENCE_KILOPASCAL = 101.325;

/** Vm = R × T / P */
function litresPerMole(celsius, kilopascal) {
    return GAS_CONSTANT * (celsius - ABSOLUTE_ZERO_CELSIUS) / kilopascal;
}

/** Vm = 24.45 × (T / 298.15) × (101.325 / P) */
function conventionalLitresPerMole(celsius, kilopascal) {
    const kelvin = celsius - ABSOLUTE_ZERO_CELSIUS;

    return CONVENTIONAL_LITRES_PER_MOLE
        * (kelvin / REFERENCE_KELVIN)
        * (REFERENCE_KILOPASCAL / kilopascal);
}

/** array_sum: جمع به همان ترتیب PHP. */
function sum(values) {
    let total = 0;

    for (const value of values) {
        total += value;
    }

    return total;
}

// mg/m³ = ppm × MW / Vm
function ppmToMass(molarVolumeOf) {
    return {
        compute(inputs) {
            const molarVolume = molarVolumeOf(inputs.temperature, inputs.pressure);

            return {
                values: {
                    concentration: inputs.concentration * inputs.molecular_weight / molarVolume,
                    molar_volume: molarVolume,
                },
                notes: [],
            };
        },
    };
}

// ppm = mg/m³ × Vm / MW
function massToPpm(molarVolumeOf) {
    return {
        compute(inputs) {
            const molarVolume = molarVolumeOf(inputs.temperature, inputs.pressure);

            return {
                values: {
                    concentration: inputs.concentration * molarVolume / inputs.molecular_weight,
                    molar_volume: molarVolume,
                },
                notes: [],
            };
        },
    };
}

// TimeWeightedAverage.php — TWA = Σ(Cᵢ × tᵢ) / Σtᵢ
const timeWeightedAverage = {
    crossCheck(inputs) {
        const { concentrations, durations } = inputs;
        const errors = [];

        if (concentrations.length !== durations.length) {
            errors.push({
                key: 'durations',
                message: `تعداد مدت‌ها (${durations.length}) با تعداد غلظت‌ها (${concentrations.length}) یکی نیست؛ هر بازه باید هر دو را داشته باشد.`,
            });
        }

        if (sum(durations) <= 0) {
            errors.push({ key: 'durations', message: 'مجموع مدت بازه‌ها باید بیشتر از صفر باشد.' });
        }

        return errors;
    },

    compute(inputs) {
        const { concentrations, durations } = inputs;
        let weighted = 0;

        concentrations.forEach((concentration, index) => {
            weighted += concentration * durations[index];
        });

        const total = sum(durations);
        const notes = [];

        if (total < 480) {
            notes.push('مدت نمونه‌برداری کمتر از یک شیفت هشت‌ساعته است؛ این عدد میانگین همان بازه است، نه TWA هشت‌ساعته.');
        }

        return {
            values: { twa: weighted / total, covered_duration: total },
            notes,
        };
    },
};

// MixtureExposureIndex.php — EI = Σ(Cᵢ / Lᵢ)
const mixtureExposureIndex = {
    crossCheck(inputs) {
        const { concentrations, limits } = inputs;

        if (concentrations.length === limits.length) {
            return [];
        }

        return [{
            key: 'limits',
            message: `تعداد حدها (${limits.length}) با تعداد غلظت‌ها (${concentrations.length}) یکی نیست؛ هر جزء باید هر دو را داشته باشد.`,
        }];
    },

    compute(inputs) {
        const { concentrations, limits } = inputs;
        const ratios = concentrations.map((concentration, index) => concentration / limits[index]);
        const index = sum(ratios);
        const largest = Math.max(...ratios);
        const notes = [];

        if (index > 0 && largest / index > 0.8) {
            notes.push('بیش از هشتاد درصد شاخص از یک جزء می‌آید؛ کنترل همان جزء بیشترین اثر را دارد.');
        }

        return {
            values: {
                exposure_index: index,
                dominant_share: index > 0 ? largest / index * 100 : 0,
            },
            notes,
        };
    },
};

// Brief و Scala — RF = (8/h) × (24−h)/16 ، RF هفتگی = (40/H) × (168−H)/128
const briefScalaAdjustment = {
    crossCheck(inputs) {
        if (inputs.weekly_hours >= inputs.shift_hours) {
            return [];
        }

        return [{ key: 'weekly_hours', message: 'ساعت کار هفتگی نمی‌تواند کمتر از مدت یک شیفت باشد.' }];
    },

    compute(inputs) {
        const shift = inputs.shift_hours;
        const week = inputs.weekly_hours;

        const daily = Math.min(1, (8 / shift) * (24 - shift) / 16);
        const weekly = Math.min(1, (40 / week) * (168 - week) / 128);
        const notes = [];

        if (daily === 1 && weekly === 1) {
            notes.push('برنامه کاری از هشت ساعت در روز و چهل ساعت در هفته بیشتر نیست؛ این روش حد را تغییر نمی‌دهد.');
        }

        return {
            values: {
                daily_factor: daily,
                weekly_factor: weekly,
                reduction_factor: Math.min(daily, weekly),
            },
            notes,
        };
    },
};

export default {
    'ppm-to-mass-concentration@1.0.0': ppmToMass(litresPerMole),
    'ppm-to-mass-concentration@2.0.0': ppmToMass(conventionalLitresPerMole),
    'mass-concentration-to-ppm@1.0.0': massToPpm(litresPerMole),
    'mass-concentration-to-ppm@2.0.0': massToPpm(conventionalLitresPerMole),
    'twa-ppm@1.0.0': timeWeightedAverage,
    'twa-mass-concentration@1.0.0': timeWeightedAverage,
    'mixture-exposure-index-ppm@1.0.0': mixtureExposureIndex,
    'mixture-exposure-index-mass@1.0.0': mixtureExposureIndex,
    'brief-scala-adjustment@1.0.0': briefScalaAdjustment,
};
