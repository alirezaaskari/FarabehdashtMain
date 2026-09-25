<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * تبدیل غلظت حجمی گاز یا بخار به غلظت جرمی.
 *
 *     mg/m³ = ppm × MW / Vm       و       Vm = 24.45 × (T / 298.15) × (101.325 / P)
 *
 * نسخه ۲ (DEC-19): حجم مولی از عدد مرسوم ۲۴٫۴۵ می‌آید تا نتیجه با جدول‌های
 * رایج یکی باشد. نسخه ۱ برای بازتولید محاسبه‌های ذخیره‌شده می‌ماند.
 *
 * دما و فشار ورودی اجباری‌اند، نه اختیاری: همین دو، عاملِ رایج‌ترین خطای
 * خاموش در گزارش‌های مواجهه‌اند.
 */
final readonly class PpmToMassConcentrationV2 implements Formula
{
    public function id(): string
    {
        return 'ppm-to-mass-concentration';
    }

    public function version(): string
    {
        return '2.0.0';
    }

    public function title(): string
    {
        return 'تبدیل ppm به میلی‌گرم بر مترمکعب';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'حجم مولی مرسوم ۲۴٫۴۵ و تعریف غلظت حجمی',
            publisher: 'OSHA Technical Manual و NIOSH Pocket Guide (عدد ۲۴٫۴۵ در ۲۵ درجه و ۱ اتمسفر)',
            year: 2017,
            relation: 'mg/m³ = ppm × MW / Vm ، Vm = 24.45 × (T / 298.15) × (101.325 / P)',
            note: 'حجم مولی مرسوم ۲۴٫۴۵ لیتر بر مول در ۲۵ درجه سلسیوس و ۱۰۱٫۳۲۵ کیلوپاسکال است و با قانون گازها به دما و فشار اندازه‌گیری برده می‌شود.',
        );
    }

    public function limitations(): array
    {
        return [
            'این تبدیل فقط برای گاز و بخار معنا دارد؛ برای گرد و غبار و دود فلزی و آئروسل ppm تعریف نمی‌شود.',
            'فرض رابطه رفتار گاز کامل است. در فشار بالا یا نزدیک نقطه شبنم، خطا بیشتر می‌شود.',
            'مبنا عدد مرسوم ۲۴٫۴۵ لیتر بر مول است تا نتیجه در ۲۵ درجه سلسیوس و ۱۰۱٫۳۲۵ کیلوپاسکال با جدول‌های رایج یکی باشد. قانون گاز کامل با ثابت CODATA در همان شرایط ۲۴٫۴۶۵ می‌دهد، یعنی حدود ۰٫۰۶ درصد اختلاف.',
            'وزن مولکولی و شرایط اندازه‌گیری را کاربر وارد می‌کند؛ درستی‌شان بررسی نمی‌شود.',
        ];
    }

    public function inputs(): array
    {
        return [
            'concentration' => InputDefinition::single(
                'concentration',
                'غلظت حجمی',
                Unit::PartsPerMillion,
                0.0,
                1_000_000.0,
            ),
            'molecular_weight' => InputDefinition::single(
                'molecular_weight',
                'وزن مولکولی',
                Unit::GramPerMole,
                1.0,
                1_000.0,
            ),
            'temperature' => InputDefinition::single(
                'temperature',
                'دمای هوا',
                Unit::Celsius,
                -50.0,
                100.0,
            ),
            'pressure' => InputDefinition::single(
                'pressure',
                'فشار هوا',
                Unit::Kilopascal,
                50.0,
                120.0,
            ),
        ];
    }

    public function outputs(): array
    {
        return [
            'concentration' => Unit::MilligramPerCubicMetre,
            'molar_volume' => Unit::LitrePerMole,
        ];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $molarVolume = MolarVolume::conventionalLitresPerMole(
            $inputs->value('temperature'),
            $inputs->value('pressure'),
        );

        return new Outcome(
            values: [
                'concentration' => $inputs->value('concentration') * $inputs->value('molecular_weight') / $molarVolume,
                'molar_volume' => $molarVolume,
            ],
            notes: [],
        );
    }
}
