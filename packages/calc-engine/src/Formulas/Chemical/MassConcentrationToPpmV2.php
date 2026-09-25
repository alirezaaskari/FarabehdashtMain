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
 * تبدیل غلظت جرمی گاز یا بخار به غلظت حجمی — وارون تبدیل ppm به mg/m³.
 *
 *     ppm = mg/m³ × Vm / MW       و       Vm = 24.45 × (T / 298.15) × (101.325 / P)
 *
 * نسخه ۲ (DEC-19): حجم مولی از عدد مرسوم ۲۴٫۴۵ می‌آید تا نتیجه با جدول‌های
 * رایج یکی باشد. نسخه ۱ برای بازتولید محاسبه‌های ذخیره‌شده می‌ماند.
 *
 * عمداً فرمول جداگانه است و نه یک «حالت» روی فرمول رفت: ورودی و خروجی‌اش
 * واحد دیگری دارند و باید مستقل نسخه بگیرد.
 */
final readonly class MassConcentrationToPpmV2 implements Formula
{
    public function id(): string
    {
        return 'mass-concentration-to-ppm';
    }

    public function version(): string
    {
        return '2.0.0';
    }

    public function title(): string
    {
        return 'تبدیل میلی‌گرم بر مترمکعب به ppm';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'حجم مولی مرسوم ۲۴٫۴۵ و تعریف غلظت حجمی',
            publisher: 'OSHA Technical Manual و NIOSH Pocket Guide (عدد ۲۴٫۴۵ در ۲۵ درجه و ۱ اتمسفر)',
            year: 2017,
            relation: 'ppm = mg/m³ × Vm / MW ، Vm = 24.45 × (T / 298.15) × (101.325 / P)',
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
                'غلظت جرمی',
                Unit::MilligramPerCubicMetre,
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
            'concentration' => Unit::PartsPerMillion,
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
                'concentration' => $inputs->value('concentration') * $molarVolume / $inputs->value('molecular_weight'),
                'molar_volume' => $molarVolume,
            ],
            notes: [],
        );
    }
}
