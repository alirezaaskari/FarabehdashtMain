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
 * تغییر تراز صدای منبع نقطه‌ای با فاصله، در میدان آزاد.
 *
 *     L₂ = L₁ − 20 × log₁₀(r₂ / r₁)
 *
 * یعنی شش دسی‌بل کاهش با هر دو برابر شدن فاصله. منبع خطی (مثل نوار نقاله
 * بلند) سه دسی‌بل کم می‌کند و عمداً در این نسخه نیست.
 */
final readonly class DistanceAttenuationV1 implements Formula
{
    public function id(): string
    {
        return 'noise-distance-attenuation';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'کاهش تراز صدا با فاصله (منبع نقطه‌ای)';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'ISO 9613-2 — Attenuation of sound during propagation outdoors: geometrical divergence',
            publisher: 'سازمان بین‌المللی استانداردسازی (ISO)',
            year: 1996,
            relation: 'L₂ = L₁ − 20·log₁₀(r₂/r₁)',
            note: 'فقط واگرایی هندسی منبع نقطه‌ای در میدان آزاد؛ جذب هوا، زمین و مانع جداگانه‌اند.',
        );
    }

    public function limitations(): array
    {
        return [
            'در فضای بسته، بازتاب دیوارها تراز دور از منبع را بیشتر از این عدد نگه می‌دارد (میدان واخنشی).',
            'در نزدیکی منبع (میدان نزدیک، کمتر از چند برابر بزرگ‌ترین بعد دستگاه) رابطه برقرار نیست.',
            'برای منبع خطی یا سطحی، این رابطه کاهش تراز را بیش از واقع نشان می‌دهد.',
        ];
    }

    public function inputs(): array
    {
        return [
            'level' => InputDefinition::single('level', 'تراز اندازه‌گیری‌شده', Unit::Decibel, 0.0, 180.0),
            'measured_distance' => InputDefinition::single('measured_distance', 'فاصله اندازه‌گیری', Unit::Metre, 0.1, 10_000.0),
            'target_distance' => InputDefinition::single('target_distance', 'فاصله مورد نظر', Unit::Metre, 0.1, 10_000.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'level_at_target' => Unit::Decibel,
            'change' => Unit::Decibel,
        ];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $change = -20.0 * log10($inputs->value('target_distance') / $inputs->value('measured_distance'));

        $notes = [];

        if ($change > 0.0) {
            $notes[] = 'فاصله مورد نظر از فاصله اندازه‌گیری کمتر است؛ تراز نزدیک‌تر به منبع برون‌یابی شده و در میدان نزدیک قابل اتکا نیست.';
        }

        return new Outcome(
            values: [
                'level_at_target' => $inputs->value('level') + $change,
                'change' => $change,
            ],
            notes: $notes,
        );
    }
}
