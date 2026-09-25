<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Contracts\PaymentGateway;
use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\Courses\Actions\PublishCourse;
use App\Modules\Courses\Actions\SubmitCourseForReview;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Courses\Services\Payments\FakePaymentGateway;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Support\Ledger\AccountType;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * پرداخت دوره از کیف پول (DEC-37) و دوره رایگان (DEC-38).
 */
final class WalletAndFreeEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
    }

    private function course(int $priceToman, CourseStatus $status = CourseStatus::Published): Course
    {
        return Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'c-'.Str::random(8),
            'title' => 'دوره ایمنی',
            'price_toman' => $priceToman,
            'status' => $status,
        ])->refresh();
    }

    private function balanceOf(User $user): int
    {
        return $this->app->make(WalletStatementReader::class)->balanceOf($user->id)->toman;
    }

    public function test_a_course_paid_from_the_wallet_debits_the_wallet_instead_of_the_treasury(): void
    {
        $student = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($student->id, Money::toman(500_000), null);
        $course = $this->course(300_000);

        $this->actingAs($student)->post(route('courses.enroll', $course), ['payment' => 'wallet'])
            ->assertOk()
            ->assertViewIs('courses::checkout-success');

        $enrollment = Enrollment::query()->where('course_id', $course->id)->sole();
        $this->assertSame(EnrollmentStatus::Paid, $enrollment->status);
        $this->assertSame(PaymentSource::Wallet, $enrollment->payment_source);
        $this->assertNull($enrollment->gateway_authority);
        $this->assertSame(200_000, $this->balanceOf($student));

        $entries = LedgerEntry::query()
            ->where('transaction_id', LedgerTransaction::query()->where('reference_id', $enrollment->uuid)->sole()->id)
            ->with('account')
            ->get();

        $this->assertSame(0, $entries->sum(fn (LedgerEntry $e): int => $e->amount_toman * $e->direction->sign()));
        $this->assertNotContains(AccountType::Treasury, $entries->map(fn (LedgerEntry $e): AccountType => $e->account->type)->all());
    }

    public function test_an_insufficient_wallet_changes_nothing(): void
    {
        $student = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($student->id, Money::toman(100_000), null);
        $course = $this->course(300_000);

        $this->actingAs($student)
            ->from(route('courses.show', $course))
            ->post(route('courses.enroll', $course), ['payment' => 'wallet'])
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHasErrors('payment');

        $this->assertSame(100_000, $this->balanceOf($student));
        $this->assertSame(EnrollmentStatus::Failed, Enrollment::query()->where('course_id', $course->id)->sole()->status);
        $this->assertSame(0, LedgerTransaction::query()->where('kind', 'courses.enrollment_paid')->count());
    }

    public function test_free_is_not_a_payment_method_the_form_can_ask_for(): void
    {
        $student = User::factory()->create();
        $course = $this->course(300_000);

        $this->actingAs($student)->post(route('courses.enroll', $course), ['payment' => 'free'])
            ->assertSessionHasErrors('payment');

        $this->assertSame(0, Enrollment::query()->count());
    }

    public function test_a_free_course_enrolls_at_once_without_any_ledger_transaction(): void
    {
        $student = User::factory()->create();
        $course = $this->course(0);

        $this->actingAs($student)->post(route('courses.enroll', $course))
            ->assertOk()
            ->assertViewIs('courses::checkout-success');

        $enrollment = Enrollment::query()->where('course_id', $course->id)->sole();
        $this->assertSame(EnrollmentStatus::Paid, $enrollment->status);
        $this->assertSame(PaymentSource::Free, $enrollment->payment_source);
        $this->assertSame(0, LedgerTransaction::query()->count());
    }

    public function test_a_free_course_still_goes_through_admin_review(): void
    {
        $course = $this->course(0, CourseStatus::Draft);
        CourseSession::query()->create([
            'course_id' => $course->id,
            'title' => 'جلسه اول',
            'position' => 1,
        ]);

        $submitted = $this->app->make(SubmitCourseForReview::class)->handle($course->refresh());
        $this->assertSame(CourseStatus::InReview, $submitted->status);

        $published = $this->app->make(PublishCourse::class)->handle($submitted, User::factory()->create()->id);
        $this->assertSame(CourseStatus::Published, $published->status);
    }

    public function test_the_course_page_says_free_instead_of_zero_toman(): void
    {
        $course = $this->course(0);

        $this->actingAs(User::factory()->create())
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('ثبت‌نام رایگان')
            ->assertDontSee('روش پرداخت');
    }

    public function test_the_course_page_offers_the_wallet_when_it_covers_the_price(): void
    {
        $student = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($student->id, Money::toman(500_000), null);

        $this->actingAs($student)
            ->get(route('courses.show', $this->course(300_000)))
            ->assertOk()
            ->assertSee('روش پرداخت')
            ->assertSee('name="payment" value="wallet"', false)
            ->assertDontSee('کافی نیست');
    }
}
