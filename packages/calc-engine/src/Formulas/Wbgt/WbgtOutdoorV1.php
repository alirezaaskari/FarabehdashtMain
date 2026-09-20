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
 * شاخص WBGT برای فضای باز با تابش مستقیم خورشید.
 *
 *     WBGT = 0.7 × Tnw + 0.2 × Tg + 0.1 × Ta
 *
 * تفاوتش با حالت بدون تابش فقط در سهم دمای گوی و افزوده شدن دمای خشک هواست.
 * دو رابطه عمداً دو فرمول جداگانه‌اند، نه یک فرمول با کلید انتخاب: ورودی‌هایشان
 * یکی نیست و هر کدام باید مستقل نسخه بگیرد.
 */
final readonly class WbgtOutdoorV1 implements Formula
{
    public function id(): string
    {
        return 'wbgt-outdoor';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'شاخص WBGT — با بار تابشی خورشید';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'ISO 7243',
            publisher: 'سازمان بین‌المللی استاندارد (ISO)',
            year: 2017,
            relation: 'WBGT = 0.7 × Tnw + 0.2 × Tg + 0.1 × Ta',
            note: 'همین رابطه در سند معیار مواجهه گرمایی NIOSH (ویرایش ۲۰۱۶) برای فضای باز با تابش خورشید به‌کار رفته است.',
        );
    }

    public function limitations(): array
    {
        return [
            'WBGT فقط شرایط محیط را توصیف می‌کند؛ قضاوت درباره مواجهه بدون نرخ متابولیک، نوع پوشش و مدت کار ممکن نیست.',
            'این رابطه برای محیط دارای تابش مستقیم خورشید است. در سایه یا داخل ساختمان از رابطه بدون بار تابشی استفاده کنید.',
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
            'dry_bulb' => InputDefinition::single(
                'dry_bulb',
                'دمای خشک هوا',
                Unit::Celsius,
                -50.0,
                100.0,
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
        $dryBulb = $inputs->value('dry_bulb');

        $notes = [];

        if ($naturalWetBulb > $dryBulb) {
            $notes[] = 'دمای تر طبیعی از دمای خشک هوا بیشتر ثبت شده است؛ این در عمل نادر است و بهتر است اندازه‌گیری بازبینی شود.';
        }

        if ($globe < $dryBulb) {
            $notes[] = 'دمای گوی کمتر از دمای خشک هوا ثبت شده است؛ در حضور تابش خورشید انتظار عکس آن می‌رود.';
        }

        return new Outcome(
            values: ['wbgt' => 0.7 * $naturalWetBulb + 0.2 * $globe + 0.1 * $dryBulb],
            notes: $notes,
        );
    }
}
