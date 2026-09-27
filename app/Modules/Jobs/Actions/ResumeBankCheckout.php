<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\LedgerRecorder;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Jobs\Domain\BankPackage;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\PaymentStatus;
use App\Modules\Jobs\Events\BankPackagePaid;
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
 * خرید بسته درخواست تماس بانک رزومه (DEC-72) با درگاه یا کیف پول.
 *
 * پول همان لحظه درآمد پلتفرم است؛ «برگشت اعتبار» پس از رد یا بی‌پاسخی
 * اعتبار بسته را برمی‌گرداند، نه پول را.
 */
final readonly class ResumeBankCheckout
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

    public function open(Company $company): BankPackage
    {
        if (! $company->isListed()) {
            throw new RuntimeException('بانک رزومه برای کارفرمایی است که صفحه شرکتش تأیید شده است.');
        }

        if (! $this->pricing->bankCharging()) {
            throw new RuntimeException('درخواست تماس فعلاً رایگان است و بسته لازم نیست.');
        }

        return $this->db->transaction(function () use ($company): BankPackage {
            BankPackage::query()->where('company_id', $company->id)->where('status', PaymentStatus::Pending)->update(['status' => PaymentStatus::Failed]);

            return BankPackage::query()->create([
                'uuid' => (string) Str::uuid7(),
                'company_id' => $company->id,
                'credits' => $this->pricing->bankCredits(),
                'price_toman' => $this->pricing->bankPrice()->toman,
                'status' => PaymentStatus::Pending,
            ]);
        });
    }

    public function startGateway(BankPackage $package, ?string $payerMobile): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $package->price(),
                description: 'بسته '.$package->credits.' درخواست تماس بانک رزومه',
                callbackUrl: route('jobs.talent.callback'),
                orderUuid: $package->uuid,
                payerMobile: $payerMobile,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            $package->forceFill(['status' => PaymentStatus::Failed])->save();

            throw $exception;
        }

        $package->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }

    public function payFromWallet(User $payer, BankPackage $package): BankPackage
    {
        $this->guard->assertAllowed();
        $this->wallet->assertCanPay((int) $payer->getKey(), $package->price());

        return $this->complete($package, null, PaymentSource::Wallet);
    }

    public function complete(BankPackage $package, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): BankPackage
    {
        if ($package->status !== PaymentStatus::Pending) {
            throw new RuntimeException('فقط بسته در انتظار پرداخت تکمیل می‌شود.');
        }

        $payerId = $package->company->user_id;

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'jobs.resume_bank_paid',
            idempotencyKey: 'jobs.resume_bank_paid:'.$package->uuid,
            entries: [
                new LedgerEntryLine($source->debitAccount($payerId), EntryDirection::Debit, $package->price()),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $package->price()),
            ],
            referenceType: BankPackage::class,
            referenceId: $package->uuid,
            memo: 'بسته بانک رزومه '.$package->uuid,
        ));

        $package->forceFill([
            'status' => PaymentStatus::Paid,
            'payment_source' => $source,
            'gateway_ref_id' => $gatewayRefId,
            'paid_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new BankPackagePaid($package, $payerId));

        return $package;
    }
}
