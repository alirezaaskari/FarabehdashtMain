/**
 * نسخه آفلاین فرمول‌های گرمایی، روشنایی، تهویه، ارتعاش و ارگونومی؛
 * هم‌ارز packages/calc-engine/src/Formulas (Wbgt، Lighting، Ventilation، Vibration، Ergonomics).
 */

/** همان `array_sum` در PHP: جمع ترتیبی از صفر. */
function arraySum(values) {
    return values.reduce((sum, value) => sum + value, 0);
}

/** همان `(int)` در PHP: بریدن به سمت صفر. */
function toInt(value) {
    return Math.trunc(value);
}

/** همان `Vibration\Durations::check`: هر ردیف مقدار و مدت دارد و مدت کل صفر نیست. */
function checkDurations(inputs, keys, noun) {
    const durations = inputs.durations;
    const errors = [];

    for (const key of [].concat(keys)) {
        const count = inputs[key].length;

        if (count !== durations.length) {
            errors.push({
                key: 'durations',
                message: `تعداد مدت‌ها (${durations.length}) با تعداد ${noun} (${count}) یکی نیست؛ هر کار باید هر دو را داشته باشد.`,
            });
            break;
        }
    }

    if (arraySum(durations) <= 0) {
        errors.push({ key: 'durations', message: 'مجموع مدت کارها باید بیشتر از صفر باشد.' });
    }

    return errors;
}

const VIBRATION_REFERENCE_HOURS = 8;
const WBV_HORIZONTAL_FACTOR = 1.4;

/** A(8) = √(Σ aᵢ² · Tᵢ / 8)؛ ضریب محور جداگانه ضرب می‌شود. */
function dailyVibration(accelerations, durations) {
    let sum = 0;

    accelerations.forEach((acceleration, index) => {
        sum += acceleration ** 2 * durations[index];
    });

    return Math.sqrt(sum / VIBRATION_REFERENCE_HOURS);
}

const NIOSH_LOAD_CONSTANT = 23;
const NIOSH_KNUCKLE_HEIGHT = 75;

// [بسامد در دقیقه، [[≤۱ ساعت: V<75، V≥75]، [≤۲ ساعت]، [≤۸ ساعت]]]
const NIOSH_FREQUENCY_TABLE = [
    [0.2, [[1.00, 1.00], [0.95, 0.95], [0.85, 0.85]]],
    [0.5, [[0.97, 0.97], [0.92, 0.92], [0.81, 0.81]]],
    [1.0, [[0.94, 0.94], [0.88, 0.88], [0.75, 0.75]]],
    [2.0, [[0.91, 0.91], [0.84, 0.84], [0.65, 0.65]]],
    [3.0, [[0.88, 0.88], [0.79, 0.79], [0.55, 0.55]]],
    [4.0, [[0.84, 0.84], [0.72, 0.72], [0.45, 0.45]]],
    [5.0, [[0.80, 0.80], [0.60, 0.60], [0.35, 0.35]]],
    [6.0, [[0.75, 0.75], [0.50, 0.50], [0.27, 0.27]]],
    [7.0, [[0.70, 0.70], [0.42, 0.42], [0.22, 0.22]]],
    [8.0, [[0.60, 0.60], [0.35, 0.35], [0.18, 0.18]]],
    [9.0, [[0.52, 0.52], [0.30, 0.30], [0.00, 0.15]]],
    [10.0, [[0.45, 0.45], [0.26, 0.26], [0.00, 0.13]]],
    [11.0, [[0.41, 0.41], [0.00, 0.23], [0.00, 0.00]]],
    [12.0, [[0.37, 0.37], [0.00, 0.21], [0.00, 0.00]]],
    [13.0, [[0.00, 0.34], [0.00, 0.00], [0.00, 0.00]]],
    [14.0, [[0.00, 0.31], [0.00, 0.00], [0.00, 0.00]]],
    [15.0, [[0.00, 0.28], [0.00, 0.00], [0.00, 0.00]]],
];

const NIOSH_DURATIONS = { 1: 0, 2: 1, 8: 2 };

const NIOSH_COUPLINGS = {
    1: [1.00, 1.00],
    2: [0.95, 1.00],
    3: [0.90, 0.90],
};

