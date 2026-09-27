<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\LedgerRecorder;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Jobs\Domain\Enums\PaymentStatus;
use App\Modules\Jobs\Domain\Enums\PostingState;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\PostingPayment;
use App\Modules\Jobs\Events\PostingPublished;
use App\Modules\Jobs\Services\JobPricing;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * انتشار و تمدید آگهی تأییدشده (DEC-63).
 *
 * هر دوره یک ردیف پرداخت با قیمت و مدت همان لحظه است. دوره رایگان («اولین
 * آگهی» یا کلید درآمد خاموش) بی درگاه و بی دفتر کل ثبت می‌شود؛ دوره پولی
 * همان مسیر درگاه یا کیف پول است و به درآمد پلتفرم می‌رود. تمدید آگهی زنده
 * از پایان اعتبار فعلی جلو می‌رود تا روزی نسوزد.
 */
final readonly class PostingCheckout
{
    public function __construct(
        private JobPricing $pricing,
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
        private WalletCheckout $wallet,
        private LedgerRecorder $ledger,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function open(JobPosting $posting): PostingPayment
    {
        if (! in_array($posting->state(), [PostingState::AwaitingPayment, PostingState::Live, PostingState::Expired], true)) {
            throw new RuntimeException('فقط آگهی تأییدشده منتشر یا تمدید می‌شود.');
        }

        $price = $this->pricing->priceFor($posting->company);

        return $this->db->transaction(function () use ($posting, $price): PostingPayment {
            $posting->payments()->where('status', PaymentStatus::Pending)->update(['status' => PaymentStatus::Failed]);

            return PostingPayment::query()->create([
                'uuid' => (string) Str::uuid7(),
                'posting_id' => $posting->id,
                'price_toman' => $price->toman,
                'days' => $this->pricing->days(),
                'is_free' => $price->isZero(),
                'status' => PaymentStatus::Pending,
            ]);
        });
    }

    /** دوره رایگان همان لحظه منتشر می‌شود؛ درگاه و کیف پول در کار نیست. */
    public function publishFree(PostingPayment $payment, int $actorId): PostingPayment
    {
        if (! $payment->is_free) {
            throw new RuntimeException('این دوره رایگان نیست.');
        }

        return $this->complete($payment, null, null, $actorId);
    }

    public function startGateway(PostingPayment $payment, ?string $payerMobile): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $payment->price(),
                description: 'انتشار آگهی شغلی «'.$payment->posting->title.'»',
                callbackUrl: route('jobs.employer.postings.callback'),
                orderUuid: $payment->uuid,
                payerMobile: $payerMobile,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            $payment->forceFill(['status' => PaymentStatus::Failed])->save();

            throw $exception;
        }

        $payment->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }

    public function payFromWallet(User $payer, PostingPayment $payment): PostingPayment
    {
        $this->guard->assertAllowed();
        $this->wallet->assertCanPay((int) $payer->getKey(), $payment->price());

        return $this->complete($payment, null, PaymentSource::Wallet, (int) $payer->getKey());
    }

    public function complete(PostingPayment $payment, ?string $gatewayRefId, ?PaymentSource $source = PaymentSource::Gateway, ?int $actorId = null): PostingPayment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw new RuntimeException('فقط دوره در انتظار پرداخت تکمیل می‌شود.');
        }

        $posting = $payment->posting;
        $payerId = $posting->company->user_id;

        if (! $payment->is_free && $source !== null) {
            $this->ledger->record(new LedgerTransactionRequest(
                kind: 'jobs.posting_paid',
                idempotencyKey: 'jobs.posting_paid:'.$payment->uuid,
                entries: [
                    new LedgerEntryLine($source->debitAccount($payerId), EntryDirection::Debit, $payment->price()),
                    new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $payment->price()),
                ],
                referenceType: PostingPayment::class,
                referenceId: $payment->uuid,
                memo: 'انتشار آگهی شغلی '.$posting->uuid,
            ));
        }

        $this->db->transaction(function () use ($payment, $posting, $gatewayRefId, $source): void {
            $now = Carbon::now();
            $from = $posting->state($now) === PostingState::Live && $posting->expires_at !== null ? $posting->expires_at->copy() : $now;

            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'gateway_ref_id' => $gatewayRefId,
                'payment_source' => $payment->is_free ? null : $source,
                'paid_at' => $now,
            ])->save();

            $posting->forceFill([
                'published_at' => $posting->published_at ?? $now,
                'expires_at' => $from->addDays($payment->days),
            ])->save();
        });

        $this->events->dispatch(new PostingPublished($posting->refresh(), $payment->refresh(), $actorId ?? $payerId));

        return $payment;
    }
}
