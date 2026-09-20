<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Noise;

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
 * دز صدا و تراز مواجهه هشت‌ساعته معادل آن.
 *
 *     Tᵢ = 8 / 2^((Lᵢ − L_c) / q)
 *     D  = 100 × Σ(Cᵢ / Tᵢ)
 *     TWA = L_c + q × log₂(D / 100)
 *
 * `L_c` تراز معیار و `q` نرخ تبادل است. هیچ‌کدام در کد ثابت نشده‌اند و هر دو
 * ورودی اجباری‌اند، چون بین مراجع فرق دارند (مثلاً ۹۰ و ۵ در یک مرجع، ۸۵ و ۳
 * در مرجعی دیگر). ثابت‌کردنشان یعنی ابزار بی‌صدا یک مرجع را به کاربر تحمیل
 * کند.
 */
final readonly class NoiseDoseV1 implements CrossValidated, Formula
{
    private const int MAX_INTERVALS = 48;

    private const float REFERENCE_DURATION_HOURS = 8.0;

    public function id(): string
    {
        return 'noise-dose';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'دز صدا و تراز معادل هشت‌ساعته';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'رابطه دز صدا با مدت مجاز و نرخ تبادل',
            publisher: 'رابطه مشترک مراجع مواجهه شغلی با صدا',
            year: 2016,
            relation: 'T = 8 / 2^((L − L_c)/q) ، D = 100 × Σ(C/T) ، TWA = L_c + q × log₂(D/100)',
            note: 'تراز معیار و نرخ تبادل ورودی‌اند و در فرمول ثابت نشده‌اند، چون مقدارشان بین مراجع فرق می‌کند.',
        );
    }

    public function limitations(): array
    {
        return [
            'تراز معیار و نرخ تبادل را کاربر تعیین می‌کند؛ انتخاب مرجع و درستی‌اش بر عهده کارشناس است.',
            'دز بیش از ۱۰۰ درصد یک عدد است، نه حکم انطباق یا عدم انطباق قانونی.',
            'رابطه فرض می‌کند تراز در هر بازه ثابت بوده و مواجهه پیوسته است؛ صدای ضربه‌ای جداگانه ارزیابی می‌شود.',
            'اثر وسیله حفاظت شنوایی در این محاسبه دیده نشده است.',
        ];
    }

    public function inputs(): array
    {
        return [
            'levels' => InputDefinition::list('levels', 'تراز هر بازه', Unit::Decibel, -20.0, 200.0, 1, self::MAX_INTERVALS),
            'durations' => InputDefinition::list('durations', 'مدت هر بازه', Unit::Hour, 0.0, 24.0, 1, self::MAX_INTERVALS),
            'criterion_level' => InputDefinition::single('criterion_level', 'تراز معیار', Unit::Decibel, 70.0, 100.0),
            'exchange_rate' => InputDefinition::single('exchange_rate', 'نرخ تبادل', Unit::Decibel, 2.0, 6.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'dose' => Unit::Percent,
            'twa' => Unit::Decibel,
            'covered_duration' => Unit::Hour,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        $levels = $inputs->values('levels');
        $durations = $inputs->values('durations');

        $errors = [];

        if (count($levels) !== count($durations)) {
            $errors[] = new InputError(
                'durations',
                InputErrorCode::TooFewItems,
                sprintf(
                    'تعداد مدت‌ها (%d) با تعداد ترازها (%d) یکی نیست؛ هر بازه باید هر دو را داشته باشد.',
                    count($durations),
                    count($levels),
                ),
                ['expected' => count($levels), 'given' => count($durations)],
            );
        }

        if (array_sum($durations) <= 0.0) {
            $errors[] = new InputError(
                'durations',
                InputErrorCode::OutOfRange,
                'مجموع مدت بازه‌ها باید بیشتر از صفر باشد.',
                ['given' => array_sum($durations)],
            );
        }

        return $errors;
    }

    public function compute(InputSet $inputs): Outcome
    {
        $levels = $inputs->values('levels');
        $durations = $inputs->values('durations');
        $criterion = $inputs->value('criterion_level');
        $exchangeRate = $inputs->value('exchange_rate');

        $dose = 0.0;

        foreach ($levels as $index => $level) {
            $allowedHours = self::REFERENCE_DURATION_HOURS / 2 ** (($level - $criterion) / $exchangeRate);
            $dose += $durations[$index] / $allowedHours;
        }

        $dose *= 100.0;
        $total = array_sum($durations);

        $notes = [];

        if ($total > 8.0) {
            $notes[] = 'مجموع مدت بازه‌ها بیش از هشت ساعت است؛ مطمئن شوید بازه‌ها هم‌پوشانی ندارند.';
        }

        return new Outcome(
            values: [
                'dose' => $dose,
                'twa' => $criterion + $exchangeRate * log($dose / 100.0, 2),
                'covered_duration' => $total,
            ],
            notes: $notes,
        );
    }
}
