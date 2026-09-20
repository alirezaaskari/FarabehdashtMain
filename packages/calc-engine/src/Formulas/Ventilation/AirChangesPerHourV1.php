<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Ventilation;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * تعداد تعویض هوا در ساعت.
 *
 *     ACH = Q / V
 *
 * مدت یک تعویض هم برگردانده می‌شود، چون در عمل پرسش واقعی معمولاً این است که
 * «چند دقیقه طول می‌کشد تا هوای اتاق یک بار عوض شود».
 */
final readonly class AirChangesPerHourV1 implements Formula
{
    private const float MINUTES_PER_HOUR = 60.0;

    public function id(): string
    {
        return 'air-changes-per-hour';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'تعداد تعویض هوا در ساعت';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'تعریف نرخ تعویض هوا',
            publisher: 'تعریف مرسوم در مهندسی تهویه صنعتی',
            year: 2019,
            relation: 'ACH = Q / V',
            note: 'نسبت دبی حجمی هوای تازه به حجم فضا. رابطه، تعریف است و فرض اختلاط کامل دارد.',
        );
    }

    public function limitations(): array
    {
        return [
            'رابطه فرض اختلاط کامل هوا را دارد؛ در فضای با جریان کوتاه‌مدار، هوای واقعی رسیده به منطقه تنفسی کمتر است.',
            'نرخ تعویض هوا جایگزین ارزیابی تهویه موضعی نیست و نمی‌گوید آلاینده در منبع کنترل شده است.',
            'مقایسه با حد توصیه‌شده هر کاربری، تفسیر است و در خروجی ابزار انجام نمی‌شود.',
        ];
    }

    public function inputs(): array
    {
        return [
            'airflow' => InputDefinition::single('airflow', 'دبی هوای تازه', Unit::CubicMetrePerHour, 0.0, 1_000_000.0),
            'volume' => InputDefinition::single('volume', 'حجم فضا', Unit::CubicMetre, 0.1, 1_000_000.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'air_changes' => Unit::PerHour,
            'minutes_per_change' => Unit::Minute,
        ];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $airChanges = $inputs->value('airflow') / $inputs->value('volume');

        $notes = [];

        if ($airChanges <= 0.0) {
            $notes[] = 'دبی هوای تازه صفر است؛ با این ورودی تعویض هوایی رخ نمی‌دهد و مدت تعویض بی‌معناست.';
        }

        return new Outcome(
            values: [
                'air_changes' => $airChanges,
                'minutes_per_change' => $airChanges > 0.0 ? self::MINUTES_PER_HOUR / $airChanges : 0.0,
            ],
            notes: $notes,
        );
    }
}
