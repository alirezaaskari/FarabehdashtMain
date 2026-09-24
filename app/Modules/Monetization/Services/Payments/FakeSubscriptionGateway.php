<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Support\Money;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use App\Support\Payments\PaymentVerificationResult;
use Illuminate\Support\Str;

/**
 * درگاه ساختگی برای تست اشتراک — بدون هیچ تماس شبکه‌ای واقعی.
 *
 * هر ماژول فروشنده نسخه خودش را دارد (تجارت و دوره‌ها هم)، چون قاعده ۱
 * اجازه نمی‌دهد یکی از دیگری import کند. اگر ماژول چهارمی فروش پیدا کند،
 * وقتش است که این سه به یک ابزار مشترک در `app/Support` تبدیل شوند.
 */
final class FakeSubscriptionGateway implements PaymentGateway
{
    /** @var array<string, Money> */
    private array $requestedAmounts = [];

    private bool $failNextVerification = false;

    public function requestPayment(PaymentRequest $request): PaymentRequestResult
    {
        $authority = 'FAKE-'.Str::random(24);
        $this->requestedAmounts[$authority] = $request->amount;

        return new PaymentRequestResult($authority, "https://fake-gateway.test/pay/{$authority}");
    }

    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult
    {
        if ($this->failNextVerification) {
            $this->failNextVerification = false;

            return PaymentVerificationResult::failure('پرداخت توسط کاربر لغو شد.');
        }

        $requested = $this->requestedAmounts[$authority] ?? null;

        if ($requested === null || ! $requested->equals($expectedAmount)) {
            return PaymentVerificationResult::failure('مبلغ تأییدشده درگاه با مبلغ اشتراک نمی‌خواند.');
        }

        return PaymentVerificationResult::success('FAKE-REF-'.Str::random(12), $expectedAmount);
    }

    public function failNextVerification(): void
    {
        $this->failNextVerification = true;
    }
}
