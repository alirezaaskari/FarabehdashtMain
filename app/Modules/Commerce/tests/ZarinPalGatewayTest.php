<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Modules\Commerce\Services\Payments\ZarinPalGateway;
use App\Support\Money;
use App\Support\Payments\PaymentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http as HttpFacade;
use RuntimeException;
use Tests\TestCase;

/**
 * آداپتور زرین‌پال — بدون هیچ تماس شبکه‌ای واقعی؛ فقط رفت‌وبرگشت تومان↔ریال
 * و مقایسه مبلغ (ADR-0003) سنجیده می‌شود.
 */
final class ZarinPalGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(bool $sandbox = false): ZarinPalGateway
    {
        return new ZarinPalGateway($this->app->make(Http::class), [
            'merchant_id' => 'test-merchant',
            'sandbox' => $sandbox,
            'timeout' => 5,
        ]);
    }

    public function test_it_converts_toman_to_rial_when_requesting_payment(): void
    {
        HttpFacade::fake([
            'api.zarinpal.com/*' => HttpFacade::response([
                'data' => ['code' => 100, 'authority' => 'AUTH123'],
                'errors' => [],
            ]),
        ]);

        $result = $this->gateway()->requestPayment(new PaymentRequest(
            amount: Money::toman(50_000),
            description: 'خرید قالب گزارش',
            callbackUrl: 'https://farabehdasht.com/commerce/callback',
            orderUuid: 'order-uuid-1',
        ));

        $this->assertSame('AUTH123', $result->authority);
        $this->assertSame('https://www.zarinpal.com/pg/StartPay/AUTH123', $result->redirectUrl);

        HttpFacade::assertSent(function (Request $request): bool {
            $this->assertSame(500_000, $request['amount']); // ۵۰٬۰۰۰ تومان × ۱۰
            $this->assertSame('test-merchant', $request['merchant_id']);

            return true;
        });
    }

    public function test_sandbox_uses_sandbox_host(): void
    {
        HttpFacade::fake([
            'sandbox.zarinpal.com/*' => HttpFacade::response([
                'data' => ['code' => 100, 'authority' => 'AUTH-SANDBOX'],
                'errors' => [],
            ]),
        ]);

        $result = $this->gateway(sandbox: true)->requestPayment(new PaymentRequest(
            amount: Money::toman(1_000),
            description: 'تست',
            callbackUrl: 'https://farabehdasht.com/commerce/callback',
            orderUuid: 'order-uuid-2',
        ));

        $this->assertStringStartsWith('https://sandbox.zarinpal.com/pg/StartPay/', $result->redirectUrl);
    }

    public function test_a_rejected_payment_request_throws(): void
    {
        HttpFacade::fake([
            'api.zarinpal.com/*' => HttpFacade::response([
                'data' => [],
                'errors' => ['code' => -9, 'message' => 'اطلاعات ارسالی معتبر نیست'],
            ]),
        ]);

        $this->expectException(RuntimeException::class);

        $this->gateway()->requestPayment(new PaymentRequest(
            amount: Money::toman(1_000),
            description: 'تست',
            callbackUrl: 'https://farabehdasht.com/commerce/callback',
            orderUuid: 'order-uuid-3',
        ));
    }

    public function test_verify_succeeds_when_amount_matches(): void
    {
        HttpFacade::fake([
            'api.zarinpal.com/*' => HttpFacade::response([
                'data' => ['code' => 100, 'ref_id' => 998877, 'amount' => 500_000],
                'errors' => [],
            ]),
        ]);

        $result = $this->gateway()->verify('AUTH123', Money::toman(50_000));

        $this->assertTrue($result->successful);
        $this->assertSame('998877', $result->referenceId);
        $this->assertSame(50_000, $result->amount?->toman);
    }

    public function test_verify_fails_when_the_gateways_amount_does_not_match_the_order(): void
    {
        // درگاه می‌گوید موفق بوده، ولی مبلغ بازگشتی با مبلغ سفارش نمی‌خواند —
        // طبق ADR-0003 این باید رد شود، نه پذیرفته.
        HttpFacade::fake([
            'api.zarinpal.com/*' => HttpFacade::response([
                'data' => ['code' => 100, 'ref_id' => 1, 'amount' => 100_000],
                'errors' => [],
            ]),
        ]);

        $result = $this->gateway()->verify('AUTH123', Money::toman(50_000));

        $this->assertFalse($result->successful);
    }

    public function test_verify_treats_already_verified_as_success(): void
    {
        HttpFacade::fake([
            'api.zarinpal.com/*' => HttpFacade::response([
                'data' => ['code' => 101, 'ref_id' => 5, 'amount' => 500_000],
                'errors' => [],
            ]),
        ]);

        $result = $this->gateway()->verify('AUTH123', Money::toman(50_000));

        $this->assertTrue($result->successful);
    }

    public function test_verify_fails_when_the_gateway_rejects(): void
    {
        HttpFacade::fake([
            'api.zarinpal.com/*' => HttpFacade::response([
                'data' => [],
                'errors' => ['code' => -50, 'message' => 'مبلغ پرداخت شده با مبلغ درخواستی متفاوت است'],
            ]),
        ]);

        $result = $this->gateway()->verify('AUTH123', Money::toman(50_000));

        $this->assertFalse($result->successful);
    }

    public function test_a_missing_merchant_id_throws(): void
    {
        $this->expectException(RuntimeException::class);

        (new ZarinPalGateway($this->app->make(Http::class), ['merchant_id' => null]))
            ->requestPayment(new PaymentRequest(
                amount: Money::toman(1_000),
                description: 'تست',
                callbackUrl: 'https://farabehdasht.com/commerce/callback',
                orderUuid: 'order-uuid-4',
            ));
    }
}
