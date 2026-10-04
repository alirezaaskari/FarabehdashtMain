<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Contracts\EscrowKeeper;
use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Modules\Marketplace\Events\ContractChanged;
use App\Support\Escrow\EscrowHoldRequest;
use App\Support\Ledger\AccountType;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * پرداخت یک مرحله با درگاه یا کیف پول و نشاندن پول در «امانت وجه پروژه».
 *
 * فقط مرحله‌ای که نوبتش است (اولین پرداخت‌نشده پس از آزادسازی قبلی)
 * پرداخت می‌شود. کلید امانت یکتاست، پس بازگشت دوباره درگاه اثر مالی دوم ندارد.
 * مالیِ قرارداد جاری با خاموش شدن کلید درآمدی بسته نمی‌شود.
 */
final readonly class MilestoneCheckout
{
    public function __construct(
        private Container $container,
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
        private WalletCheckout $wallet,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function startGateway(MarketMilestone $milestone, int $payerId, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();
        $this->assertPayable($milestone, $payerId);

        $result = $this->gateway->requestPayment(new PaymentRequest(
            amount: $milestone->amount(),
            description: 'مرحله پروژه '.$milestone->uuid,
            callbackUrl: route('market.milestones.callback'),
            orderUuid: $milestone->uuid,
            payerMobile: $payerMobile,
        ));

        $milestone->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }

    public function payFromWallet(MarketMilestone $milestone, int $payerId): MarketMilestone
    {
        $this->guard->assertAllowed();
        $this->assertPayable($milestone, $payerId);
        $this->wallet->assertCanPay($payerId, $milestone->amount());

        return $this->complete($milestone, null, PaymentSource::Wallet);
    }

    /** پرداخت تأییدشده: پول در امانت پروژه، مهلت تحویل از همین لحظه. */
    public function complete(MarketMilestone $milestone, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): MarketMilestone
    {
        $milestone = $this->db->transaction(function () use ($milestone, $gatewayRefId, $source): MarketMilestone {
            $locked = MarketMilestone::query()->whereKey($milestone->id)->lockForUpdate()->firstOrFail();
            $contract = $locked->contract;
            $this->assertPayable($locked, $contract->client_user_id);

            $commission = $locked->amount()->percentage($contract->commission_bp / 100);
            $hold = $this->container->make(EscrowKeeper::class)->hold(new EscrowHoldRequest(
                key: $locked->escrowKey(),
                payerUserId: $contract->client_user_id,
                payeeUserId: $contract->provider_user_id,
                amount: $locked->amount(),
                commission: $commission,
                source: $source,
                referenceType: MarketMilestone::class,
                referenceId: $locked->uuid,
                memo: 'مرحله '.$locked->position.' پروژه '.$contract->uuid,
                account: AccountType::ProjectEscrow,
            ));

            $now = Carbon::now();
            $locked->forceFill([
                'status' => MilestoneStatus::Funded,
                'commission_toman' => $commission->toman,
                'escrow_uuid' => $hold->uuid,
                'payment_source' => $source,
                'gateway_ref_id' => $gatewayRefId,
                'funded_at' => $now,
                'due_at' => $now->copy()->addDays($locked->days),
            ])->save();

            if ($contract->status === ContractStatus::AwaitingPayment) {
                $contract->forceFill(['status' => ContractStatus::Active])->save();
            }

            return $locked->setRelation('contract', $contract);
        });

        $this->events->dispatch(new ContractChanged($milestone->contract, ContractChanged::FUNDED, $milestone->contract->client_user_id, $milestone));

        return $milestone;
    }

    private function assertPayable(MarketMilestone $milestone, int $payerId): void
    {
        $contract = $milestone->contract;

        if ($contract->client_user_id !== $payerId) {
            throw new RuntimeException('فقط کارفرمای همین قرارداد مرحله را می‌پردازد.');
        }

        if (! $this->container->bound(EscrowKeeper::class)) {
            throw new RuntimeException('پرداخت مرحله فعلاً در دسترس نیست.');
        }

        if ($contract->status === ContractStatus::AwaitingPayment && $contract->pay_by->isPast()) {
            throw new RuntimeException('مهلت پرداخت مرحله اول گذشته و این قرارداد بی‌اثر می‌شود.');
        }

        if ($contract->payable()?->id !== $milestone->id) {
            throw new RuntimeException('نوبت پرداخت این مرحله نیست؛ مرحله بعد پس از آزادسازی مرحله قبل پرداخت می‌شود.');
        }
    }
}
