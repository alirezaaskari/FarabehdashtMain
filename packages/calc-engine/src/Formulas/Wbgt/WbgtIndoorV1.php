<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Wbgt;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * شاخص WBGT برای محیط بدون بار تابشی خورشید (داخل ساختمان یا بیرون در سایه).
 *
 *     WBGT = 0.7 × Tnw + 0.3 × Tg
 *
 * دمای خشک هوا در این حالت وارد رابطه نمی‌شود؛ اثرش از راه دمای گوی و دمای
 * تر طبیعی دیده شده است.
 */
final readonly class WbgtIndoorV1 implements Formula
{
    public function id(): string
    {
        return 'wbgt-indoor';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'شاخص WBGT — بدون بار تابشی خورشید';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'ISO 7243',
            publisher: 'سازمان بین‌المللی استاندارد (ISO)',
            year: 2017,
            relation: 'WBGT = 0.7 × Tnw + 0.3 × Tg',
            note: 'همین رابطه در سند معیار مواجهه گرمایی NIOSH (ویرایش ۲۰۱۶) نیز برای محیط بدون بار تابشی خورشید به‌کار رفته است.',
        );
    }

    public function limitations(): array
    {
        return [
            'WBGT فقط شرایط محیط را توصیف می‌کند؛ قضاوت درباره مواجهه بدون نرخ متابولیک، نوع پوشش و مدت کار ممکن نیست.',
            'این رابطه برای محیط بدون تابش مستقیم خورشید است. در فضای باز آفتابی از رابطه دارای بار تابشی استفاده کنید.',
            'اعتبار نتیجه به کالیبره بودن دماسنج گوی و دماسنج تر طبیعی و به رعایت زمان تعادل دستگاه وابسته است.',
        ];
    }

    public function inputs(): array
    {
        return [
            'natural_wet_bulb' => InputDefinition::single(
                'natural_wet_bulb',
                'دمای تر طبیعی',
                Unit::Celsius,
                -50.0,
                100.0,
            ),
            'globe' => InputDefinition::single(
                'globe',
                'دمای گوی',
                Unit::Celsius,
                -50.0,
                150.0,
            ),
        ];
    }

    public function outputs(): array
    {
        return ['wbgt' => Unit::Celsius];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $naturalWetBulb = $inputs->value('natural_wet_bulb');
        $globe = $inputs->value('globe');

        $notes = [];

        if ($naturalWetBulb > $globe) {
            $notes[] = 'دمای تر طبیعی از دمای گوی بیشتر ثبت شده است؛ این در عمل نادر است و بهتر است اندازه‌گیری بازبینی شود.';
        }

        return new Outcome(
            values: ['wbgt' => 0.7 * $naturalWetBulb + 0.3 * $globe],
            notes: $notes,
        );
    }
}
