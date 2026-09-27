<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Modules\Jobs\Domain\BankRequest;
use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
use App\Modules\Jobs\Domain\Passport;
use App\Modules\Jobs\Events\BankMembershipChanged;
use App\Modules\Jobs\Events\BankRequestAnswered;
use App\Modules\Jobs\Services\SkillPassport;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * حضور در بانک رزومه (۲۰-۵): فقط خود کارجو و فقط با انتخاب خودش (Opt-in).
 * بیرون آمدن درخواست‌های بی‌پاسخ را رد می‌کند تا اعتبار کارفرما برگردد؛
 * درخواست پذیرفته‌شده به همان اجازه‌ای که داده بود می‌ماند.
 */
final readonly class ManageBankMembership
{
    public const ABILITY = 'resume.manage';

    public function __construct(
        private SkillPassport $passports,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function join(int $userId): Passport
    {
        $passport = Passport::of($userId);

        if ($passport->in_bank) {
            return $passport;
        }

        if ($passport->city === null || $this->passports->skillIds($userId) === []) {
            throw new RuntimeException('پیش از پیوستن، در گذرنامه مهارتی شهر و دست‌کم یک مهارت را مشخص کنید؛ کارفرما فقط همین‌ها را می‌بیند.');
        }

        $passport->forceFill([
            'in_bank' => true,
            'bank_token' => $passport->bank_token ?? (string) Str::uuid7(),
            'bank_joined_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new BankMembershipChanged($passport));

        return $passport;
    }

    public function leave(int $userId): void
    {
        $passport = Passport::query()->where('user_id', $userId)->first();

        if ($passport === null || ! $passport->in_bank) {
            return;
        }

        $declined = $this->db->transaction(function () use ($passport, $userId): array {
            $passport->forceFill(['in_bank' => false])->save();

            $pending = BankRequest::query()->where('jobseeker_id', $userId)->where('status', BankRequestStatus::Pending)->with('company')->get();
            $pending->each(static fn (BankRequest $request): bool => $request->forceFill([
                'status' => BankRequestStatus::Declined,
                'answered_at' => Carbon::now(),
            ])->save());

            return $pending->all();
        });

        $this->events->dispatch(new BankMembershipChanged($passport));

        foreach ($declined as $request) {
            $this->events->dispatch(new BankRequestAnswered($request, $userId));
        }
    }
}
