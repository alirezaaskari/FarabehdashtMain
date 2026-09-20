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
 * تراز معادل پیوسته — میانگین انرژی صوتی در یک بازه.
 *
 *     L_eq = 10 × log₁₀( Σ(tᵢ × 10^(Lᵢ/10)) / Σtᵢ )
 *
 * تفاوتش با جمع لگاریتمی: آن‌جا چند منبع هم‌زمان با هم جمع می‌شوند، این‌جا
 * چند بازه پشت سر هم بر زمان میانگین گرفته می‌شوند.
 */
final readonly class EquivalentContinuousLevelV1 implements CrossValidated, Formula
{
    private const int MAX_INTERVALS = 64;

    public function id(): string
    {
        return 'equivalent-continuous-level';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'تراز معادل پیوسته (Leq)';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'ISO 9612 — تعیین مواجهه شغلی با صدا',
            publisher: 'سازمان بین‌المللی استاندارد (ISO)',
            year: 2009,
            relation: 'L_eq = 10 × log₁₀( Σ(tᵢ × 10^(Lᵢ/10)) / Σtᵢ )',
            note: 'میانگین‌گیری بر پایه انرژی است، نه بر پایه عدد دسی‌بل. رابطه از تعریف لگاریتمی تراز به‌دست می‌آید.',
        );
    }

    public function limitations(): array
    {
        return [
            'ترازها باید هم‌وزن باشند: میانگین‌گیری A-weighted با A-weighted و خطی با خطی.',
            'رابطه فرض می‌کند تراز در هر بازه ثابت بوده است؛ نوسان درون یک بازه دیده نمی‌شود.',
            'این تراز معادل بازه اندازه‌گیری است، نه لزوماً مواجهه هشت‌ساعته؛ تعمیم دادنش قضاوت کارشناس است.',
        ];
    }

    public function inputs(): array
    {
        return [
            'levels' => InputDefinition::list('levels', 'تراز هر بازه', Unit::Decibel, -20.0, 200.0, 1, self::MAX_INTERVALS),
            'durations' => InputDefinition::list('durations', 'مدت هر بازه', Unit::Minute, 0.0, 1_440.0, 1, self::MAX_INTERVALS),
        ];
    }

    public function outputs(): array
    {
        return [
            'leq' => Unit::Decibel,
            'covered_duration' => Unit::Minute,
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

        $energy = 0.0;

        foreach ($levels as $index => $level) {
            $energy += $durations[$index] * 10 ** ($level / 10);
        }

        $total = array_sum($durations);

        return new Outcome(
            values: [
                'leq' => 10 * log10($energy / $total),
                'covered_duration' => $total,
            ],
            notes: [],
        );
    }
}
