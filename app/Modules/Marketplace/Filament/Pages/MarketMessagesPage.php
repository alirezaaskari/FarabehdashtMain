<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Filament\Pages;

use App\Modules\Marketplace\Actions\ManageMarketModeration;
use App\Modules\Marketplace\Actions\ModerateMessage;
use App\Modules\Marketplace\Admin\PendingMarketItems;
use App\Modules\Marketplace\Domain\Enums\MessageStatus;
use App\Modules\Marketplace\Domain\Enums\ReviewMode;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Modules\Marketplace\Domain\MarketStrike;
use App\Modules\Marketplace\Services\MessagePolicy;
use App\Modules\Marketplace\Services\StrikeBook;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * پیام‌های بازار پروژه (DEC-80): کلید حالت بررسی، فهرست کلمه‌های مشکوک،
 * صف پیام‌های نگه‌داشته و کاربرانی که به سقف اخطار رسیده‌اند.
 */
final class MarketMessagesPage extends Page
{
    protected static ?string $slug = 'market-messages';

    protected static ?int $navigationSort = 50;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected string $view = 'marketplace::filament.pages.market-messages';

    public int $mode = 0;

    public string $words = '';

    /** @var list<array{id: int, body: string, meta: string, flags: list<string>}> */
    public array $held = [];

    /** @var list<array{user: int, strikes: int, since: string}> */
    public array $blocked = [];

    public static function getNavigationLabel(): string
    {
        return 'پیام‌های بازار';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MarketMessage::query()->where('status', MessageStatus::Held)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'بازار پروژه — پیام‌ها';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingMarketItems::ABILITY) === true;
    }

    /** @return list<array{value: int, label: string, description: string}> */
    public function modes(): array
    {
        return array_map(static fn (ReviewMode $mode): array => [
            'value' => $mode->value,
            'label' => $mode->label(),
            'description' => $mode->description(),
        ], ReviewMode::cases());
    }

    public function mount(MessagePolicy $policy): void
    {
        $this->mode = $policy->mode()->value;
        $this->words = implode("\n", $policy->words());
        $this->load();
    }

    public function saveSettings(ManageMarketModeration $moderation): void
    {
        $this->run(fn () => $moderation->save(
            ReviewMode::tryFrom($this->mode) ?? ReviewMode::Suspicious,
            preg_split('/\R/u', $this->words) ?: [],
            (int) Auth::id(),
        ), 'ذخیره شد؛ از پیام بعدی اثر دارد');
    }

    public function approve(int $id, ModerateMessage $moderate): void
    {
        $this->run(fn () => $moderate->approve(MarketMessage::query()->findOrFail($id), (int) Auth::id()), 'پیام رسید');
    }

    public function reject(int $id, ModerateMessage $moderate): void
    {
        $this->run(fn () => $moderate->reject(MarketMessage::query()->findOrFail($id), (int) Auth::id()), 'رد شد و یک اخطار ثبت شد');
    }

    public function reopen(int $userId, ManageMarketModeration $moderation): void
    {
        $this->run(fn () => $moderation->reopen($userId, (int) Auth::id()), 'دسترسی دوباره باز شد');
    }

    private static function flagLabel(string $flag): string
    {
        return match (true) {
            $flag === 'digits' => 'رشته رقم بلند',
            $flag === 'worded_digits' => 'رقم با حروف',
            $flag === 'email' => 'ایمیل',
            $flag === 'link' => 'لینک یا شناسه',
            default => 'کلمه «'.substr($flag, 5).'»',
        };
    }

    private function run(callable $action, string $done): void
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load();
    }

    private function load(): void
    {
        $policy = app(MessagePolicy::class);
        $strikes = app(StrikeBook::class);

        $this->held = MarketMessage::query()
            ->where('status', MessageStatus::Held)
            ->with('bid.project')
            ->oldest('id')
            ->get()
            ->map(static fn (MarketMessage $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'meta' => implode(' · ', [
                    'کاربر #'.$message->sender_user_id,
                    $message->sender_user_id === $message->bid->provider_user_id ? 'مجری' : 'کارفرما',
                    $message->bid->project->title,
                    JalaliDate::long($message->created_at),
                ]),
                'flags' => array_map(self::flagLabel(...), $policy->flags($message->body)),
            ])
            ->values()
            ->all();

        $this->blocked = MarketStrike::query()
            ->whereNull('cleared_at')
            ->selectRaw('user_id, count(*) as strikes, max(created_at) as latest')
            ->groupBy('user_id')
            ->havingRaw('count(*) >= ?', [$strikes->limit()])
            ->get()
            ->map(static fn (MarketStrike $row): array => [
                'user' => (int) $row->user_id,
                'strikes' => (int) $row->getAttribute('strikes'),
                'since' => JalaliDate::long(Carbon::parse((string) $row->getAttribute('latest'))),
            ])
            ->values()
            ->all();
    }
}
