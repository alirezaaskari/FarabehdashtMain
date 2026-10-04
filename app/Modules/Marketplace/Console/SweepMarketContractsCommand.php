<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Console;

use App\Modules\Marketplace\Actions\AcceptBid;
use App\Modules\Marketplace\Actions\MilestoneFlow;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketMilestone;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * دو مهلت خودکار بازار پروژه (DEC-81): بی‌اثر شدن قراردادی که مرحله اولش
 * پرداخت نشد، و آزادسازی مرحله تحویل‌شده‌ای که کارفرما پاسخش نداد.
 * هر ساعت اجرا می‌شود؛ اجرای دوباره بی‌اثر است.
 */
#[AsCommand(name: 'marketplace:sweep', description: 'بی‌اثر کردن قراردادهای پرداخت‌نشده و آزادسازی مرحله‌های بی‌پاسخ')]
final class SweepMarketContractsCommand extends Command
{
    public function handle(AcceptBid $contracts, MilestoneFlow $flow): int
    {
        $now = Carbon::now();
        $lapsed = 0;
        $released = 0;

        foreach (MarketContract::query()->where('status', ContractStatus::AwaitingPayment)->where('pay_by', '<=', $now)->lazyById() as $contract) {
            try {
                $contracts->lapse($contract);
                $lapsed++;
            } catch (RuntimeException) {
                // کارفرما هم‌زمان پرداخت کرد.
            }
        }

        foreach (MarketMilestone::query()->where('status', MilestoneStatus::Delivered)->where('release_at', '<=', $now)->lazyById() as $milestone) {
            try {
                $flow->autoRelease($milestone);
                $released++;
            } catch (RuntimeException) {
                // کارفرما هم‌زمان تأیید کرد یا اصلاح خواست.
            }
        }

        $this->info("قرارداد بی‌اثر: {$lapsed} · مرحله آزادشده: {$released}");

        return self::SUCCESS;
    }
}
