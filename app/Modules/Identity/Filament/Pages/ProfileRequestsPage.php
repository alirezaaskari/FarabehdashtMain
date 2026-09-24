<?php

declare(strict_types=1);

namespace App\Modules\Identity\Filament\Pages;

use App\Models\User;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\UserProfile;
use App\Support\JalaliDate;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * صف درخواست‌های نقش تجاری (فروشنده، مدرس، مشاور، کارفرما و کارجو).
 *
 * بدون این صفحه `ReviewProfileRequest` فقط از تست صدا زده می‌شد و روی سایت
 * زنده هیچ‌کس فروشنده یا مدرس نمی‌شد. دکمه‌ها همان اکشن را صدا می‌زنند تا
 * رویداد و دفتر رویداد دور زده نشود. شماره موبایل نشان داده نمی‌شود؛ نام و
 * شناسه برای بررسی کافی است.
 */
final class ProfileRequestsPage extends Page
{
    public const ABILITY = 'admin.content.review';

    protected static ?string $slug = 'profile-requests';

    protected static ?int $navigationSort = 45;

    protected string $view = 'identity::filament.pages.profile-requests';

    /** @var list<array{id: int, user: string, type: string, requested: string|null}> */
    public array $rows = [];

    /** @var array<int, string> */
    public array $rejectNotes = [];

    public static function getNavigationLabel(): string
    {
        return 'درخواست‌های نقش';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = UserProfile::query()->awaitingReview()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'درخواست‌های نقش';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function approve(int $id, ReviewProfileRequest $review): void
    {
        $profile = $this->pending($id);
        $admin = Auth::user();

        if ($profile === null || ! $admin instanceof User) {
            return;
        }

        $review->approve($profile, $admin);

        Notification::make()->title(sprintf('نقش «%s» تأیید شد', $profile->type->label()))->success()->send();

        $this->load();
    }

    public function reject(int $id, ReviewProfileRequest $review): void
    {
        $profile = $this->pending($id);
        $admin = Auth::user();
        $note = trim($this->rejectNotes[$id] ?? '');

        if ($profile === null || ! $admin instanceof User) {
            return;
        }

        if ($note === '') {
            Notification::make()->title('رد نشد')->body('دلیل رد را بنویسید؛ کاربر همین را می‌بیند.')->danger()->send();

            return;
        }

        $review->reject($profile, $admin, $note);
        unset($this->rejectNotes[$id]);

        Notification::make()->title(sprintf('نقش «%s» رد شد', $profile->type->label()))->success()->send();

        $this->load();
    }

    /** فقط درخواست در انتظار؛ دکمه‌ای که از صفحه کهنه زده شود، نقش فعال را دوباره بررسی نمی‌کند. */
    private function pending(int $id): ?UserProfile
    {
        return UserProfile::query()->awaitingReview()->find($id);
    }

    private function load(): void
    {
        $this->rows = UserProfile::query()
            ->awaitingReview()
            ->with('user')
            ->oldest('requested_at')
            ->get()
            ->map(static fn (UserProfile $profile): array => [
                'id' => $profile->id,
                'user' => ($profile->user->name ?? '').' (کاربر #'.$profile->user_id.')',
                'type' => $profile->type->label(),
                'requested' => $profile->requested_at === null ? null : JalaliDate::long($profile->requested_at),
            ])
            ->values()
            ->all();
    }
}
