<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Tools\Domain\BodyFigure;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Reports\CalculationReportSource;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Entitlement\OpenGate;
use App\Support\Reporting\ReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * پس از ارزیابی پوسچر: شکل بدن کنار هر گام، مقایسه ایستگاه‌ها و پیش و پس از
 * اصلاح، و بخش «ارزیابی ارگونومی» گزارش.
 */
final class ErgonomicsAssessmentTest extends TestCase
{
    use RefreshDatabase;

    /** مثال حل‌شده NTP 1173 (INSST): صندلی ۶، امتیاز نهایی ۶. */
    private const array INSST_EXAMPLE = [
        'chair_height' => '3', 'desk_no_leg_room' => '0', 'chair_height_fixed' => '1', 'seat_depth' => '1', 'seat_depth_fixed' => '0',
        'armrests' => '2', 'armrest_hard' => '0', 'armrest_wide' => '0', 'armrest_fixed' => '1',
        'backrest' => '2', 'desk_too_high' => '0', 'backrest_fixed' => '1', 'chair_duration' => '3',
        'monitor' => '1', 'monitor_far' => '0', 'monitor_neck_twist' => '0', 'monitor_glare' => '0', 'monitor_no_holder' => '1', 'monitor_duration' => '3',
        'phone' => '1', 'phone_neck_hold' => '0', 'phone_no_handsfree' => '0', 'phone_duration' => '2',
        'mouse' => '2', 'mouse_separate_surface' => '0', 'mouse_pinch' => '0', 'mouse_palmrest' => '1', 'mouse_duration' => '3',
        'keyboard' => '1', 'keyboard_deviation' => '0', 'keyboard_too_high' => '0', 'keyboard_overhead' => '0',
        'keyboard_platform_fixed' => '0', 'keyboard_duration' => '2',
    ];

    /** همان ایستگاه INSST پس از اصلاح صندلی: ارتفاع و دسته درست، دسته تنظیم‌شدنی. */
    private const array IMPROVED = [
        'chair_height' => '1', 'chair_height_fixed' => '0', 'armrests' => '1', 'armrest_fixed' => '0',
    ];

    public function test_every_posture_step_marks_body_parts_its_figure_can_draw(): void
    {
        $catalog = $this->app->make(ToolCatalog::class);

        foreach (['rula', 'reba', 'rosa'] as $slug) {
            $definition = $catalog->resolve($slug)->definition;

            foreach ($definition->steps as $step) {
                $this->assertNotEmpty($step['body'] ?? [], "{$slug}: {$step['title']}");
                $this->assertNotEmpty(BodyFigure::of($definition->bodyPose, $step['body'])->marked);
            }
        }

        $this->assertSame(BodyFigure::SEATED, $catalog->resolve('rosa')->definition->bodyPose);
        $this->get(route('tools.show', 'rosa'))->assertOk()->assertSee('class="tool-body"', false);
    }

    public function test_an_unknown_body_part_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BodyFigure::of(BodyFigure::STANDING, ['screen']);
    }

    public function test_a_saved_assessment_offers_its_peers_for_comparison(): void
    {
        $user = User::factory()->create();
        $before = $this->save($user, 'میز حسابداری ـ پیش از اصلاح');
        $after = $this->save($user, 'میز حسابداری ـ پس از اصلاح', self::IMPROVED);

        $this->actingAs($user)->get(route('tools.calculations.show', $after->uuid))
            ->assertOk()
            ->assertSee('مقایسه با ارزیابی‌های دیگر')
            ->assertSee('value="'.$before->uuid.'"', false)
            ->assertSee('مقایسه ارزیابی‌ها ویژه اشتراک حرفه‌ای است.');
    }

    public function test_comparing_is_for_subscribers(): void
    {
        $user = User::factory()->create();
        $before = $this->save($user, 'پیش');
        $after = $this->save($user, 'پس', self::IMPROVED);

        $this->actingAs($user)
            ->get(route('tools.calculations.compare', ['c' => [$before->uuid, $after->uuid]]))
            ->assertRedirectContains('compare_assessments');
    }

    public function test_before_and_after_shows_the_change_and_what_was_fixed(): void
    {
        $this->app->instance(EntitlementGate::class, new OpenGate);

        $user = User::factory()->create();
        $before = $this->save($user, 'میز حسابداری ـ پیش از اصلاح');
        $after = $this->save($user, 'میز حسابداری ـ پس از اصلاح', self::IMPROVED);

        $this->assertGreaterThan($after->outputs['rosa_score']['value'], $before->outputs['rosa_score']['value']);

        $this->actingAs($user)
            ->get(route('tools.calculations.compare', ['c' => [$after->uuid, $before->uuid]]))
            ->assertOk()
            ->assertSeeInOrder(['میز حسابداری ـ پیش از اصلاح', 'میز حسابداری ـ پس از اصلاح', 'تغییر'])
            ->assertSee('امتیاز نهایی ROSA')
            ->assertSee('بهتر')
            ->assertSee('پاسخ‌هایی که فرق دارد')
            ->assertSee('خیلی بلند: زاویه زانو بیش از ۹۰ درجه')
            ->assertSee('زانو حدود ۹۰ درجه، کف پا روی زمین')
            ->assertDontSee('کار با کاغذ بدون نگه‌دارنده سند', false);
    }

    public function test_only_the_users_own_assessments_of_one_method_are_compared(): void
    {
        $this->app->instance(EntitlementGate::class, new OpenGate);

        $user = User::factory()->create();
        $mine = $this->save($user, 'من');
        $theirs = $this->save(User::factory()->create(), 'دیگری');

        $this->actingAs($user)
            ->get(route('tools.calculations.compare', ['c' => [$mine->uuid, $theirs->uuid]]))
            ->assertOk()
            ->assertSee('مقایسه ممکن نشد')
            ->assertDontSee('دیگری');
    }

    public function test_the_report_explains_each_assessment_beside_its_scores(): void
    {
        $user = User::factory()->create();
        $saved = $this->save($user, 'میز حسابداری');

        $data = $this->app->make(CalculationReportSource::class)->load((int) $user->getKey(), [$saved->uuid]);

        $this->assertInstanceOf(ReportData::class, $data);
        $this->assertNotEmpty($data->measurements, 'امتیازها همچنان سطر جدول نتایج‌اند.');
        $this->assertCount(1, $data->assessments);

        $assessment = $data->assessments[0];
        $answers = array_column($assessment->answers, 'value', 'label');

        $this->assertSame('میز حسابداری', $assessment->point);
        $this->assertStringContainsString('صندلی', implode(' ', $assessment->notes));
        $this->assertSame('خیلی بلند: زاویه زانو بیش از ۹۰ درجه', $answers['ارتفاع صندلی']);
        $this->assertSame('بله', $answers['دسته تنظیم‌شدنی نیست']);
        $this->assertArrayNotHasKey('بازتاب نور روی صفحه', $answers, 'کلید خاموش در گزارش نمی‌آید.');
        $this->assertEquals($data, ReportData::fromArray(json_decode((string) json_encode($data->toArray()), true)));
    }

    /**
     * @param  array<string, string>  $changes
     */
    private function save(User $user, string $label, array $changes = []): SavedCalculation
    {
        $this->actingAs($user)
            ->post(route('tools.calculations.store', 'rosa'), [...self::INSST_EXAMPLE, ...$changes, 'label' => $label])
            ->assertRedirect();

        return SavedCalculation::query()->latest('id')->firstOrFail();
    }
}
