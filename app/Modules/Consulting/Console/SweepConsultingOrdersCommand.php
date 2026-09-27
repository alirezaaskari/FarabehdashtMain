<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Console;

use App\Modules\Consulting\Actions\ConsultingOrderFlow;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * دو مهلت خودکار درخواست خدمت: بی‌پاسخی مشاور (DEC-53) و آزادسازی پس از
 * «انجام شد» بی‌اعتراض (DEC-54). هر ساعت اجرا می‌شود؛ اجرای دوباره بی‌اثر است.
 */
#[AsCommand(name: 'consulting:sweep', description: 'بستن درخواست‌های بی‌پاسخ و آزادسازی کارهای تأییدنشده پس از مهلت')]
final class SweepConsultingOrdersCommand extends Command
{
    public function handle(ConsultingOrderFlow $flow, Repository $config): int
    {
        $now = Carbon::now();
        $replyBy = $now->copy()->subHours((int) $config->get('consulting.orders.reply_hours', 48));
        $releaseBy = $now->copy()->subDays((int) $config->get('consulting.orders.auto_release_days', 7));

        $expired = $this->each(
            ConsultingOrder::query()->where('status', OrderStatus::AwaitingConsultant)->where('paid_at', '<=', $replyBy),
            $flow->expire(...),
        );

        $released = $this->each(
            ConsultingOrder::query()->where('status', OrderStatus::Delivered)->where('delivered_at', '<=', $releaseBy),
            $flow->autoRelease(...),
        );

        $this->info("بی‌پاسخ بسته شد: {$expired} · آزاد شد: {$released}");

        return self::SUCCESS;
    }

    /**
     * @param  Builder<ConsultingOrder>  $query
     * @param  callable(ConsultingOrder): ConsultingOrder  $step
     */
    private function each($query, callable $step): int
    {
        $count = 0;

        foreach ($query->with('service')->lazyById() as $order) {
            try {
                $step($order);
                $count++;
            } catch (RuntimeException) {
                // هم‌زمان کاربر یا مدیر گامی برداشته؛ این درخواست دیگر در مهلت نیست.
            }
        }

        return $count;
    }
}
