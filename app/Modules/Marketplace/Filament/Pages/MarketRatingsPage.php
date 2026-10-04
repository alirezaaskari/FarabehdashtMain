<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Filament\Pages;

use App\Modules\Marketplace\Actions\RateContract;
use App\Modules\Marketplace\Admin\PendingMarketItems;
use App\Modules\Marketplace\Domain\MarketRating;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * امتیازهای بازار پروژه (بخش ۲۱-۶، DEC-85): مدیر متن توهین‌آمیز یا
 * نامربوط را پنهان می‌کند. عدد امتیاز هرگز تغییر نمی‌کند.
 */
final class MarketRatingsPage extends Page
{
    private const LIMIT = 50;

    protected static ?string $slug = 'market-ratings';

    protected static ?int $navigationSort = 51;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected string $view = 'marketplace::filament.pages.market-ratings';

    /** @var list<array{id: int, stars: int, comment: string, meta: string, hidden: bool}> */
    public array $rows = [];

    public static function getNavigationLabel(): string
    {
        return 'امتیازهای بازار پروژه';
    }

    public function getTitle(): string
    {
        return 'امتیازهای بازار پروژه';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingMarketItems::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function hide(int $id, RateContract $rate): void
    {
        $this->moderate($id, $rate, true);
    }

    public function show(int $id, RateContract $rate): void
    {
        $this->moderate($id, $rate, false);
    }

    private function moderate(int $id, RateContract $rate, bool $hidden): void
    {
        $rate->moderate(MarketRating::query()->findOrFail($id), (int) Auth::id(), $hidden);
        Notification::make()->title($hidden ? 'متن پنهان شد' : 'متن دوباره نمایش داده می‌شود')->success()->send();
        $this->load();
    }

    private function load(): void
    {
        $this->rows = MarketRating::query()
            ->whereNotNull('comment')
            ->with('contract.project:id,title')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(static fn (MarketRating $rating): array => [
                'id' => $rating->id,
                'stars' => $rating->stars,
                'comment' => (string) $rating->comment,
                'meta' => implode(' · ', [
                    $rating->contract->project->title,
                    'به '.$rating->side->rateeLabel().' (کاربر #'.$rating->ratee_user_id.')',
                    JalaliDate::long($rating->created_at),
                ]),
                'hidden' => $rating->hidden_at !== null,
            ])
            ->values()
            ->all();
    }
}
