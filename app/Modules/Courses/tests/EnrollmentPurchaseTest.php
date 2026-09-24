<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Courses\Services\Payments\FakePaymentGateway;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Support\Ledger\EntryDirection;
use App\Support\Payments\PaymentGatewayUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\UnavailablePaymentGateway;
use Tests\TestCase;

/**
 * جریان خرید دوره — نیمه «هویت مالی مشترک مدرس و فروشنده» معیار پذیرش بخش ۱۳.
 */
final class EnrollmentPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    private function publishedCourse(int $instructorUserId, int $priceToman = 300_000): Course
    {
        return Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $instructorUserId,
            'slug' => 'c-'.Str::random(8),
            'title' => 'دوره ایمنی',
            'price_toman' => $priceToman,
            'status' => CourseStatus::Published,
        ])->refresh();
    }

    public function test_a_full_enrollment_payment_records_a_balanced_ledger_transaction(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = $this->publishedCourse($instructor->id, 300_000);

        $this->actingAs($student)->post(route('courses.enroll', $course))->assertRedirect();

        $enrollment = Enrollment::query()->where('course_id', $course->id)->sole();
        $this->assertSame(EnrollmentStatus::Pending, $enrollment->status);

        $this->get(route('courses.callback', [
            'Authority' => $enrollment->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        $enrollment->refresh();
        $this->assertSame(EnrollmentStatus::Paid, $enrollment->status);
        $this->assertNotNull($enrollment->gateway_ref_id);

        $transaction = LedgerTransaction::query()->where('reference_id', $enrollment->uuid)->sole();
        $entries = LedgerEntry::query()->where('transaction_id', $transaction->id)->get();

        $net = $entries->sum(fn (LedgerEntry $e): int => $e->amount_toman * $e->direction->sign());
        $this->assertSame(0, $net);

        $credits = $entries->where('direction', EntryDirection::Credit)->sum('amount_toman');
        $this->assertSame(300_000, $credits);
    }

    public function test_a_student_cannot_enroll_twice(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = $this->publishedCourse($instructor->id);

        $this->actingAs($student)->post(route('courses.enroll', $course))->assertRedirect();
        $this->actingAs($student)->post(route('courses.enroll', $course))->assertRedirect();

        $this->assertSame(1, Enrollment::query()->where('course_id', $course->id)->count());
    }

    public function test_a_cancelled_payment_leaves_the_enrollment_failed_with_no_ledger_effect(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = $this->publishedCourse($instructor->id);

        $this->actingAs($student)->post(route('courses.enroll', $course))->assertRedirect();
        $enrollment = Enrollment::query()->where('course_id', $course->id)->sole();

        $this->get(route('courses.callback', [
            'Authority' => $enrollment->gateway_authority,
            'Status' => 'NOK',
        ]))->assertOk();

        $this->assertSame(EnrollmentStatus::Failed, $enrollment->refresh()->status);
        $this->assertSame(0, LedgerTransaction::query()->where('reference_id', $enrollment->uuid)->count());
    }

    public function test_replaying_the_callback_does_not_record_the_ledger_twice(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = $this->publishedCourse($instructor->id);

        $this->actingAs($student)->post(route('courses.enroll', $course))->assertRedirect();
        $enrollment = Enrollment::query()->where('course_id', $course->id)->sole();

        $params = ['Authority' => $enrollment->gateway_authority, 'Status' => 'OK'];
        $this->get(route('courses.callback', $params))->assertOk();
        $this->get(route('courses.callback', $params))->assertOk();

        $this->assertSame(1, LedgerTransaction::query()->where('reference_id', $enrollment->uuid)->count());
    }

    public function test_an_unpublished_course_cannot_be_enrolled_in(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $instructor->id,
            'slug' => 'draft-course',
            'title' => 'دوره پیش‌نویس',
            'price_toman' => 10_000,
            'status' => CourseStatus::Draft,
        ]);

        $this->actingAs($student)->post(route('courses.enroll', $course))->assertRedirect();

        $this->assertSame(0, Enrollment::query()->where('course_id', $course->id)->count());
    }

    public function test_an_unreachable_gateway_shows_a_failure_page_instead_of_an_error(): void
    {
        $this->app->instance(PaymentGateway::class, new UnavailablePaymentGateway);
        $course = $this->publishedCourse(User::factory()->create()->id);

        $this->actingAs(User::factory()->create())
            ->post(route('courses.enroll', $course))
            ->assertOk()
            ->assertSee(PaymentGatewayUnavailable::USER_MESSAGE);

        $this->assertSame(EnrollmentStatus::Failed, Enrollment::query()->where('course_id', $course->id)->sole()->status);
    }
}
