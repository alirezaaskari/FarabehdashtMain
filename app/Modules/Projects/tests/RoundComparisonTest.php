<?php

declare(strict_types=1);

namespace App\Modules\Projects\Tests;

use App\Models\User;
use App\Modules\Projects\Actions\CreateProject;
use App\Modules\Projects\Actions\RecordReading;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Domain\ProjectRound;
use App\Modules\Projects\Domain\RoundComparison;
use App\Modules\Projects\Services\ComparisonChart;
use App\Modules\Projects\Services\RoundComparer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۸، نیمه دوم:
 * «مقایسه دو دور جدول و نمودار هم‌زمان می‌دهد.»
 *
 * هر دو از یک شیء `RoundComparison` ساخته می‌شوند تا هیچ‌وقت دو چیز متفاوت
 * نگویند — تستِ آخر همین را می‌سنجد.
 */
final class RoundComparisonTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = $this->app->make(CreateProject::class)->handle($this->user, 'پایش صدا');

        foreach (['ایستگاه الف', 'ایستگاه ب'] as $index => $title) {
            $this->project->stations()->create(['title' => $title, 'sort_order' => $index]);
        }
    }

    private function record(int $roundIndex, int $stationIndex, float $value, string $unit = 'dB'): void
    {
        $this->app->make(RecordReading::class)->manual(
            project: $this->project,
            round: $this->project->rounds()->get()[$roundIndex],
            station: $this->project->stations()->get()[$stationIndex],
            value: $value,
            unit: $unit,
        );
    }

    private function compare(): RoundComparison
    {
        $rounds = $this->project->rounds()->get();

        return $this->app->make(RoundComparer::class)->compare($this->project, $rounds[0], $rounds[1]);
    }

    public function test_the_table_reports_the_change_per_station(): void
    {
        $this->record(0, 0, 92.0);
        $this->record(1, 0, 85.0);
        $this->record(0, 1, 88.0);
        $this->record(1, 1, 86.0);

        $comparison = $this->compare();

        $this->assertCount(2, $comparison->rows);
        $this->assertEqualsWithDelta(-7.0, $comparison->rows[0]->change(), 1e-9);
        $this->assertEqualsWithDelta(-2.0, $comparison->rows[1]->change(), 1e-9);
        $this->assertEqualsWithDelta(-4.5, $comparison->averageChange(), 1e-9);
    }

    public function test_percent_change_is_relative_to_the_first_round(): void
    {
        $this->record(0, 0, 100.0);
        $this->record(1, 0, 75.0);

        $this->assertEqualsWithDelta(-25.0, $this->compare()->rows[0]->percentChange(), 1e-9);
    }

    public function test_direction_is_reported_without_judging_it(): void
    {
        // کاهش برای صدا مطلوب است و برای روشنایی نه؛ پس «بهتر» و «بدتر»
        // این‌جا تصمیم‌گیری نمی‌شود.
        $this->record(0, 0, 90.0);
        $this->record(1, 0, 80.0);
        $this->record(0, 1, 80.0);
        $this->record(1, 1, 90.0);

        $rows = $this->compare()->rows;

        $this->assertSame('decreased', $rows[0]->direction());
        $this->assertSame('increased', $rows[1]->direction());
    }

    public function test_a_station_missing_from_one_round_is_not_compared(): void
    {
        $this->record(0, 0, 92.0);
        $this->record(1, 0, 85.0);
        $this->record(0, 1, 88.0);

        $comparison = $this->compare();

        $this->assertTrue($comparison->hasGaps());
        $this->assertCount(1, $comparison->comparableRows());
        $this->assertNull($comparison->rows[1]->change());
    }

    public function test_two_rounds_with_different_units_are_refused(): void
    {
        // مقایسه دسی‌بل با لوکس عددی ممکن است و کاملاً بی‌معنا؛ نمودارش هم
        // خوش‌شکل درمی‌آید و همین خطرناکش می‌کند.
        $this->record(0, 0, 90.0, 'dB');
        $this->record(1, 0, 450.0, 'lx');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('واحدهای متفاوتی دارند');

        $this->compare();
    }

    public function test_a_round_cannot_be_compared_with_itself(): void
    {
        $rounds = $this->project->rounds()->get();

        $this->expectException(RuntimeException::class);

        $this->app->make(RoundComparer::class)->compare($this->project, $rounds[0], $rounds[0]);
    }

    public function test_a_round_from_another_project_is_refused(): void
    {
        $other = $this->app->make(CreateProject::class)->handle($this->user, 'پروژه دیگر');

        $this->expectException(RuntimeException::class);

        $this->app->make(RoundComparer::class)->compare(
            $this->project,
            $this->project->rounds()->first(),
            $other->rounds()->first(),
        );
    }

    public function test_the_chart_and_the_table_describe_the_same_numbers(): void
    {
        // هر دو از یک شیء ساخته می‌شوند؛ این تست همان را قفل می‌کند.
        $this->record(0, 0, 92.0);
        $this->record(1, 0, 85.0);
        $this->record(0, 1, 88.0);
        $this->record(1, 1, 86.0);

        $comparison = $this->compare();
        $bars = $this->app->make(ComparisonChart::class)->bars($comparison);

        $this->assertCount(4, $bars);

        $fromChart = [];

        foreach ($bars as $bar) {
            $fromChart[$bar->station][$bar->series] = $bar->value;
        }

        foreach ($comparison->rows as $row) {
            $this->assertEqualsWithDelta($row->before, $fromChart[$row->station][1], 1e-9);
            $this->assertEqualsWithDelta($row->after, $fromChart[$row->station][2], 1e-9);
        }
    }

    public function test_bar_height_is_proportional_to_value_on_a_shared_scale(): void
    {
        // مقیاس از بیشینه هر دو دور می‌آید؛ وگرنه دو میله با ارتفاع یکسان
        // دو عدد متفاوت را نشان می‌دادند.
        $this->record(0, 0, 100.0);
        $this->record(1, 0, 50.0);

        $bars = $this->app->make(ComparisonChart::class)->bars($this->compare());

        $tall = array_values(array_filter($bars, static fn ($b): bool => $b->series === 1))[0];
        $short = array_values(array_filter($bars, static fn ($b): bool => $b->series === 2))[0];

        $this->assertEqualsWithDelta(0.5, $short->height / $tall->height, 1e-9);
    }

    public function test_bars_of_a_pair_never_touch(): void
    {
        // فاصله دو پیکسلی، کدگذاری ثانویه‌ای است که مرز دو میله را بدون
        // اتکا به رنگ منتقل می‌کند.
        $this->record(0, 0, 92.0);
        $this->record(1, 0, 85.0);

        $bars = $this->app->make(ComparisonChart::class)->bars($this->compare());

        $first = $bars[0];
        $second = $bars[1];

        $this->assertGreaterThanOrEqual(2.0, $second->x - ($first->x + $first->width));
    }

    public function test_the_comparison_page_shows_both_the_chart_and_the_table(): void
    {
        $this->record(0, 0, 92.0);
        $this->record(1, 0, 85.0);

        $rounds = $this->project->rounds()->get();

        $this->actingAs($this->user)
            ->get(route('projects.compare', $this->project->uuid).'?before='.$rounds[0]->id.'&after='.$rounds[1]->id)
            ->assertOk()
            ->assertSee('<svg', escape: false)
            ->assertSee('جدول عددی')
            ->assertSee('92')
            ->assertSee('85')
            ->assertSee('تفسیر کفایت اقدام کنترلی');
    }

    public function test_the_chart_carries_a_legend_and_direct_labels(): void
    {
        // هویت هرگز فقط با رنگ منتقل نمی‌شود.
        $this->record(0, 0, 92.0);
        $this->record(1, 0, 85.0);

        $rounds = $this->project->rounds()->get();

        $this->actingAs($this->user)
            ->get(route('projects.compare', $this->project->uuid).'?before='.$rounds[0]->id.'&after='.$rounds[1]->id)
            ->assertOk()
            ->assertSee('دور اول')
            ->assertSee('دور دوم')
            ->assertSee('bg-chart-1', escape: false)
            ->assertSee('fill-chart-2', escape: false);
    }

    public function test_another_users_project_is_a_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('projects.compare', $this->project->uuid))
            ->assertNotFound();
    }

    public function test_an_empty_comparison_does_not_divide_by_zero(): void
    {
        $comparison = $this->compare();

        $this->assertEqualsWithDelta(1.0, $comparison->scaleMax(), 1e-9);
        $this->assertNull($comparison->averageChange());
        $this->assertSame([], $this->app->make(ComparisonChart::class)->bars($comparison));
    }

    public function test_rounds_are_ordered_and_belong_to_the_project(): void
    {
        $rounds = $this->project->rounds()->get();

        $this->assertCount(2, $rounds);
        $this->assertInstanceOf(ProjectRound::class, $rounds[0]);
        $this->assertSame('دور اول', $rounds[0]->title);
        $this->assertSame('دور دوم', $rounds[1]->title);
    }
}
