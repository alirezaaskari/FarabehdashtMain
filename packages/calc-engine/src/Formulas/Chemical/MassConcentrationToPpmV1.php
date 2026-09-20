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
 *     ppm = mg/m³ × Vm / MW       و       Vm = R × T / P
 *
 * عمداً فرمول جداگانه است و نه یک «حالت» روی فرمول رفت: ورودی و خروجی‌اش
 * واحد دیگری دارند و باید مستقل نسخه بگیرد.
 */
final readonly class MassConcentrationToPpmV1 implements Formula
{
    public function id(): string
    {
        return 'mass-concentration-to-ppm';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'تبدیل میلی‌گرم بر مترمکعب به ppm';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'قانون گاز کامل و تعریف غلظت حجمی',
            publisher: 'CODATA 2018 برای ثابت جهانی گازها',
            year: 2018,
            relation: 'ppm = mg/m³ × Vm / MW ، Vm = R × T / P',
            note: 'رابطه از تعریف ppm به‌عنوان نسبت حجمی و از قانون گاز کامل به‌دست می‌آید. R = 8.314462618 L·kPa/(mol·K).',
        );
    }

    public function limitations(): array
    {
        return [
            'این تبدیل فقط برای گاز و بخار معنا دارد؛ برای گرد و غبار و دود فلزی و آئروسل ppm تعریف نمی‌شود.',
            'فرض رابطه رفتار گاز کامل است. در فشار بالا یا نزدیک نقطه شبنم، خطا بیشتر می‌شود.',
            'مرجع‌های قدیمی حجم مولی ثابت ۲۴٫۴۵ لیتر بر مول را در ۲۵ درجه سلسیوس و ۱۰۱٫۳۲۵ کیلوپاسکال به‌کار می‌برند. این فرمول حجم مولی را از قانون گاز کامل حساب می‌کند (۲۴٫۴۶۵ در همان شرایط)، پس نتیجه حدود ۰٫۰۶ درصد با آن جدول‌ها فرق می‌کند.',
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
        $molarVolume = MolarVolume::litresPerMole(
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
