<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

use Farabehdasht\CalcEngine\CrossValidated;
use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputError;
use Farabehdasht\CalcEngine\Input\InputErrorCode;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * ضریب کاهش حد مواجهه برای شیفت غیرمعمول، به روش Brief و Scala.
 *
 *     RF روزانه = (8 / h) × (24 − h) / 16
 *     RF هفتگی = (40 / H) × (168 − H) / 128
 *
 * ضریب حاکم کوچک‌ترِ این دو است. روش برای شیفت کوتاه‌تر حد را بالا نمی‌برد،
 * پس هر ضریب بیش از یک، یک گرفته می‌شود.
 *
 * خود حد ورودی نیست: حد با هر واحدی باشد در همین ضریب ضرب می‌شود، و گرفتن
 * آن فقط واحد ضمنی به موتور اضافه می‌کرد.
 */
final readonly class BriefScalaAdjustmentV1 implements CrossValidated, Formula
{
    public function id(): string
    {
        return 'brief-scala-adjustment';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'تعدیل حد مواجهه برای شیفت غیرمعمول (Brief و Scala)';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'Occupational exposure limits for novel work schedules',
            publisher: 'Brief R.S., Scala R.A. — American Industrial Hygiene Association Journal 36(6)',
            year: 1975,
            relation: 'RF = (8/h) × (24−h)/16 ، RF هفتگی = (40/H) × (168−H)/128',
            note: 'ضریب حاکم کوچک‌ترِ ضریب روزانه و هفتگی است و هرگز بیش از یک گرفته نمی‌شود.',
        );
    }

    public function limitations(): array
    {
        return [
            'روش برای ماده‌هایی است که اثرشان به دز تجمعی وابسته است؛ برای حد سقفی و اثر تحریکی حاد کاربرد ندارد.',
            'روش فرض می‌کند زمان بازیابی میان شیفت‌ها کوتاه‌تر شده است؛ نیمه‌عمر ماده در بدن دیده نمی‌شود و روش‌های دیگر (مثل مدل‌های فارماکوکینتیک) عدد دیگری می‌دهند.',
            'ضرب ضریب در حد و انتخاب حد مرجع، کار کارشناس است؛ عدد حاصل حد رسمی تازه نیست.',
        ];
    }

    public function inputs(): array
    {
        return [
            'shift_hours' => InputDefinition::single('shift_hours', 'مدت شیفت روزانه', Unit::Hour, 1.0, 20.0),
            'weekly_hours' => InputDefinition::single('weekly_hours', 'ساعت کار در هفته', Unit::Hour, 1.0, 120.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'daily_factor' => Unit::Ratio,
            'weekly_factor' => Unit::Ratio,
            'reduction_factor' => Unit::Ratio,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        if ($inputs->value('weekly_hours') >= $inputs->value('shift_hours')) {
            return [];
        }

        return [
            new InputError(
                'weekly_hours',
                InputErrorCode::OutOfRange,
                'ساعت کار هفتگی نمی‌تواند کمتر از مدت یک شیفت باشد.',
                ['min' => $inputs->value('shift_hours'), 'given' => $inputs->value('weekly_hours')],
            ),
        ];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $shift = $inputs->value('shift_hours');
        $week = $inputs->value('weekly_hours');

        $daily = min(1.0, (8.0 / $shift) * (24.0 - $shift) / 16.0);
        $weekly = min(1.0, (40.0 / $week) * (168.0 - $week) / 128.0);

        $notes = [];

        if ($daily === 1.0 && $weekly === 1.0) {
            $notes[] = 'برنامه کاری از هشت ساعت در روز و چهل ساعت در هفته بیشتر نیست؛ این روش حد را تغییر نمی‌دهد.';
        }

        return new Outcome(
            values: [
                'daily_factor' => $daily,
                'weekly_factor' => $weekly,
                'reduction_factor' => min($daily, $weekly),
            ],
            notes: $notes,
        );
    }
}
