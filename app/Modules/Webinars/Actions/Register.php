<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Actions;

use App\Contracts\SalesSwitch;
use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\Webinar;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Modules\Webinars\Events\WebinarRegistered;
use App\Modules\Webinars\Services\Seats;
use App\Support\Payments\PaymentSource;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ثبت‌نام: رایگان همان لحظه قطعی می‌شود؛ پولی یک ردیف در انتظار پرداخت با
 * قیمت همین لحظه باز می‌کند که صندلی را `hold_minutes` نگه می‌دارد.
 */
final readonly class Register
{
    public function __construct(
        private Seats $seats,
        private SalesSwitch $sales,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function handle(Webinar $webinar, int $userId): WebinarRegistration
    {
        if (! $webinar->isPublished() || $webinar->hasStarted()) {
            throw new InvalidArgumentException('ثبت‌نام این رویداد باز نیست.');
        }

        if (! $webinar->isFree() && ! $this->sales->isOpen(SalesSwitch::EVENT_WEBINAR)) {
            throw new InvalidArgumentException('ثبت‌نام رویدادهای پولی در حال حاضر بسته است.');
        }

        $registration = $this->db->transaction(function () use ($webinar, $userId): WebinarRegistration {
            Webinar::query()->whereKey($webinar->id)->lockForUpdate()->first();

            if ($this->seats->isRegistered($webinar, $userId)) {
                throw new InvalidArgumentException('در این رویداد ثبت‌نام کرده‌اید.');
            }

            if ($this->seats->remaining($webinar, $userId) === 0) {
                throw new InvalidArgumentException('ظرفیت این رویداد پر شده است.');
            }

            $snapshot = $webinar->isFree()
                ? ['status' => RegistrationStatus::Confirmed, 'price_toman' => 0, 'payment_source' => PaymentSource::Free, 'gateway_authority' => null, 'paid_at' => now()]
                : ['status' => RegistrationStatus::Pending, 'price_toman' => $webinar->price_toman, 'payment_source' => PaymentSource::Gateway, 'gateway_authority' => null];

            $existing = WebinarRegistration::query()->where('webinar_id', $webinar->id)->where('user_id', $userId)->first();

            if ($existing !== null) {
                $existing->forceFill([...$snapshot, 'reminded_at' => null])->save();
                $existing->touch();

                return $existing;
            }

            return WebinarRegistration::query()->create([
                'uuid' => (string) Str::uuid7(),
                'webinar_id' => $webinar->id,
                'user_id' => $userId,
                ...$snapshot,
            ]);
        });

        if ($registration->isConfirmed()) {
            $this->events->dispatch(new WebinarRegistered($registration));
        }

        return $registration;
    }
}
