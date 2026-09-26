<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\PayoutStatus;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Modules\Commerce\Events\PayoutPaid;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * مدیر مالی پس از واریز بانکی دستی، درخواست را «واریز شد» می‌زند. اثرش در
 * دفتر کل همان تسویه دستی است (بدهکار بدهی به فروشنده، بستانکار خزانه)، با
 * کلید یکتای همین درخواست تا دو کلیک دو بار کم نکند.
 */
final readonly class MarkPayoutPaid
{
    public function __construct(
        private SettleVendor $settle,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function handle(PayoutRequest $payout, int $actorId, string $bankReference): PayoutRequest
    {
        $bankReference = trim($bankReference);

        if ($bankReference === '' || mb_strlen($bankReference) > 120) {
            throw new InvalidArgumentException('شماره پیگیری واریز بانکی را بنویسید.');
        }

        $payout = $this->db->transaction(function () use ($payout, $actorId, $bankReference): PayoutRequest {
            $locked = PayoutRequest::query()->lockForUpdate()->findOrFail($payout->id);

            if (! $locked->isOpen()) {
                throw new InvalidArgumentException('این درخواست دیگر باز نیست.');
            }

            $this->settle->handle(
                $locked->user_id,
                $locked->amount(),
                $actorId,
                'درخواست تسویه '.$locked->uuid.' · پیگیری '.$bankReference,
                idempotencyKey: 'commerce.payout_paid:'.$locked->uuid,
            );

            $locked->forceFill([
                'status' => PayoutStatus::Paid,
                'decided_by' => $actorId,
                'decided_at' => now(),
                'bank_reference' => $bankReference,
            ])->save();

            return $locked;
        });

        $this->events->dispatch(new PayoutPaid($payout, $actorId));

        return $payout;
    }
}
