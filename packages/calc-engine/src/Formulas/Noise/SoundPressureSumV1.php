<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Noise;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * جمع لگاریتمی ترازهای صوتی.
 *
 *     L = 10 × log₁₀( Σ 10^(Lᵢ/10) )
 *
 * تراز صوت لگاریتمی است، پس ترازها را نمی‌شود با هم جمع عددی کرد: دو منبع
 * ۹۰ دسی‌بلی ۱۸۰ نمی‌شوند، ۹۳ می‌شوند. همین یک رابطه هم برای جمع چند منبع
 * هم‌زمان کار می‌کند و هم برای جمع باندهای اکتاو به تراز کل.
 */
final readonly class SoundPressureSumV1 implements Formula
{
    /**
     * فاصله‌ای که زیرش یک منبع عملاً غالب است: اگر تراز کل کمتر از این مقدار
     * از بلندترین منبع بیشتر شود، کار روی بقیه منابع اثر شنیدنی ندارد.
     */
    private const float DOMINANCE_THRESHOLD_DB = 0.5;

    public function id(): string
    {
        return 'sound-pressure-sum';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'جمع لگاریتمی ترازهای صوتی';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'IEC 61672-1 — تعریف تراز فشار صوت',
            publisher: 'کمیسیون بین‌المللی الکتروتکنیک (IEC)',
            year: 2013,
            relation: 'L = 10 × log₁₀( Σ 10^(Lᵢ/10) )',
            note: 'رابطه جمع مستقیماً از تعریف لگاریتمی تراز فشار صوت به‌دست می‌آید و در متون آکوستیک استاندارد است. فرض آن ناهمدوس بودن منابع است.',
        );
    }

    public function limitations(): array
    {
        return [
            'فرض رابطه این است که منابع ناهمدوس‌اند؛ برای صوت همدوس (مثلاً دو بلندگو با یک سیگنال) تداخل فاز نتیجه را عوض می‌کند.',
            'ترازها باید هم‌وزن باشند: جمع A-weighted با A-weighted و خطی با خطی. موتور وزن‌دهی را نمی‌داند و بررسی نمی‌کند.',
            'این محاسبه تراز کل را می‌دهد، نه دز مواجهه؛ دز به مدت مواجهه و نرخ تبادل هم وابسته است.',
        ];
    }

    public function inputs(): array
    {
        return [
            'levels' => InputDefinition::list(
                'levels',
                'ترازهای صوتی',
                Unit::Decibel,
                -20.0,
                200.0,
                2,
                64,
            ),
        ];
    }

    public function outputs(): array
    {
        return ['total' => Unit::Decibel];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $levels = $inputs->values('levels');

        $energy = 0.0;

        foreach ($levels as $level) {
            $energy += 10 ** ($level / 10);
        }

        $total = 10 * log10($energy);

        $notes = [];
        $loudest = max($levels);

        if ($total - $loudest < self::DOMINANCE_THRESHOLD_DB) {
            $notes[] = 'یک منبع بر تراز کل غالب است؛ کاهش بقیه منابع تغییر محسوسی در نتیجه نمی‌دهد.';
        }

        return new Outcome(
            values: ['total' => $total],
            notes: $notes,
        );
    }
}
