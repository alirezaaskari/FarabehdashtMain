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
 * تراز مواجهه روزانه با صدا، نرمال‌شده به هشت ساعت (L_EX,8h).
 *
 *     L_EX,8h = 10 × log₁₀( Σ (Tₘ / T₀) × 10^(0.1 × Lₘ) ) ، T₀ = 8 h
 *
 * برخلاف ابزار دز، تراز معیار و نرخ تبادل ندارد: نرخ تبادل ۳ دسی‌بل در خود
 * تعریف انرژی است و عدد مستقیماً با حدهای روزانه مقایسه‌پذیر است.
 */
final readonly class DailyNoiseExposureV1 implements CrossValidated, Formula
{
    private const int MAX_TASKS = 48;

    private const float REFERENCE_HOURS = 8.0;

    public function id(): string
    {
        return 'daily-noise-exposure';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'تراز مواجهه روزانه با صدا (L_EX,8h)';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'ISO 9612 — Acoustics: Determination of occupational noise exposure, Engineering method',
            publisher: 'سازمان بین‌المللی استانداردسازی (ISO)',
            year: 2009,
            relation: 'L_EX,8h = 10·log₁₀(Σ (Tₘ/8) · 10^(0.1·L_p,A,eqT,m))',
            note: 'روش مبتنی بر کار (task-based): هر ردیف یک کار با تراز معادل و مدت روزانه‌اش است.',
        );
    }

    public function limitations(): array
    {
        return [
            'عدم‌قطعیت اندازه‌گیری که ISO 9612 محاسبه‌اش را می‌خواهد، در این عدد نیست.',
            'تراز هر کار باید تراز معادل A-وزن همان کار باشد؛ تراز اوج (C-وزن) جداگانه ارزیابی می‌شود.',
            'اثر وسیله حفاظت شنوایی در این محاسبه دیده نشده است.',
            'مقایسه با حد مواجهه، تفسیر است و در خروجی ابزار انجام نمی‌شود.',
        ];
    }

    public function inputs(): array
    {
        return [
            'levels' => InputDefinition::list('levels', 'تراز معادل هر کار', Unit::Decibel, 0.0, 160.0, 1, self::MAX_TASKS),
            'durations' => InputDefinition::list('durations', 'مدت روزانه هر کار', Unit::Hour, 0.0, 24.0, 1, self::MAX_TASKS),
        ];
    }

    public function outputs(): array
    {
        return [
            'daily_exposure' => Unit::Decibel,
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
                    'تعداد مدت‌ها (%d) با تعداد ترازها (%d) یکی نیست؛ هر کار باید هر دو را داشته باشد.',
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
                'مجموع مدت کارها باید بیشتر از صفر باشد.',
                ['given' => array_sum($durations)],
            );
        }

        return $errors;
    }

    public function compute(InputSet $inputs): Outcome
    {
        $durations = $inputs->values('durations');

        $energy = 0.0;

        foreach ($inputs->values('levels') as $index => $level) {
            $energy += $durations[$index] / self::REFERENCE_HOURS * 10 ** (0.1 * $level);
        }

        $total = array_sum($durations);

        $notes = [];

        if ($total > 24.0) {
            $notes[] = 'مجموع مدت کارها بیش از یک شبانه‌روز است؛ مطمئن شوید کارها هم‌پوشانی ندارند.';
        }

        return new Outcome(
            values: [
                'daily_exposure' => 10.0 * log10($energy),
                'covered_duration' => $total,
            ],
            notes: $notes,
        );
    }
}
