<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\PayoutStatus;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Modules\Commerce\Events\PayoutRejected;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * رد درخواست، مثلاً وقتی شبا به نام کس دیگری است. دلیل الزامی است چون
 * فروشنده همان را در اعلانش می‌بیند. مانده دست‌نخورده می‌ماند.
 */
final readonly class RejectPayout
{
    public function __construct(
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function handle(PayoutRequest $payout, int $actorId, string $reason): PayoutRequest
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('دلیل رد را بنویسید؛ فروشنده همین را می‌بیند.');
        }

        $payout = $this->db->transaction(function () use ($payout, $actorId, $reason): PayoutRequest {
            $locked = PayoutRequest::query()->lockForUpdate()->findOrFail($payout->id);

            if (! $locked->isOpen()) {
                throw new InvalidArgumentException('این درخواست دیگر باز نیست.');
            }

            $locked->forceFill([
                'status' => PayoutStatus::Rejected,
                'decided_by' => $actorId,
                'decided_at' => now(),
                'note' => $reason,
            ])->save();

            return $locked;
        });

        $this->events->dispatch(new PayoutRejected($payout, $actorId));

        return $payout;
    }
}
