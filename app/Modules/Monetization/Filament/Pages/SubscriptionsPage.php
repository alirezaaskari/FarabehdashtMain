<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Filament\Pages;

use App\Models\User;
use App\Modules\Monetization\Actions\AssignTeamSeat;
use App\Modules\Monetization\Actions\RevokeTeamSeat;
use App\Modules\Monetization\Domain\Plan;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Modules\Monetization\Services\PlanCatalog;
use App\Modules\Monetization\Services\SubscriptionReader;
use App\Support\JalaliDate;
use App\Support\Mobile;
use App\Support\Money;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

/**
 * اشتراک‌ها: قیمت پلن‌ها، مشترکان فعال و صندلی‌های تیمی.
 *
 * قیمت از همین‌جا عوض می‌شود و نه از فایل پیکربندی، چون «مدیریت بدون
 * کدنویسی» قاعده محصول است. قیمت هر دوره پرداخت‌شده Snapshot شده، پس تغییر
 * این عدد هیچ صورتحساب گذشته‌ای را جابه‌جا نمی‌کند.
 *
 * صندلی تیمی این‌جا دستی اعطا می‌شود: صورتحساب خودکار تیمی و رابط خودسرویس
 * به نسخه ۲ موکول شده‌اند (`docs/roadmap/revenue-additions.md`)، ولی مدل
 * داده از روز اول هست تا افزودنش بعداً بازنویسی لایه دسترسی نباشد.
 */
final class SubscriptionsPage extends Page
{
    public const ABILITY = 'admin.finance.reports';

    protected static ?string $slug = 'subscriptions';

    protected static ?int $navigationSort = 61;

    protected string $view = 'monetization::filament.pages.subscriptions';

    /** @var array<string, string> */
    public array $prices = [];

    public string $ownerMobile = '';

    public string $memberMobile = '';

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'اشتراک‌ها';
    }

    public function getTitle(): string
    {
        return 'اشتراک‌ها و صندلی‌های تیمی';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(PlanCatalog $plans): void
    {
        foreach ($plans->active() as $plan) {
            $this->prices[$plan->slug] = (string) $plan->price_toman;
        }
    }

    /**
     * @return list<array{slug: string, title: string, cycle: string, price: string}>
     */
    public function planRows(PlanCatalog $plans): array
    {
        $rows = [];

        foreach ($plans->active() as $plan) {
            $rows[] = [
                'slug' => $plan->slug,
                'title' => $plan->title,
                'cycle' => $plan->billing_cycle->label(),
                'price' => $plan->price()->format(),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{mobile: string, ends: string, status: string, seats: int}>
     */
    public function subscriberRows(): array
    {
        $rows = [];

        $subscriptions = Subscription::query()->current()->with(['user', 'seats'])->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;

            $rows[] = [
                'mobile' => $user === null ? '—' : (Mobile::tryFromInput((string) $user->mobile)?->masked() ?? '—'),
                'ends' => $subscription->ends_at === null ? '—' : JalaliDate::short($subscription->ends_at),
                'status' => $subscription->status->label(),
                'seats' => $subscription->seats->whereNull('revoked_at')->count(),
            ];
        }

        return $rows;
    }

    public function savePrices(PlanCatalog $plans): void
    {
        $this->error = null;

        foreach ($plans->active() as $plan) {
            $input = $this->prices[$plan->slug] ?? null;

            if ($input === null) {
                continue;
            }

            try {
                $price = Money::fromInput($input);
            } catch (InvalidArgumentException $exception) {
                $this->error = $exception->getMessage();

                return;
            }

            Plan::query()->whereKey($plan->getKey())->update(['price_toman' => $price->toman]);
        }

        Notification::make()->title('قیمت‌ها ذخیره شد')->success()->send();
    }

    public function grantSeat(SubscriptionReader $subscriptions, AssignTeamSeat $assign): void
    {
        $this->error = null;

        $owner = $this->findUser($this->ownerMobile);
        $member = $this->findUser($this->memberMobile);

        if ($owner === null || $member === null) {
            $this->error ??= 'کاربری با این شماره موبایل پیدا نشد.';

            return;
        }

        $subscription = $subscriptions->ownSubscription($owner);

        if ($subscription === null) {
            $this->error = 'این کاربر اشتراک فعالی ندارد.';

            return;
        }

        try {
            $assign->handle($subscription, $member, is_int(Auth::id()) ? (int) Auth::id() : null);
        } catch (RuntimeException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->memberMobile = '';

        Notification::make()->title('صندلی داده شد')->success()->send();
    }

    public function revokeSeat(RevokeTeamSeat $revoke): void
    {
        $this->error = null;

        $member = $this->findUser($this->memberMobile);

        if ($member === null) {
            $this->error ??= 'کاربری با این شماره موبایل پیدا نشد.';

            return;
        }

        $seat = TeamSeat::query()->active()->where('member_user_id', $member->getKey())->first();

        if ($seat === null) {
            $this->error = 'این کاربر صندلی فعالی ندارد.';

            return;
        }

        $revoke->handle($seat, is_int(Auth::id()) ? (int) Auth::id() : null);

        Notification::make()->title('صندلی پس گرفته شد')->success()->send();
    }

    private function findUser(string $input): ?User
    {
        try {
            $mobile = Mobile::fromInput($input);
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return null;
        }

        return User::query()->where('mobile', $mobile->value)->first();
    }
}
