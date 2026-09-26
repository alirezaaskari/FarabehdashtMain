<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Contracts\PaymentGateway;
use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Courses\Actions\RefundEnrollment;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Courses\Filament\Pages\EnrollmentRefundPage;
use App\Modules\Courses\Services\Payments\FakePaymentGateway;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Support\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/** بازگشت وجه ثبت‌نام دوره (بخش ۱۸-۱۱). */
final class EnrollmentRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
    }

    public function test_a_refund_returns_the_full_price_to_the_wallet_and_closes_access(): void
    {
        [$student, $course, $enrollment] = $this->paidFromWallet();
        $this->assertSame(200_000, $this->balanceOf($student));

        $this->app->make(RefundEnrollment::class)->handle($enrollment, $this->admin()->id, 'انصراف');

        $enrollment->refresh();
        $this->assertSame(EnrollmentStatus::Refunded, $enrollment->status);
        $this->assertSame('انصراف', $enrollment->refund_reason);
        $this->assertSame(500_000, $this->balanceOf($student));

        $refund = LedgerTransaction::query()->where('kind', 'courses.enrollment_refunded')->sole();
        $entries = LedgerEntry::query()->where('transaction_id', $refund->id)->get();
        $this->assertSame(0, $entries->sum(fn (LedgerEntry $e): int => $e->amount_toman * $e->direction->sign()));

        $this->actingAs($student)->get(route('courses.learn', $course))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'courses.enrollment_refunded', 'subject_id' => $enrollment->uuid]);
    }

    public function test_a_refund_happens_once(): void
    {
        [, , $enrollment] = $this->paidFromWallet();
        $refund = $this->app->make(RefundEnrollment::class);
        $refund->handle($enrollment, $this->admin()->id);

        $this->expectException(InvalidArgumentException::class);
        $refund->handle($enrollment, $this->admin()->id);
    }

    public function test_a_free_enrollment_has_nothing_to_refund(): void
    {
        $student = User::factory()->create();
        $course = $this->course(0);
        $this->actingAs($student)->post(route('courses.enroll', $course));
        $enrollment = Enrollment::query()->sole();
        $this->assertSame(EnrollmentStatus::Paid, $enrollment->status);

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(RefundEnrollment::class)->handle($enrollment, $this->admin()->id);
    }

    public function test_buying_again_after_a_refund_is_a_new_payment(): void
    {
        [$student, $course, $enrollment] = $this->paidFromWallet();
        $oldUuid = $enrollment->uuid;
        $this->app->make(RefundEnrollment::class)->handle($enrollment, $this->admin()->id);

        $this->actingAs($student)->post(route('courses.enroll', $course), ['payment' => 'wallet'])->assertOk();

        $enrollment->refresh();
        $this->assertSame(EnrollmentStatus::Paid, $enrollment->status);
        $this->assertNotSame($oldUuid, $enrollment->uuid);
        $this->assertNull($enrollment->refunded_at);
        $this->assertSame(200_000, $this->balanceOf($student));
        $this->assertSame(2, LedgerTransaction::query()->where('kind', 'courses.enrollment_paid')->count());
        $this->actingAs($student)->get(route('courses.learn', $course))->assertOk();
    }

    public function test_the_admin_finds_by_mobile_and_confirms_in_two_steps(): void
    {
        [$student, , $enrollment] = $this->paidFromWallet();
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        Livewire::actingAs($this->admin())->test(EnrollmentRefundPage::class)
            ->set('query', $student->mobile)
            ->call('find')
            ->assertSee('دوره ایمنی')
            ->assertSee('تومان')
            ->call('confirm', $enrollment->id)
            ->call('ask', $enrollment->id)
            ->set("reasons.{$enrollment->id}", 'خطای خرید')
            ->call('confirm', $enrollment->id)
            ->assertNotified('وجه دوره به کیف پول دانشجو برگشت')
            ->assertSee('وجه برگشت داده شد');

        $this->assertSame('خطای خرید', $enrollment->refresh()->refund_reason);
    }

    public function test_only_finance_admins_open_the_page(): void
    {
        $this->actingAs(User::factory()->create());
        $this->assertFalse(EnrollmentRefundPage::canAccess());

        $finance = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($finance, AdminRole::Finance);
        $this->actingAs($finance->fresh() ?? $finance);
        $this->assertTrue(EnrollmentRefundPage::canAccess());
    }

    /** @return array{User, Course, Enrollment} */
    private function paidFromWallet(): array
    {
        $student = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($student->id, Money::toman(500_000), null);
        $course = $this->course(300_000);

        $this->actingAs($student)->post(route('courses.enroll', $course), ['payment' => 'wallet'])->assertOk();

        return [$student, $course, Enrollment::query()->where('course_id', $course->id)->sole()];
    }

    private function course(int $priceToman): Course
    {
        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'c-'.Str::lower(Str::random(8)),
            'title' => 'دوره ایمنی',
            'price_toman' => $priceToman,
            'status' => CourseStatus::Published,
        ]);

        CourseSession::query()->create([
            'course_id' => $course->id,
            'title' => 'جلسه یک',
            'content_type' => 'text',
            'content' => 'متن',
            'position' => 1,
        ]);

        return $course->refresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);

        return $admin->fresh() ?? $admin;
    }

    private function balanceOf(User $user): int
    {
        return $this->app->make(WalletStatementReader::class)->balanceOf($user->id)->toman;
    }
}
