<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\PayoutStatus;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Modules\Commerce\Domain\VendorBankAccount;
use App\Modules\Commerce\Events\PayoutRequested;
use App\Modules\Commerce\Services\Payouts;
use App\Support\Money;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * درخواست تسویه فروشنده یا مدرس (DEC-45): دست‌کم کمترین مبلغ، نه بیشتر از
 * مانده، و در هر زمان فقط یک درخواست باز.
 *
 * ردیف حساب بانکی کاربر قفل می‌شود تا دو درخواست هم‌زمان هر دو «باز» نشوند.
 */
final readonly class RequestPayout
{
    public function __construct(
        private Payouts $payouts,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function handle(int $userId, Money $amount): PayoutRequest
    {
        $payout = $this->db->transaction(function () use ($userId, $amount): PayoutRequest {
            $account = VendorBankAccount::query()->where('user_id', $userId)->lockForUpdate()->first();

            if ($account === null) {
                throw new InvalidArgumentException('اول شماره شبا و نام صاحب حساب را ثبت کنید.');
            }

            if ($this->payouts->openRequest($userId) !== null) {
                throw new InvalidArgumentException('یک درخواست تسویه باز دارید؛ پس از واریز یا لغو آن، درخواست تازه بدهید.');
            }

            if ($amount->isLessThan($this->payouts->minimum())) {
                throw new InvalidArgumentException(sprintf('کمترین مبلغ تسویه %s است.', $this->payouts->minimum()->format()));
            }

            if ($amount->isGreaterThan($this->payouts->owed($userId))) {
                throw new InvalidArgumentException('مبلغ درخواست از مانده قابل‌تسویه شما بیشتر است.');
            }

            return PayoutRequest::query()->create([
                'uuid' => (string) Str::uuid7(),
                'user_id' => $userId,
                'amount_toman' => $amount->toman,
                'status' => PayoutStatus::Requested,
                'sheba' => $account->sheba,
                'holder_name' => $account->holder_name,
            ]);
        });

        $this->events->dispatch(new PayoutRequested($payout));

        return $payout;
    }
}
