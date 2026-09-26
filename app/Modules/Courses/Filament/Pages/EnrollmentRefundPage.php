<?php

declare(strict_types=1);

namespace App\Modules\Courses\Filament\Pages;

use App\Models\User;
use App\Modules\Courses\Actions\RefundEnrollment;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Support\Admin\NavigationGroup;
use App\Support\Mobile;
use App\Support\Payments\PaymentSource;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * بازگشت وجه دوره (بخش ۱۸-۱۱) — هم‌تای «بازگشت وجه» فروشگاه.
 *
 * جست‌وجو با شناسه ثبت‌نام یا موبایل دانشجو؛ هر ردیف اثر مالی را پیش از اجرا
 * نشان می‌دهد و «اجرا» جدا است. بازگشت همیشه کامل است و دسترسی را می‌بندد.
 */
final class EnrollmentRefundPage extends Page
{
    public const ABILITY = 'admin.refund.issue';

    protected static ?string $slug = 'courses-refund';

    protected static ?int $navigationSort = 49;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected string $view = 'courses::filament.pages.refund';

    public string $query = '';

    public ?string $error = null;

    /** @var list<array<string, mixed>>|null */
    public ?array $rows = null;

    /** @var array<int, string> */
    public array $reasons = [];

    public ?int $confirming = null;

    public static function getNavigationLabel(): string
    {
        return 'بازگشت وجه دوره';
    }

    public function getTitle(): string
    {
        return 'بازگشت وجه ثبت‌نام دوره';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function find(): void
    {
        $this->error = null;
        $this->rows = null;
        $this->confirming = null;

        $query = trim($this->query);
        $mobile = Mobile::normalize($query);

        $enrollments = Enrollment::query()
            ->with('course')
            ->when(
                $mobile !== null,
                fn ($q) => $q->where('student_user_id', User::query()->where('mobile', $mobile)->value('id') ?? 0),
                fn ($q) => $q->where('uuid', $query),
            )
            ->whereIn('status', [EnrollmentStatus::Paid->value, EnrollmentStatus::Refunded->value])
            ->latest('id')
            ->get();

        if ($query === '' || $enrollments->isEmpty()) {
            $this->error = 'ثبت‌نام پرداخت‌شده‌ای با این شناسه یا موبایل پیدا نشد.';

            return;
        }

        $this->rows = $enrollments->map(static fn (Enrollment $enrollment): array => [
            'id' => $enrollment->id,
            'uuid' => $enrollment->uuid,
            'course' => $enrollment->course->title,
            'price' => $enrollment->price()->format(),
            'instructor' => $enrollment->instructorAmount()->format(),
            'commission' => $enrollment->commission()->format(),
            'source' => $enrollment->payment_source->label(),
            'paidAt' => $enrollment->paid_at?->format('Y-m-d H:i'),
            'progress' => $enrollment->progress()->count().' از '.$enrollment->course->sessions()->count(),
            'refundable' => $enrollment->status === EnrollmentStatus::Paid
                && in_array($enrollment->payment_source, [PaymentSource::Gateway, PaymentSource::Wallet], true)
                && ! $enrollment->price()->isZero(),
            'status' => $enrollment->status->label(),
            'refunded' => $enrollment->status === EnrollmentStatus::Refunded,
        ])->all();
    }

    public function ask(int $id): void
    {
        $this->confirming = $id;
    }

    public function confirm(int $id, RefundEnrollment $refund): void
    {
        $enrollment = Enrollment::query()->find($id);
        $actorId = Auth::id();

        if ($enrollment === null || ! is_int($actorId) || $this->confirming !== $id) {
            return;
        }

        $reason = trim($this->reasons[$id] ?? '');

        try {
            $refund->handle($enrollment, $actorId, $reason === '' ? null : $reason);

            Notification::make()->title('وجه دوره به کیف پول دانشجو برگشت')->success()->send();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('ثبت نشد')->body($exception->getMessage())->danger()->send();
        }

        unset($this->reasons[$id]);
        $this->find();
    }
}