/** ضریب بسامد از جدول NIOSH با درون‌یابی خطی میان دو ردیف. */
function nioshFrequencyMultiplier(inputs) {
    const column = NIOSH_DURATIONS[toInt(inputs.duration)];
    const side = inputs.vertical < NIOSH_KNUCKLE_HEIGHT ? 0 : 1;
    const frequency = Math.max(0.2, inputs.frequency);
    let previous = null;

    for (const [rate, row] of NIOSH_FREQUENCY_TABLE) {
        const value = row[column][side];

        if (frequency <= rate) {
            if (previous === null || frequency === rate) {
                return value;
            }

            const [lowRate, lowValue] = previous;

            return lowValue + (value - lowValue) * (frequency - lowRate) / (rate - lowRate);
        }

        previous = [rate, value];
    }

    return 0;
}

export default {
    // WBGT = 0.7 × Tnw + 0.3 × Tg
    'wbgt-indoor@1.0.0': {
        compute({ natural_wet_bulb: naturalWetBulb, globe }) {
            const notes = [];

            if (naturalWetBulb > globe) {
                notes.push('دمای تر طبیعی از دمای گوی بیشتر ثبت شده است؛ این در عمل نادر است و بهتر است اندازه‌گیری بازبینی شود.');
            }

            return { values: { wbgt: 0.7 * naturalWetBulb + 0.3 * globe }, notes };
        },
    },

    // WBGT = 0.7 × Tnw + 0.2 × Tg + 0.1 × Ta
    'wbgt-outdoor@1.0.0': {
        compute({ natural_wet_bulb: naturalWetBulb, globe, dry_bulb: dryBulb }) {
            const notes = [];

            if (naturalWetBulb > dryBulb) {
                notes.push('دمای تر طبیعی از دمای خشک هوا بیشتر ثبت شده است؛ این در عمل نادر است و بهتر است اندازه‌گیری بازبینی شود.');
            }

            if (globe < dryBulb) {
                notes.push('دمای گوی کمتر از دمای خشک هوا ثبت شده است؛ در حضور تابش خورشید انتظار عکس آن می‌رود.');
            }

            return { values: { wbgt: 0.7 * naturalWetBulb + 0.2 * globe + 0.1 * dryBulb }, notes };
        },
    },

    // U₀ = Emin / Eavg و Emin / Emax
    'illuminance-uniformity@1.0.0': {
        compute({ readings }) {
            const average = arraySum(readings) / readings.length;
            const minimum = Math.min(...readings);
            const maximum = Math.max(...readings);
            const notes = [];

            if (average <= 0) {
                notes.push('همه قرائت‌ها صفرند؛ نسبت یکنواختی در این حالت تعریف نمی‌شود و صفر گزارش شده است.');
            }

            return {
                values: {
                    average,
                    minimum,
                    maximum,
                    uniformity_min_average: average > 0 ? minimum / average : 0,
                    uniformity_min_max: maximum > 0 ? minimum / maximum : 0,
                },
                notes,
            };
        },
    },

    // ACH = Q / V؛ مدت هر تعویض = 60 / ACH
    'air-changes-per-hour@1.0.0': {
        compute({ airflow, volume }) {
            const airChanges = airflow / volume;
            const notes = [];

            if (airChanges <= 0) {
                notes.push('دبی هوای تازه صفر است؛ با این ورودی تعویض هوایی رخ نمی‌دهد و مدت تعویض بی‌معناست.');
            }

            return {
                values: {
                    air_changes: airChanges,
                    minutes_per_change: airChanges > 0 ? 60 / airChanges : 0,
                },
                notes,
            };
        },
    },

    // Q = K × G × 1000 / C
    'dilution-ventilation@1.0.0': {
        compute({ generation_rate: generationRate, target_concentration: target, mixing_factor: mixingFactor }) {
            const ideal = generationRate * 1000 / target;

            return {
                values: {
                    required_airflow: mixingFactor * ideal,
                    ideal_airflow: ideal,
                },
                notes: [],
            };
        },
    },

    // A(8) = √(Σ ahv,i² · Ti / 8)
    'hand-arm-vibration@1.0.0': {
        crossCheck(inputs) {
            return checkDurations(inputs, 'magnitudes', 'مقدارها');
        },
        compute({ magnitudes, durations }) {
            return {
                values: {
                    daily_exposure: dailyVibration(magnitudes, durations),
                    covered_duration: arraySum(durations),
                },
                notes: [],
            };
        },
    },

    // Aj(8) = kj × √(Σ awj,i² · Ti / 8)؛ kx = ky = 1.4، kz = 1؛ A(8) = بیشینه سه محور
    'whole-body-vibration@1.0.0': {
        crossCheck(inputs) {
            return checkDurations(inputs, ['x_axis', 'y_axis', 'z_axis'], 'قرائت‌های محور');
        },
        compute({ x_axis: xAxis, y_axis: yAxis, z_axis: zAxis, durations }) {
            const x = WBV_HORIZONTAL_FACTOR * dailyVibration(xAxis, durations);
            const y = WBV_HORIZONTAL_FACTOR * dailyVibration(yAxis, durations);
            const z = 1.0 * dailyVibration(zAxis, durations);
            const daily = Math.max(x, y, z);
            const dominant = daily === z ? 'z' : daily === x ? 'x' : 'y';

            return {
                values: {
                    x_exposure: x,
                    y_exposure: y,
                    z_exposure: z,
                    daily_exposure: daily,
                    covered_duration: arraySum(durations),
                },
                notes: [`محور غالب: ${dominant}. کنترل (صندلی، سرعت، سطح مسیر) را از همین محور شروع کنید.`],
            };
        },
    },

    // RWL = LC × HM × VM × DM × AM × FM × CM؛ LI = بار / RWL
    'niosh-lifting@1.0.0': {
        crossCheck(inputs) {
            const errors = [];

            if (!Object.hasOwn(NIOSH_DURATIONS, toInt(inputs.duration))
                || Math.floor(inputs.duration) !== inputs.duration) {
                errors.push({ key: 'duration', message: 'مدت کار یکی از سه گروه ۱، ۲ یا ۸ ساعت است.' });
            }

            if (Math.floor(inputs.coupling) !== inputs.coupling) {
                errors.push({ key: 'coupling', message: 'کیفیت دستگیره یکی از سه گروه خوب (۱)، متوسط (۲) یا ضعیف (۳) است.' });
            }

            if (errors.length === 0 && nioshFrequencyMultiplier(inputs) <= 0) {
                errors.push({
                    key: 'frequency',
                    message: 'با این بسامد و مدت کار، ضریب بسامد در جدول NIOSH صفر است؛ کار بیرون از دامنه معادله است و باید بازطراحی شود.',
                });
            }

            return errors;
        },
        compute(inputs) {
            const { vertical } = inputs;
            const multipliers = {
                horizontal_multiplier: 25 / Math.max(25, inputs.horizontal),
                vertical_multiplier: 1 - 0.003 * Math.abs(vertical - NIOSH_KNUCKLE_HEIGHT),
                distance_multiplier: 0.82 + 4.5 / Math.max(25, inputs.travel),
                asymmetric_multiplier: 1 - 0.0032 * inputs.asymmetry,
                frequency_multiplier: nioshFrequencyMultiplier(inputs),
                coupling_multiplier: NIOSH_COUPLINGS[toInt(inputs.coupling)][vertical < NIOSH_KNUCKLE_HEIGHT ? 0 : 1],
            };

            // همان array_product: ضرب ترتیبی از یک.
            const product = Object.values(multipliers).reduce((carry, value) => carry * value, 1);
            const recommended = NIOSH_LOAD_CONSTANT * product;
            const notes = [];

            if (inputs.horizontal < 25 || inputs.travel < 25) {
                notes.push('فاصله افقی یا جابه‌جایی عمودی کمتر از ۲۵ سانتی‌متر، طبق راهنمای NIOSH همان ۲۵ گرفته شد.');
            }

            return {
                values: {
                    recommended_weight: recommended,
                    lifting_index: inputs.load / recommended,
                    ...multipliers,
                },
                notes,
            };
        },
    },
};
