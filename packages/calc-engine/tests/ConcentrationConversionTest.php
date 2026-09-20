<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use PHPUnit\Framework\TestCase;

/**
 * تبدیل ppm و mg/m³ — و اثر دما و فشار که رایج‌ترین جای خطای خاموش است.
 */
final class ConcentrationConversionTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = Engine::withDefaultFormulas();
    }

    public function test_the_two_directions_are_exact_inverses(): void
    {
        $conditions = ['molecular_weight' => 92.14, 'temperature' => 25.0, 'pressure' => 101.325];

        $forward = $this->engine->run('ppm-to-mass-concentration', [...$conditions, 'concentration' => 50.0]);

        $back = $this->engine->run('mass-concentration-to-ppm', [
            ...$conditions,
            'concentration' => $forward->output('concentration')->value,
        ]);

        $this->assertEqualsWithDelta(50.0, $back->output('concentration')->value, 1e-9);
    }

    public function test_warmer_air_means_a_lower_mass_concentration_for_the_same_ppm(): void
    {
        // هوای گرم‌تر رقیق‌تر است: همان نسبت حجمی، جرم کمتری در مترمکعب.
        $shared = ['concentration' => 50.0, 'molecular_weight' => 92.14, 'pressure' => 101.325];

        $cold = $this->engine->run('ppm-to-mass-concentration', [...$shared, 'temperature' => 0.0]);
        $warm = $this->engine->run('ppm-to-mass-concentration', [...$shared, 'temperature' => 40.0]);

        $this->assertGreaterThan(
            $warm->output('concentration')->value,
            $cold->output('concentration')->value,
        );
    }

    public function test_lower_pressure_means_a_lower_mass_concentration(): void
    {
        $shared = ['concentration' => 50.0, 'molecular_weight' => 92.14, 'temperature' => 25.0];

        $sea = $this->engine->run('ppm-to-mass-concentration', [...$shared, 'pressure' => 101.325]);
        $altitude = $this->engine->run('ppm-to-mass-concentration', [...$shared, 'pressure' => 85.0]);

        $this->assertGreaterThan(
            $altitude->output('concentration')->value,
            $sea->output('concentration')->value,
        );
    }

    public function test_the_molar_volume_is_reported_alongside_the_result(): void
    {
        // بدون دیدن حجم مولی، خواننده گزارش نمی‌تواند عدد را بازبینی کند.
        $calculation = $this->engine->run('ppm-to-mass-concentration', [
            'concentration' => 50.0,
            'molecular_weight' => 92.14,
            'temperature' => 25.0,
            'pressure' => 101.325,
        ]);

        $this->assertEqualsWithDelta(24.4654036966, $calculation->output('molar_volume')->value, 1e-9);
    }

    public function test_the_difference_from_the_legacy_constant_is_disclosed(): void
    {
        // قاعده محصولی: اختلاف با جدول‌های مرسوم باید گفته شود، نه پنهان بماند.
        $calculation = $this->engine->run('ppm-to-mass-concentration', [
            'concentration' => 50.0,
            'molecular_weight' => 92.14,
            'temperature' => 25.0,
            'pressure' => 101.325,
        ]);

        $this->assertStringContainsString(
            '۲۴٫۴۵',
            implode(' ', $calculation->limitations),
        );
    }
}
