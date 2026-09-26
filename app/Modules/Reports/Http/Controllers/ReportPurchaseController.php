<?php

declare(strict_types=1);

namespace App\Modules\Reports\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Modules\Reports\Actions\CompleteReportPurchase;
use App\Modules\Reports\Actions\OpenReportPurchase;
use App\Modules\Reports\Actions\PayReportFromWallet;
use App\Modules\Reports\Actions\StartReportCheckout;
use App\Modules\Reports\Domain\Enums\ReportPurchaseStatus;
use App\Modules\Reports\Domain\ReportPurchase;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * خرید تکی صدور یک گزارش (DEC-44) — از مرحله بازبینی، با درگاه یا کیف پول.
 *
 * هر دو مسیر به همان مرحله بازبینی برمی‌گردند: خرید فقط اجازه صدور است و
 * صدور همچنان کار خود کاربر است، با همان تأیید هشدار کالیبراسیون.
 */
final readonly class ReportPurchaseController
{
    use FindsReports;

    private const string PAID = 'پرداخت انجام شد. حالا می‌توانید گزارش را صادر کنید.';

    public function __construct(
        private OpenReportPurchase $open,
        private StartReportCheckout $startCheckout,
        private PayReportFromWallet $payFromWallet,
        private CompleteReportPurchase $complete,
        private PaymentGateway $gateway,
    ) {}

    public function store(Request $request, string $uuid): RedirectResponse
    {
        $report = $this->report($request, $uuid);
        $source = PaymentSource::requested($request);

        try {
            $purchase = $this->open->handle($report);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['purchase' => $exception->getMessage()]);
        }

        if ($source === PaymentSource::Wallet) {
            try {
                $this->payFromWallet->handle($purchase);
            } catch (InsufficientWalletBalance $exception) {
                $purchase->forceFill(['status' => ReportPurchaseStatus::Failed])->save();

                return back()->withErrors(['payment' => $exception->getMessage()]);
            }

            return redirect()->route('reports.review', $report->uuid)->with('status', self::PAID);
        }

        try {
            $result = $this->startCheckout->handle($purchase, $this->user($request)->mobile);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return back()->withErrors(['purchase' => PaymentGatewayUnavailable::USER_MESSAGE]);
        }

        return redirect()->away($result->redirectUrl);
    }

    /** زرین‌پال بدون نشست کاربر هم ممکن است برگردد؛ مسیر عمداً بیرون از auth است. */
    public function callback(Request $request): RedirectResponse
    {
        $purchase = ReportPurchase::query()
            ->where('gateway_authority', (string) $request->query('Authority'))
            ->with('report')
            ->firstOrFail();

        $review = redirect()->route('reports.review', $purchase->report->uuid);

        if ($purchase->isPaid()) {
            return $review->with('status', self::PAID);
        }

        if ((string) $request->query('Status') !== 'OK') {
            $purchase->forceFill(['status' => ReportPurchaseStatus::Failed])->save();

            return $review->withErrors(['purchase' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $purchase->gateway_authority, $purchase->price());

        if (! $verification->successful) {
            $purchase->forceFill(['status' => ReportPurchaseStatus::Failed])->save();

            return $review->withErrors(['purchase' => $verification->failureReason ?? 'پرداخت تأیید نشد.']);
        }

        $this->complete->handle($purchase, $verification->referenceId ?? '');

        return $review->with('status', self::PAID);
    }
}
