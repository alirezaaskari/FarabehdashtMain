<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Contracts\PaymentGateway;
use App\Support\Money;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;

/**
 * آداپتور زرین‌پال (REST v4) — تصمیم مدیر (DEC-10).
 *
 * تنها کلاسی که ریال می‌بیند: `Money::toRialForGateway()` هنگام ارسال،
 * `Money::fromGatewayRial()` هنگام مقایسه مبلغ بازگشتی با مبلغ انتظارمی
 * (ADR-0003). کد ۱۰۰ یعنی موفق؛ کد ۱۰۱ در تأیید یعنی «قبلاً تأیید شده» —
 * هر دو موفق تلقی می‌شوند، چون جلوگیری از اثر مالی دوم مسئولیت
 * `LedgerRecorder` (idempotency_key) است، نه این آداپتور.
 *
 * @param  array{merchant_id: ?string, sandbox: bool, timeout: int}  $config
 */
final readonly class ZarinPalGateway implements PaymentGateway
{
    private const int SUCCESS_CODE = 100;

    private const int ALREADY_VERIFIED_CODE = 101;

    /** @param array<string, mixed> $config */
    public function __construct(
        private Http $http,
        private array $config,
    ) {}

    public function requestPayment(PaymentRequest $request): PaymentRequestResult
    {
        $merchantId = $this->merchantId();

        try {
            $response = $this->http
                ->timeout($this->timeout())
                ->acceptJson()
                ->post($this->endpoint('request'), [
                    'merchant_id' => $merchantId,
                    'amount' => $request->amount->toRialForGateway(),
                    'description' => $request->description,
                    'callback_url' => $request->callbackUrl,
                    'metadata' => array_filter([
                        'order_id' => $request->orderUuid,
                        'mobile' => $request->payerMobile,
                        'email' => $request->payerEmail,
                    ]),
                ]);
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayUnavailable('اتصال به زرین‌پال برقرار نشد: '.$exception->getMessage(), previous: $exception);
        }

        $code = (int) data_get($response->json(), 'data.code');

        if ($code !== self::SUCCESS_CODE) {
            $message = (string) data_get($response->json(), 'errors.message', 'خطای ناشناخته درگاه پرداخت.');

            throw new PaymentGatewayUnavailable("درخواست پرداخت زرین‌پال رد شد: {$message}");
        }

        $authority = (string) data_get($response->json(), 'data.authority');

        return new PaymentRequestResult($authority, $this->startPayUrl($authority));
    }

    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult
    {
        $response = $this->http
            ->timeout($this->timeout())
            ->acceptJson()
            ->post($this->endpoint('verify'), [
                'merchant_id' => $this->merchantId(),
                'amount' => $expectedAmount->toRialForGateway(),
                'authority' => $authority,
            ]);

        $code = (int) data_get($response->json(), 'data.code');

        if ($code !== self::SUCCESS_CODE && $code !== self::ALREADY_VERIFIED_CODE) {
            $message = (string) data_get($response->json(), 'errors.message', 'تأیید پرداخت رد شد.');

            return PaymentVerificationResult::failure($message);
        }

        $refId = data_get($response->json(), 'data.ref_id');
        $verifiedRial = data_get($response->json(), 'data.amount', $expectedAmount->toRialForGateway());

        // مقایسه صریح مبلغ بازگشتی با مبلغ انتظارمی — قاعده ADR-0003: هر
        // ناسازگاری تراکنش را رد می‌کند، حتی اگر خود درگاه کد موفق داده باشد.
        if ((int) $verifiedRial !== $expectedAmount->toRialForGateway()) {
            return PaymentVerificationResult::failure('مبلغ تأییدشده درگاه با مبلغ سفارش نمی‌خواند.');
        }

        return PaymentVerificationResult::success((string) $refId, Money::fromGatewayRial((int) $verifiedRial));
    }

    private function merchantId(): string
    {
        $merchantId = $this->config['merchant_id'] ?? null;

        if (blank($merchantId)) {
            throw new PaymentGatewayUnavailable('شناسه پذیرنده زرین‌پال تنظیم نشده است (ZARINPAL_MERCHANT_ID).');
        }

        return (string) $merchantId;
    }

    private function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? 15);
    }

    private function isSandbox(): bool
    {
        return (bool) ($this->config['sandbox'] ?? false);
    }

    private function endpoint(string $action): string
    {
        $host = $this->isSandbox() ? 'sandbox.zarinpal.com' : 'api.zarinpal.com';

        return "https://{$host}/pg/v4/payment/{$action}.json";
    }

    private function startPayUrl(string $authority): string
    {
        $host = $this->isSandbox() ? 'sandbox.zarinpal.com' : 'www.zarinpal.com';

        return "https://{$host}/pg/StartPay/{$authority}";
    }
}
