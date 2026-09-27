<?php

declare(strict_types=1);

namespace App\Modules\Core\Filament\Pages;

use App\Modules\Core\Services\Tunables;
use App\Support\Admin\NavigationGroup;
use App\Support\PersianDigits;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use UnitEnum;

/**
 * قیمت‌ها و زمان‌هایی که ماژول‌ها با `TunableSource` اعلام کرده‌اند، در یک صفحه.
 *
 * قیمت Pro، تک‌فروشی گزارش و نرخ کمیسیون صفحه خودشان را دارند (سابقه و تاریخ
 * اثر)؛ این صفحه فقط به آن‌ها پیوند می‌دهد.
 */
final class TunablesPage extends Page
{
    public const ABILITY = 'admin.settings.manage';

    protected static ?string $slug = 'tunables';

    protected static ?int $navigationSort = 10;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected string $view = 'core::filament.pages.tunables';

    /**
     * عدد هر کلید، همان‌طور که مدیر نوشته. Livewire نقطه را مسیر آرایه می‌خواند،
     * پس کلید با `field()` بی‌نقطه می‌شود.
     *
     * @var array<string, string>
     */
    public array $values = [];

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'قیمت‌ها و زمان‌ها';
    }

    public function getTitle(): string
    {
        return 'قیمت‌ها و زمان‌ها';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(Tunables $tunables): void
    {
        foreach (array_keys($tunables->definitions()) as $key) {
            $this->values[self::field($key)] = (string) $tunables->current($key);
        }
    }

    public function save(Tunables $tunables): void
    {
        $this->error = null;
        $parsed = [];

        foreach ($tunables->definitions() as $key => $tunable) {
            $raw = str_replace([',', '٬', '،', ' '], '', PersianDigits::toLatin(trim($this->values[self::field($key)] ?? '')));

            if ($raw === '' || ! ctype_digit($raw)) {
                $this->error = '«'.$tunable->label.'» را فقط با رقم بنویسید.';

                return;
            }

            $parsed[$key] = (int) $raw;
        }

        try {
            $changed = $tunables->update($parsed, (int) Auth::id());
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        Notification::make()
            ->title($changed === [] ? 'چیزی تغییر نکرده بود' : 'ذخیره شد')
            ->success()
            ->send();
    }

    public static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    /** @return array<string, string> پیوند صفحه‌هایی که قیمت‌های دیگر را نگه می‌دارند */
    public function elsewhere(): array
    {
        $pages = [
            'filament.fbh.pages.subscriptions' => 'قیمت ماهانه و سالانه حرفه‌ای',
            'filament.fbh.pages.report-sales' => 'قیمت خرید تکی گزارش',
            'filament.fbh.pages.commission-rates' => 'نرخ کمیسیون هر جریان فروش',
            'filament.fbh.pages.exam-packs' => 'قیمت بسته‌های آزمون',
            'filament.fbh.pages.bundles' => 'قیمت بسته‌های راه‌حل',
            'filament.fbh.pages.webinars' => 'قیمت و زمان رویدادها',
        ];

        $links = [];

        foreach ($pages as $route => $label) {
            if (Route::has($route)) {
                $links[route($route)] = $label;
            }
        }

        return $links;
    }
}
