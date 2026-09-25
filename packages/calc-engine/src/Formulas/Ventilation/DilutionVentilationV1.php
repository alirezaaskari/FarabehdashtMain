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
 * دبی هوای لازم برای تهویه رقیق‌سازی در حالت پایا.
 *
 *     Q = K × G / C
 *
 * `G` نرخ تولید آلاینده، `C` غلظت هدف طراحی و `K` ضریب اختلاط (۱ برای اختلاط
 * کامل تا ۱۰ برای اختلاط ضعیف). غلظت هدف عمداً «حد مجاز» نام نگرفته: طراح
 * معمولاً کسری از حد را هدف می‌گیرد و این انتخاب با اوست.
 */
final readonly class DilutionVentilationV1 implements Formula
{
    private const float MILLIGRAMS_PER_GRAM = 1_000.0;

    public function id(): string
    {
        return 'dilution-ventilation';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'دبی لازم برای تهویه رقیق‌سازی';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'Industrial Ventilation: A Manual of Recommended Practice for Design — General industrial ventilation',
            publisher: 'کنفرانس دولتی متخصصان بهداشت صنعتی آمریکا (ACGIH)',
            year: 2019,
            relation: 'Q = K × G / C',
            note: 'حالت پایا و اختلاط یکنواخت؛ ضریب K ناکاملی اختلاط و جای منبع نسبت به کارگر را جبران می‌کند.',
        );
    }

    public function limitations(): array
    {
        return [
            'تهویه رقیق‌سازی برای آلاینده بسیار سمی، منبع بزرگ یا منبع نزدیک به منطقه تنفسی مناسب نیست؛ آن‌جا تهویه موضعی لازم است.',
            'نرخ تولید باید برآورد واقعی مصرف یا تبخیر باشد؛ خطای آن مستقیم به دبی منتقل می‌شود.',
            'انتخاب ضریب اختلاط و غلظت هدف قضاوت طراح است و عدد حاصل تأیید کفایت سامانه نیست.',
        ];
    }

    public function inputs(): array
    {
        return [
            'generation_rate' => InputDefinition::single('generation_rate', 'نرخ تولید آلاینده', Unit::GramPerHour, 0.0, 1_000_000.0),
            'target_concentration' => InputDefinition::single('target_concentration', 'غلظت هدف طراحی', Unit::MilligramPerCubicMetre, 0.001, 100_000.0),
            'mixing_factor' => InputDefinition::single('mixing_factor', 'ضریب اختلاط (K)', Unit::Ratio, 1.0, 10.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'required_airflow' => Unit::CubicMetrePerHour,
            'ideal_airflow' => Unit::CubicMetrePerHour,
        ];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $ideal = $inputs->value('generation_rate') * self::MILLIGRAMS_PER_GRAM / $inputs->value('target_concentration');

        return new Outcome(
            values: [
                'required_airflow' => $inputs->value('mixing_factor') * $ideal,
                'ideal_airflow' => $ideal,
            ],
        );
    }
}
