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
 * حذف صدای زمینه از تراز اندازه‌گیری‌شده.
 *
 *     L_source = 10 × log₁₀( 10^(L_total/10) − 10^(L_background/10) )
 *
 * وارونِ جمع لگاریتمی است: انرژی زمینه از انرژی کل کم می‌شود، نه دسی‌بلش از
 * دسی‌بل کل.
 */
final readonly class BackgroundNoiseCorrectionV1 implements CrossValidated, Formula
{
    /**
     * زیر این اختلاف، تصحیح قابل اتکا نیست و نتیجه فقط حد بالا است — قاعده‌ای
     * که در استانداردهای اندازه‌گیری صدا مرسوم است.
     */
    private const float RELIABLE_DIFFERENCE_DB = 3.0;

    /**
     * بالای این اختلاف، سهم زمینه کمتر از ۰٫۵ دسی‌بل است و تصحیح عملاً بی‌اثر.
     */
    private const float NEGLIGIBLE_DIFFERENCE_DB = 10.0;

    public function id(): string
    {
        return 'background-noise-correction';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'حذف صدای زمینه';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'IEC 61672-1 — تعریف تراز فشار صوت',
            publisher: 'کمیسیون بین‌المللی الکتروتکنیک (IEC)',
            year: 2013,
            relation: 'L_source = 10 × log₁₀( 10^(L_total/10) − 10^(L_background/10) )',
            note: 'رابطه وارونِ جمع لگاریتمی ترازهاست و مستقیماً از تعریف لگاریتمی تراز به‌دست می‌آید.',
        );
    }

    public function limitations(): array
    {
        return [
            'اگر اختلاف تراز کل و تراز زمینه کمتر از ۳ دسی‌بل باشد، تصحیح قابل اتکا نیست و نتیجه را باید حد بالا دانست.',
            'فرض رابطه این است که صدای زمینه هنگام روشن‌بودن منبع همان مقدار بوده که جداگانه اندازه گرفته شد.',
            'منبع و زمینه باید ناهمدوس باشند؛ برای صوت همدوس تداخل فاز نتیجه را عوض می‌کند.',
        ];
    }

    public function inputs(): array
    {
        return [
            'total' => InputDefinition::single('total', 'تراز کل (منبع روشن)', Unit::Decibel, -20.0, 200.0),
            'background' => InputDefinition::single('background', 'تراز زمینه (منبع خاموش)', Unit::Decibel, -20.0, 200.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'source' => Unit::Decibel,
            'difference' => Unit::Decibel,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        $difference = $inputs->value('total') - $inputs->value('background');

        if ($difference > 0.0) {
            return [];
        }

        // انرژی منفی یعنی لگاریتم بی‌معنا؛ این خطای داده است نه نتیجه عددی.
        return [new InputError(
            'background',
            InputErrorCode::OutOfRange,
            'تراز زمینه باید کمتر از تراز کل باشد؛ با این دو عدد، سهم منبع منفی می‌شود.',
            ['difference' => $difference],
        )];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $total = $inputs->value('total');
        $background = $inputs->value('background');
        $difference = $total - $background;

        $notes = [];

        if ($difference < self::RELIABLE_DIFFERENCE_DB) {
            $notes[] = 'اختلاف تراز کل و زمینه کمتر از ۳ دسی‌بل است؛ نتیجه را حد بالای تراز منبع بدانید، نه مقدار آن.';
        } elseif ($difference > self::NEGLIGIBLE_DIFFERENCE_DB) {
            $notes[] = 'صدای زمینه دست‌کم ۱۰ دسی‌بل پایین‌تر است و سهمش ناچیز؛ تصحیح عملاً چیزی را عوض نکرد.';
        }

        return new Outcome(
            values: [
                'source' => 10 * log10(10 ** ($total / 10) - 10 ** ($background / 10)),
                'difference' => $difference,
            ],
            notes: $notes,
        );
    }
}
