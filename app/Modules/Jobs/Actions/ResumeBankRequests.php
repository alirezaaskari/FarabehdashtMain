<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Models\User;
use App\Modules\Jobs\Domain\BankRequest;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\AccessKind;
use App\Modules\Jobs\Domain\Enums\AccessSource;
use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
use App\Modules\Jobs\Domain\Passport;
use App\Modules\Jobs\Events\BankRequestAnswered;
use App\Modules\Jobs\Events\BankRequestSent;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Jobs\Services\ResumeAccessRecorder;
use App\Modules\Jobs\Services\ResumeBank;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * درخواست تماس بانک رزومه (۲۰-۵، DEC-72).
 *
 * درخواست «در انتظار» یک اعتبار نگه می‌دارد تا کارفرما بیش از بسته‌اش
 * درخواست نفرستد؛ فقط «پذیرفته» آن را خرج می‌کند و رد یا پایان مهلت آن را
 * برمی‌گرداند. هر شرکت از هر کارجو یک درخواست باز یا پاسخ‌گرفته دارد؛ پس از
 * رد، دوباره نمی‌پرسد. نام و راه تماس فقط پس از پذیرش و با دکمه «نمایش»
 * می‌آید و هر بار در دفتر دسترسی ثبت می‌شود.
 */
final readonly class ResumeBankRequests
{
    public function __construct(
        private ResumeBank $bank,
        private JobPricing $pricing,
        private ResumeAccessRecorder $access,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function send(User $employer, string $token, ?string $note): BankRequest
    {
        $company = Company::query()->where('user_id', $employer->getKey())->first();

        if ($company === null || ! $company->isListed()) {
            throw new RuntimeException('بانک رزومه برای کارفرمایی است که صفحه شرکتش تأیید شده است.');
        }

        $passport = Passport::query()->where('bank_token', $token)->where('in_bank', true)->first();

        if ($passport === null || $passport->user_id === $company->user_id) {
            throw new RuntimeException('این کارجو دیگر در بانک رزومه نیست.');
        }

        return $this->db->transaction(function () use ($employer, $company, $passport, $note): BankRequest {
            // قفل ردیف شرکت تا دو درخواست همزمان یک اعتبار را دو بار خرج نکنند.
            Company::query()->whereKey($company->id)->lockForUpdate()->first();

            $asked = BankRequest::query()
                ->where('company_id', $company->id)
                ->where('jobseeker_id', $passport->user_id)
                ->whereIn('status', [BankRequestStatus::Pending, BankRequestStatus::Accepted, BankRequestStatus::Declined])
                ->exists();

            if ($asked) {
                throw new RuntimeException('پیش‌تر از همین کارجو درخواست تماس کرده‌اید.');
            }

            $charging = $this->pricing->bankCharging();

            if ($charging && $this->bank->credits($company) < 1) {
                throw new RuntimeException('اعتبار درخواست تماس تمام شده است؛ یک بسته تازه بخرید.');
            }

            $request = BankRequest::query()->create([
                'uuid' => (string) Str::uuid7(),
                'company_id' => $company->id,
                'employer_id' => (int) $employer->getKey(),
                'jobseeker_id' => $passport->user_id,
                'note' => $note,
                'status' => BankRequestStatus::Pending,
                'charged' => $charging,
                'expires_at' => Carbon::now()->addDays($this->pricing->bankReplyDays()),
            ]);

            $this->events->dispatch(new BankRequestSent($request->setRelation('company', $company)));

            return $request;
        });
    }

    public function accept(int $userId, BankRequest $request): void
    {
        $this->answer($userId, $request, BankRequestStatus::Accepted);
    }

    public function decline(int $userId, BankRequest $request): void
    {
        $this->answer($userId, $request, BankRequestStatus::Declined);
    }

    /** درخواست‌های بی‌پاسخ پس از مهلت؛ اعتبارشان برمی‌گردد. */
    public function expireDue(?Carbon $now = null): int
    {
        $now ??= Carbon::now();
        $due = BankRequest::query()->where('status', BankRequestStatus::Pending)->where('expires_at', '<=', $now)->get();

        foreach ($due as $request) {
            $request->forceFill(['status' => BankRequestStatus::Expired])->save();
            $this->events->dispatch(new BankRequestAnswered($request, null));
        }

        return $due->count();
    }

    /** نام و راه تماس کارجوی پذیرفته؛ هر بار یک ردیف دفتر دسترسی. */
    public function reveal(User $employer, BankRequest $request): User
    {
        if ($request->employer_id !== $employer->getKey() || $request->status !== BankRequestStatus::Accepted) {
            throw new RuntimeException('راه تماس فقط پس از پذیرش کارجو دیده می‌شود.');
        }

        $this->access->record($request->jobseeker_id, (int) $employer->getKey(), $request->company_id, AccessSource::ResumeBank, AccessKind::Contact);

        return $request->jobseeker;
    }

    private function answer(int $userId, BankRequest $request, BankRequestStatus $status): void
    {
        if ($request->jobseeker_id !== $userId) {
            throw new RuntimeException('این درخواست برای شما نیست.');
        }

        if (! $request->isAnswerable()) {
            throw new RuntimeException('مهلت پاسخ این درخواست گذشته یا پیش‌تر پاسخ داده‌اید.');
        }

        $request->forceFill(['status' => $status, 'answered_at' => Carbon::now()])->save();
        $this->events->dispatch(new BankRequestAnswered($request, $userId));
    }
}
