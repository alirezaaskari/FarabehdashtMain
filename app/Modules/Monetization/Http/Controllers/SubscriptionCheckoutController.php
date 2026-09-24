<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Monetization\Actions\CancelSubscription;
use App\Modules\Monetization\Actions\CompleteSubscriptionPayment;
use App\Modules\Monetization\Actions\StartSubscriptionCheckout;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Modules\Monetization\Services\PlanCatalog;
use App\Modules\Monetization\Services\StreamRegistry;
use App\Modules\Monetization\Services\SubscriptionReader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * پلن → درگاه → Callback، و لغو تمدید.
 *
 * مثل ماژول تجارت: اشتراک پیش از تأیید درگاه هیچ اثر مالی ندارد و
 * `CompleteSubscriptionPayment` فقط از همین کنترلر و فقط پس از `verify()`
 * موفق صدا زده می‌شود.
 */
final readonly class SubscriptionCheckoutController
{
    public function __construct(
        private PlanCatalog $plans,
        private StreamRegistry $streams,
        private SubscriptionReader $subscriptions,
        private StartSubscriptionCheckout $startCheckout,
        private CompleteSubscriptionPayment $completePayment,
        private CancelSubscription $cancel,
        private PaymentGateway $gateway,
    ) {}

    public function store(Request $request, string $slug): RedirectResponse
    {
        abort_unless($this->streams->isEnabled(RevenueStream::ProSubscription), 404);

        $plan = $this->plans->findBySlug($slug);

        if ($plan === null) {
            throw new NotFoundHttpException('این پلن پیدا نشد.');
        }

        $user = $this->user($request);
        $result = $this->startCheckout->handle($user, $plan, $user->mobile ?? null);

        return redirect()->away($result->redirectUrl);
    }

    public function callback(Request $request): View
    {
        $period = SubscriptionPeriod::query()
            ->where('gateway_authority', (string) $request->query('Authority'))
            ->firstOrFail();

        if ($period->status === PeriodStatus::Paid) {
            return view('monetization::checkout-success', ['period' => $period]);
        }

        if ((string) $request->query('Status') !== 'OK') {
            $period->forceFill(['status' => PeriodStatus::Failed])->save();

            return view('monetization::checkout-failed', ['reason' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $period->gateway_authority, $period->price());

        if (! $verification->successful) {
            $period->forceFill(['status' => PeriodStatus::Failed])->save();

            return view('monetization::checkout-failed', ['reason' => $verification->failureReason]);
        }

        $this->completePayment->handle($period, $verification->referenceId ?? '');

        return view('monetization::checkout-success', ['period' => $period->refresh()]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $subscription = $this->subscriptions->ownSubscription($user);

        if ($subscription !== null) {
            $this->cancel->handle($subscription, (int) $user->getKey());
        }

        return redirect()->route('monetization.plans');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        assert($user instanceof User);

        return $user;
    }
}
