<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\PayoutStatus;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Modules\Commerce\Events\PayoutCancelled;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/** لغو درخواست باز به دست خود فروشنده، مثلاً برای عوض‌کردن مبلغ یا حساب. */
final readonly class CancelPayout
{
    public function __construct(
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function handle(PayoutRequest $payout): PayoutRequest
    {
        $payout = $this->db->transaction(function () use ($payout): PayoutRequest {
            $locked = PayoutRequest::query()->lockForUpdate()->findOrFail($payout->id);

            if (! $locked->isOpen()) {
                throw new InvalidArgumentException('این درخواست دیگر باز نیست.');
            }

            $locked->forceFill(['status' => PayoutStatus::Cancelled, 'decided_at' => now()])->save();

            return $locked;
        });

        $this->events->dispatch(new PayoutCancelled($payout));

        return $payout;
    }
}
