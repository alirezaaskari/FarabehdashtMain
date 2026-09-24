<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Reports\CalculationReportSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * محاسبه‌های ذخیره‌شده به‌عنوان منبع گزارش، با نسخه فرمول لحظه ثبت.
 */
final class CalculationReportSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_output_becomes_a_row_with_its_formula_version(): void
    {
        $user = User::factory()->create();
        $calculation = $this->calculation($user);

        $data = $this->source()->load($user->id, [$calculation->uuid]);

        $this->assertNotNull($data);
        $this->assertSame('اتاق کنترل', $data->measurements[0]->point);
        $this->assertSame('wbgt-indoor v1', $data->measurements[0]->formula);
        $this->assertSame(['wbgt-indoor v1'], $data->formulas());
        $this->assertSame([], $data->equipment);
    }

    public function test_another_users_calculation_is_never_loaded(): void
    {
        $calculation = $this->calculation(User::factory()->create());
        $stranger = User::factory()->create();

        $this->assertNull($this->source()->load($stranger->id, [$calculation->uuid]));
        $this->assertSame([], $this->source()->options($stranger->id));
    }

    private function calculation(User $user): SavedCalculation
    {
        return SavedCalculation::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $user->id,
            'tool_slug' => 'wbgt-indoor',
            'formula_id' => 'wbgt-indoor',
            'formula_version' => '1',
            'label' => 'اتاق کنترل',
            'inputs' => [],
            'outputs' => ['wbgt' => ['value' => 27.84, 'unit' => 'celsius']],
            'notes' => [],
        ]);
    }

    private function source(): CalculationReportSource
    {
        return $this->app->make(CalculationReportSource::class);
    }
}
