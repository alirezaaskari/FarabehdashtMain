<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests\Fixtures;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * فرمول ساختگی برای تست رفتار خود موتور — نه یک رابطه واقعی.
 *
 * چون شناسه و نسخه‌اش از بیرون داده می‌شود، رفتار رجیستری با چند نسخه بدون
 * ساختن کلاس‌های الکی قابل تست است.
 */
final readonly class FakeFormula implements Formula
{
    public function __construct(
        private string $id = 'demo',
        private string $version = '1.0.0',
        private float $factor = 1.0,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function title(): string
    {
        return 'فرمول آزمایشی';
    }

    public function reference(): Reference
    {
        return new Reference('آزمایشی', 'فرابهداشت', 2026, 'y = k × x');
    }

    public function limitations(): array
    {
        return ['این فرمول فقط برای تست است.'];
    }

    public function inputs(): array
    {
        return [
            'x' => InputDefinition::single('x', 'ایکس', Unit::Celsius, -100.0, 100.0),
        ];
    }

    public function outputs(): array
    {
        return ['y' => Unit::Celsius];
    }

    public function compute(InputSet $inputs): Outcome
    {
        return new Outcome(['y' => $this->factor * $inputs->value('x')]);
    }
}
