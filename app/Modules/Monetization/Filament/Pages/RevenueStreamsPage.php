<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Filament\Pages;

use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Enums\ShutdownPolicy;
use App\Modules\Monetization\Services\ShutdownPreview;
use App\Modules\Monetization\Services\StreamRegistry;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * کلیدهای درآمدزایی.
 *
 * پیش از خاموش‌کردن، چهار عدد سند کلیدها نشان داده می‌شود: مشترک فعال،
 * درآمد ماهانه متأثر، صفحه‌های پنهان‌شونده و مبلغ بازگشت وجه لازم. مدیری که
 * این چهار عدد را ندیده، تصمیم نگرفته — حدس زده.
 */
final class RevenueStreamsPage extends Page
{
    public const ABILITY = 'admin.monetization.manage';

    protected static ?string $slug = 'revenue-streams';

    protected static ?int $navigationSort = 60;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected string $view = 'monetization::filament.pages.revenue-streams';

    public ?string $pending = null;

    public string $policy = ShutdownPolicy::RunToEnd->value;

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'کلیدهای درآمدزایی';
    }

    public function getTitle(): string
    {
        return 'کلیدهای درآمدزایی';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    /**
     * وضعیت همه جریان‌ها، برای جدول صفحه.
     *
     * @return list<array{value: string, label: string, enabled: bool, built: bool}>
     */
    public function rows(StreamRegistry $streams): array
    {
        $rows = [];

        foreach (RevenueStream::cases() as $stream) {
            $rows[] = [
                'value' => $stream->value,
                'label' => $stream->label(),
                'enabled' => $streams->isEnabled($stream),
                'built' => $stream->isBuilt(),
            ];
        }

        return $rows;
    }

    /**
     * چهار عدد پیش‌نمایش خاموشی، فقط برای جریانی که مدیر انتخاب کرده.
     *
     * @return array{label: string, subscribers: int, revenue: string, pages: int, refund: string}|null
     */
    public function preview(ShutdownPreview $preview): ?array
    {
        $stream = $this->pendingStream();

        if ($stream === null) {
            return null;
        }

        $summary = $preview->for($stream);

        return [
            'label' => $stream->label(),
            'subscribers' => $summary->activeSubscribers,
            'revenue' => $summary->monthlyRevenue->format(),
            'pages' => $summary->hiddenPages,
            'refund' => $summary->refundNeeded->format(),
        ];
    }

    public function choose(string $stream): void
    {
        $this->error = null;
        $this->pending = $stream;
        $this->policy = ShutdownPolicy::RunToEnd->value;
    }

    public function enable(string $stream, ToggleRevenueStream $toggle): void
    {
        $this->apply(RevenueStream::tryFrom($stream), true, ShutdownPolicy::RunToEnd, $toggle);
    }

    public function disable(ToggleRevenueStream $toggle): void
    {
        $this->apply(
            $this->pendingStream(),
            false,
            ShutdownPolicy::tryFrom($this->policy) ?? ShutdownPolicy::RunToEnd,
            $toggle,
        );
    }

    private function apply(?RevenueStream $stream, bool $enabled, ShutdownPolicy $policy, ToggleRevenueStream $toggle): void
    {
        $this->error = null;

        if ($stream === null) {
            return;
        }

        $actorId = Auth::id();

        try {
            $toggle->handle($stream, $enabled, $policy, is_int($actorId) ? $actorId : null);
        } catch (RuntimeException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->pending = null;

        Notification::make()
            ->title($enabled ? 'جریان روشن شد' : 'جریان خاموش شد')
            ->success()
            ->send();
    }

    private function pendingStream(): ?RevenueStream
    {
        return $this->pending === null ? null : RevenueStream::tryFrom($this->pending);
    }
}
