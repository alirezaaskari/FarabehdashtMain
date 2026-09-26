<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Modules\Bundles\Actions\CompleteBundlePurchase;
use App\Modules\Bundles\Actions\OpenBundlePurchase;
use App\Modules\Bundles\Actions\PayBundleFromWallet;
use App\Modules\Bundles\Actions\StartBundleCheckout;
use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Bundles\Domain\BundlePurchase;
use App\Modules\Bundles\Domain\Enums\PurchaseStatus;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/** خرید بسته راه‌حل با درگاه یا کیف پول؛ هر دو به صفحه بسته برمی‌گردند. */
final readonly class BundlePurchaseController
{
    private const string PAID = 'خرید انجام شد. همه اجزای بسته در حساب شما فعال شد.';

    public function __construct(
        private OpenBundlePurchase $open,
        private StartBundleCheckout $startCheckout,
        private PayBundleFromWallet $payFromWallet,
        private CompleteBundlePurchase $complete,
        private PaymentGateway $gateway,
    ) {}

    public function store(Request $request, Bundle $bundle): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        try {
            $purchase = $this->open->handle($bundle, (int) $user->getKey());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['purchase' => $exception->getMessage()]);
        }

        if (PaymentSource::requested($request) === PaymentSource::Wallet) {
            try {
                $this->payFromWallet->handle($purchase);
            } catch (InsufficientWalletBalance $exception) {
                $purchase->forceFill(['status' => PurchaseStatus::Failed])->save();

                return back()->withErrors(['payment' => $exception->getMessage()]);
            }

            return redirect()->route('bundles.show', $bundle->slug)->with('status', self::PAID);
        }

        try {
            $result = $this->startCheckout->handle($purchase, $user->mobile ?? null);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return back()->withErrors(['purchase' => PaymentGatewayUnavailable::USER_MESSAGE]);
        }

        return redirect()->away($result->redirectUrl);
    }

    /** زرین‌پال بدون نشست کاربر هم ممکن است برگردد؛ مسیر عمداً بیرون از auth است. */
    public function callback(Request $request): RedirectResponse
    {
        $purchase = BundlePurchase::query()
            ->where('gateway_authority', (string) $request->query('Authority'))
            ->with('bundle')
            ->firstOrFail();

        $back = redirect()->route('bundles.show', $purchase->bundle->slug);

        if ($purchase->isPaid()) {
            return $back->with('status', self::PAID);
        }

        if ((string) $request->query('Status') !== 'OK') {
            $purchase->forceFill(['status' => PurchaseStatus::Failed])->save();

            return $back->withErrors(['purchase' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $purchase->gateway_authority, $purchase->price());

        if (! $verification->successful) {
            $purchase->forceFill(['status' => PurchaseStatus::Failed])->save();

            return $back->withErrors(['purchase' => $verification->failureReason ?? 'پرداخت تأیید نشد.']);
        }

        $this->complete->handle($purchase, $verification->referenceId ?? '');

        return $back->with('status', self::PAID);
    }
}
