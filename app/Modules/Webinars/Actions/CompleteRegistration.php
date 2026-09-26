<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Modules\Webinars\Events\WebinarRegistered;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Payments\PaymentSource;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * اثر مالی ثبت‌نام پولی: بدهکار خزانه (درگاه) یا کیف پول، بستانکار درآمد
 * پلتفرم. پول مدرس بیرون از سایت و با قرارداد خودش تسویه می‌شود. کلید
 * یکتایی دفتر کل بازگشت تکراری درگاه را بی‌اثر می‌کند.
 */
final readonly class CompleteRegistration
{
    public function __construct(
        private LedgerRecorder $ledger,
        private Dispatcher $events,
    ) {}

    public function handle(WebinarRegistration $registration, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): WebinarRegistration
    {
        if ($registration->status !== RegistrationStatus::Pending) {
            throw new RuntimeException('فقط ثبت‌نام در انتظار پرداخت تکمیل می‌شود.');
        }

        if ($source === PaymentSource::Free || $registration->price()->isZero()) {
            throw new RuntimeException('ثبت‌نام رایگان از این مسیر نمی‌گذرد.');
        }

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'webinars.registration_paid',
            idempotencyKey: 'webinars.registration_paid:'.$registration->uuid,
            entries: [
                new LedgerEntryLine($source->debitAccount($registration->user_id), EntryDirection::Debit, $registration->price()),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $registration->price()),
            ],
            referenceType: WebinarRegistration::class,
            referenceId: $registration->uuid,
            memo: 'ثبت‌نام رویداد '.$registration->uuid,
        ));

        $registration->forceFill([
            'status' => RegistrationStatus::Confirmed,
            'payment_source' => $source,
            'gateway_ref_id' => $gatewayRefId,
            'paid_at' => now(),
        ])->save();

        $this->events->dispatch(new WebinarRegistered($registration));

        return $registration->refresh();
    }
}
