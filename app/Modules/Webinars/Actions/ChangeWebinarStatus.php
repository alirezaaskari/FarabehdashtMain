<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\Enums\WebinarStatus;
use App\Modules\Webinars\Domain\Webinar;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Modules\Webinars\Events\WebinarCancelled;
use App\Modules\Webinars\Events\WebinarStatusChanged;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * انتشار یا لغو رویداد.
 *
 * لغو، مبلغ هر ثبت‌نام پولی را با تراکنش ثبت‌شده به کیف پول ثبت‌نام‌کننده
 * برمی‌گرداند (از هر راهی که پرداخته بود) و به همه ثبت‌نام‌شده‌ها خبر می‌دهد.
 */
final readonly class ChangeWebinarStatus
{
    public function __construct(
        private LedgerRecorder $ledger,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function publish(Webinar $webinar, int $actorId): Webinar
    {
        if ($webinar->status === WebinarStatus::Cancelled) {
            throw new InvalidArgumentException('رویداد لغوشده دوباره منتشر نمی‌شود؛ رویداد تازه بسازید.');
        }

        if ($webinar->hasStarted()) {
            throw new InvalidArgumentException('زمان شروع این رویداد گذشته است.');
        }

        return $this->move($webinar, WebinarStatus::Published, $actorId);
    }

    public function cancel(Webinar $webinar, int $actorId): Webinar
    {
        if ($webinar->status === WebinarStatus::Cancelled) {
            return $webinar;
        }

        if ($webinar->hasEnded()) {
            throw new InvalidArgumentException('رویداد برگزارشده لغو نمی‌شود.');
        }

        $registrants = $this->db->transaction(function () use ($webinar): array {
            $registrants = [];

            $registrations = $webinar->registrations()->where('status', RegistrationStatus::Confirmed)->lockForUpdate()->get();

            foreach ($registrations as $registration) {
                /** @var WebinarRegistration $registration */
                if ($registration->price_toman > 0) {
                    $this->refund($registration);
                }

                $registration->forceFill(['status' => RegistrationStatus::Cancelled])->save();
                $registrants[] = $registration->user_id;
            }

            $webinar->registrations()->where('status', RegistrationStatus::Pending)->update(['status' => RegistrationStatus::Failed]);

            return $registrants;
        });

        $webinar = $this->move($webinar, WebinarStatus::Cancelled, $actorId);
        $this->events->dispatch(new WebinarCancelled($webinar, $registrants));

        return $webinar;
    }

    private function refund(WebinarRegistration $registration): void
    {
        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'webinars.registration_refunded',
            idempotencyKey: 'webinars.registration_refunded:'.$registration->uuid,
            entries: [
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Debit, $registration->price()),
                new LedgerEntryLine(LedgerAccountRef::wallet($registration->user_id), EntryDirection::Credit, $registration->price()),
            ],
            referenceType: WebinarRegistration::class,
            referenceId: $registration->uuid,
            memo: 'بازگشت ثبت‌نام رویداد لغوشده '.$registration->uuid,
        ));
    }

    private function move(Webinar $webinar, WebinarStatus $to, int $actorId): Webinar
    {
        $before = $webinar->status;
        $webinar->forceFill(['status' => $to])->save();

        $this->events->dispatch(new WebinarStatusChanged($webinar, $before, $actorId));

        return $webinar;
    }
}
