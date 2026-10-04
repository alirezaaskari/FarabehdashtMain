<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Filament\Pages;

use App\Modules\Marketplace\Actions\ContractResolution;
use App\Modules\Marketplace\Admin\PendingMarketItems;
use App\Modules\Marketplace\Domain\DeliveryFile;
use App\Modules\Marketplace\Domain\Enums\MessageStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Modules\Marketplace\Domain\MilestoneDelivery;
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
 * اختلاف‌های بازار پروژه (بخش ۲۱-۵): مدیر مالی دلیل، تحویل‌ها، فایل‌ها و
 * گفت‌وگو را می‌بیند و یکی از سه رأی را می‌دهد. دکمه‌ها همان Action را صدا
 * می‌زنند تا امانت، اعلان و دفتر رویداد دور زده نشود.
 */
final class MarketDisputesPage extends Page
{
    protected static ?string $slug = 'market-disputes';

    protected static ?int $navigationSort = 59;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected string $view = 'marketplace::filament.pages.market-disputes';

    /** @var list<array{id: int, title: string, meta: string, amount: int, reason: string, deliveries: list<array{note: string, revision: string|null, files: list<array{name: string, url: string}>}>, messages: list<array{who: string, body: string}>}> */
    public array $rows = [];

    /** @var array<string, string> */
    public array $notes = [];

    /** @var array<string, int|string> */
    public array $amounts = [];

    public static function getNavigationLabel(): string
    {
        return 'اختلاف‌های بازار پروژه';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MarketMilestone::query()->where('status', MilestoneStatus::Disputed)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'اختلاف‌های بازار پروژه';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingMarketItems::DISPUTE_ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function refundAll(int $id, ContractResolution $resolution): void
    {
        $this->resolve($id, $resolution, static fn (MarketMilestone $milestone): Money => $milestone->amount());
    }

    public function releaseAll(int $id, ContractResolution $resolution): void
    {
        $this->resolve($id, $resolution, static fn (): Money => Money::zero());
    }

    public function split(int $id, ContractResolution $resolution): void
    {
        $amount = (int) ($this->amounts['m'.$id] ?? 0);

        if ($amount <= 0) {
            Notification::make()->title('مبلغ بازگشتی به کارفرما را بنویسید')->danger()->send();

            return;
        }

        $this->resolve($id, $resolution, static fn (): Money => Money::toman($amount));
    }

    /** @param  callable(MarketMilestone): Money  $toClient */
    private function resolve(int $id, ContractResolution $resolution, callable $toClient): void
    {
        try {
            $milestone = MarketMilestone::query()->findOrFail($id);
            $resolution->resolve($milestone, (int) Auth::id(), $toClient($milestone), $this->notes['m'.$id] ?? '');
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
        $this->rows = MarketMilestone::query()
            ->where('status', MilestoneStatus::Disputed)
            ->with(['contract.project', 'contract.bid.messages', 'deliveries.files', 'disputes'])
            ->oldest('updated_at')
            ->get()
            ->map(static function (MarketMilestone $milestone): array {
                $contract = $milestone->contract;
                $dispute = $milestone->openDispute();

                return [
                    'id' => $milestone->id,
                    'title' => $contract->project->title.' — مرحله '.$milestone->position.': '.$milestone->title,
                    'meta' => implode(' · ', array_filter([
                        $milestone->amount()->format(),
                        'کارفرما: کاربر #'.$contract->client_user_id,
                        'مجری: کاربر #'.$contract->provider_user_id,
                        $dispute ? 'اعتراض از '.($dispute->opened_by === $contract->client_user_id ? 'کارفرما' : 'مجری').'، '.JalaliDate::long($dispute->created_at) : null,
                        $milestone->due_at ? 'مهلت تحویل: '.JalaliDate::long($milestone->due_at) : null,
                    ])),
                    'amount' => $milestone->amount_toman,
                    'reason' => (string) $dispute?->reason,
                    'deliveries' => $milestone->deliveries->map(static fn (MilestoneDelivery $delivery): array => [
                        'note' => $delivery->note,
                        'revision' => $delivery->revision_note,
                        'files' => $delivery->files->map(static fn (DeliveryFile $file): array => [
                            'name' => $file->original_name,
                            'url' => route('market.contracts.file', $file->uuid),
                        ])->values()->all(),
                    ])->values()->all(),
                    'messages' => $contract->bid->messages
                        ->where('status', MessageStatus::Delivered)
                        ->map(static fn (MarketMessage $message): array => [
                            'who' => $message->sender_user_id === $contract->client_user_id ? 'کارفرما' : 'مجری',
                            'body' => $message->body,
                        ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
