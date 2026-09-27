<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Contracts\CommissionCalculator;
use App\Contracts\EscrowKeeper;
use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Contracts\SalesSwitch;
use App\Models\User;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Modules\Consulting\Domain\Enums\ServiceKind;
use App\Modules\Consulting\Events\ConsultingOrderChanged;
use App\Support\Escrow\EscrowHoldRequest;
use App\Support\Money;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * خرید خدمت: ثبت درخواست، پرداخت با درگاه یا کیف پول، و نشاندن پول در امانت.
 *
 * پول تا پایان کار به مشاور نمی‌رسد (بخش ۱۹-۱). کمیسیون هنگام پرداخت از
 * جریان `consulting` خوانده و ثابت می‌شود (DEC-52)؛ تغییر نرخ بعدی خرید قبلی
 * را عوض نمی‌کند.
 */
final readonly class ConsultingCheckout
{
    public const FLOW = 'consulting';

    public function __construct(
        private SalesSwitch $sales,
        private Container $container,
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
        private WalletCheckout $wallet,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function isOpen(): bool
    {
        return $this->sales->isOpen(SalesSwitch::CONSULTING_SERVICE) && $this->container->bound(EscrowKeeper::class);
    }

    /** @param  list<string>  $proposedTimes */
    public function place(User $buyer, ConsultingService $service, string $need, array $proposedTimes, ?string $city, bool $shareMobile): ConsultingOrder
    {
        if (! $this->isOpen()) {
            throw new RuntimeException('خرید خدمت مشاوره فعلاً بسته است.');
        }

        if (! $service->isOnSale()) {
            throw new RuntimeException('این خدمت فعلاً فروخته نمی‌شود.');
        }

        if ($service->profile->user_id === $buyer->getKey()) {
            throw new RuntimeException('خدمت خودتان را نمی‌توانید بخرید.');
        }

        if ($service->kind === ServiceKind::Visit && ! in_array($city, $service->cities ?? [], true)) {
            throw new RuntimeException('بازدید حضوری فقط در شهرهایی است که مشاور اعلام کرده.');
        }

        return ConsultingOrder::query()->create([
            'uuid' => (string) Str::uuid7(),
            'service_id' => $service->id,
            'buyer_id' => $buyer->getKey(),
            'consultant_id' => $service->profile->user_id,
            'price_toman' => $service->price_toman,
            'need' => $need,
            'proposed_times' => $proposedTimes,
            'city' => $service->kind === ServiceKind::Visit ? $city : null,
            'share_mobile' => $shareMobile,
            'status' => OrderStatus::AwaitingPayment,
        ]);
    }

    public function startGateway(ConsultingOrder $order, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();
        $this->assertAwaitingPayment($order);

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $order->price(),
                description: 'خدمت مشاوره '.$order->uuid,
                callbackUrl: route('consulting.orders.callback'),
                orderUuid: $order->uuid,
                payerMobile: $payerMobile,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            $order->forceFill(['status' => OrderStatus::Failed])->save();

            throw $exception;
        }

        $order->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }

    public function payFromWallet(ConsultingOrder $order): ConsultingOrder
    {
        $this->wallet->assertCanPay($order->buyer_id, $order->price());

        return $this->complete($order, null, PaymentSource::Wallet);
    }

    /**
     * پرداخت تأییدشده: پول به امانت می‌رود و نوبت مشاور است. کلید امانت
     * یکتاست، پس بازگشت دوباره درگاه اثر مالی دوم ندارد.
     */
    public function complete(ConsultingOrder $order, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): ConsultingOrder
    {
        $this->assertAwaitingPayment($order);

        $commission = $this->commission($order->price());
        $hold = $this->container->make(EscrowKeeper::class)->hold(new EscrowHoldRequest(
            key: $order->escrowKey(),
            payerUserId: $order->buyer_id,
            payeeUserId: $order->consultant_id,
            amount: $order->price(),
            commission: $commission,
            source: $source,
            referenceType: ConsultingOrder::class,
            referenceId: $order->uuid,
            memo: 'خدمت مشاوره '.$order->uuid,
        ));

        $order->forceFill([
            'status' => OrderStatus::AwaitingConsultant,
            'payment_source' => $source,
            'gateway_ref_id' => $gatewayRefId,
            'escrow_uuid' => $hold->uuid,
            'commission_toman' => $commission->toman,
            'paid_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new ConsultingOrderChanged($order, ConsultingOrderChanged::PAID, $order->buyer_id));

        return $order;
    }

    public function commission(Money $price): Money
    {
        if ($this->container->bound(CommissionCalculator::class)) {
            return $this->container->make(CommissionCalculator::class)->split($price, self::FLOW)->commission;
        }

        return $price->percentage($this->rateBp() / 100);
    }

    /** نرخ کمیسیون امروز به Basis Point، برای نمایش به مشاور. */
    public function rateBp(): int
    {
        if ($this->container->bound(CommissionCalculator::class)) {
            return $this->container->make(CommissionCalculator::class)->split(Money::toman(10_000), self::FLOW)->rateBp;
        }

        return (int) $this->config->get('consulting.orders.fallback_commission_bp', 1500);
    }

    private function assertAwaitingPayment(ConsultingOrder $order): void
    {
        if ($order->status !== OrderStatus::AwaitingPayment) {
            throw new RuntimeException('این درخواست در انتظار پرداخت نیست.');
        }
    }
}
