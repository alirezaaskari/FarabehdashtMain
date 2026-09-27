<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Filament\Pages;

use App\Modules\Consulting\Actions\ConsultingOrderFlow;
use App\Modules\Consulting\Admin\PendingConsultingItems;
use App\Modules\Consulting\Domain\ConsultingMessage;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use App\Support\Money;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * اعتراض‌های خدمت مشاوره (DEC-54): مدیر بازگشت کامل، آزادسازی کامل یا
 * تقسیم را با توضیح انتخاب می‌کند. شرح نیاز و گفت‌وگو برای داوری این‌جاست؛
 * شماره تماس هیچ طرفی نه.
 */
final class ConsultingDisputesPage extends Page
{
    protected static ?string $slug = 'consulting-disputes';

    protected static ?int $navigationSort = 58;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected string $view = 'consulting::filament.pages.consulting-disputes';

    /** @var list<array{id: int, title: string, meta: string, price: int, need: string, reason: string, messages: list<array{who: string, body: string}>}> */
    public array $rows = [];

    /** @var array<string, string> */
    public array $notes = [];

    /** @var array<string, string|int|null> مبلغ بازگشتی به خریدار برای تقسیم */
    public array $amounts = [];

    public static function getNavigationLabel(): string
    {
        return 'اعتراض خدمت مشاوره';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ConsultingOrder::query()->where('status', OrderStatus::Disputed)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'اعتراض‌های خدمت مشاوره';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingConsultingItems::DISPUTE_ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function refundAll(int $id, ConsultingOrderFlow $flow): void
    {
        $this->resolve($id, $flow, fn (ConsultingOrder $order): Money => $order->price());
    }

    public function releaseAll(int $id, ConsultingOrderFlow $flow): void
    {
        $this->resolve($id, $flow, static fn (): Money => Money::zero());
    }

    public function split(int $id, ConsultingOrderFlow $flow): void
    {
        $amount = (int) ($this->amounts['o'.$id] ?? 0);

        if ($amount <= 0) {
            Notification::make()->title('مبلغ بازگشتی به خریدار را بنویسید')->danger()->send();

            return;
        }

        $this->resolve($id, $flow, static fn (): Money => Money::toman($amount));
    }

    /** @param  callable(ConsultingOrder): Money  $toBuyer */
    private function resolve(int $id, ConsultingOrderFlow $flow, callable $toBuyer): void
    {
        try {
            $order = ConsultingOrder::query()->with('service')->findOrFail($id);
            $flow->resolve($order, (int) Auth::id(), $toBuyer($order), $this->notes['o'.$id] ?? '');
        } catch (RuntimeException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('رأی ثبت شد')->success()->send();
        $this->load();
    }

    private function load(): void
    {
        $this->notes = [];
        $this->amounts = [];
        $this->rows = ConsultingOrder::query()
            ->where('status', OrderStatus::Disputed)
            ->with(['service.profile', 'messages'])
            ->oldest('disputed_at')
            ->get()
            ->map(static fn (ConsultingOrder $order): array => [
                'id' => $order->id,
                'title' => $order->service->title,
                'meta' => implode(' · ', [
                    (string) $order->service->profile->display_name,
                    'خریدار: کاربر #'.$order->buyer_id,
                    $order->price()->format(),
                    'اعتراض: '.JalaliDate::long($order->disputed_at ?? $order->updated_at),
                    $order->delivered_at === null ? 'مشاور «انجام شد» نزده' : 'انجام شد: '.JalaliDate::long($order->delivered_at),
                ]),
                'price' => $order->price_toman,
                'need' => $order->need,
                'reason' => (string) $order->dispute_reason,
                'messages' => $order->messages->map(static fn (ConsultingMessage $message): array => [
                    'who' => $message->user_id === $order->buyer_id ? 'خریدار' : 'مشاور',
                    'body' => $message->body,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
