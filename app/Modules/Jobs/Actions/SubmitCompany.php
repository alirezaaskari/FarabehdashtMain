<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Contracts\MediaLibrary;
use App\Models\User;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\CompanyDraft;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use App\Modules\Jobs\Events\CompanySubmitted;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * کارفرما صفحه شرکت را برای تأیید مدیر می‌فرستد (DEC-65).
 *
 * نسخه منتشرشده دست نمی‌خورد؛ ویرایش در `pending` می‌نشیند. نشانی صفحه پس
 * از نخستین انتشار ثابت است.
 */
final readonly class SubmitCompany
{
    public const ABILITY = 'jobs.post';

    public function __construct(
        private MediaLibrary $media,
        private Dispatcher $events,
    ) {}

    public function handle(User $user, CompanyDraft $draft, ?UploadedFile $logo = null): Company
    {
        if (! $user->can(self::ABILITY)) {
            throw new RuntimeException('صفحه شرکت فقط برای کسی است که نقش کارفرمایش تأیید شده.');
        }

        $company = self::companyOf($user);
        $slug = $company->published_at === null ? $draft->slug : (string) $company->slug;

        if (Company::query()->where('slug', $slug)->whereKeyNot($company->getKey())->exists()) {
            throw new RuntimeException('این نشانی را شرکت دیگری گرفته است.');
        }

        $logoId = $logo === null
            ? $draft->logoId
            : $this->media->storeImage($logo->getRealPath() ?: $logo->getPathname(), $logo->getClientOriginalName(), (int) $user->getKey())->id;

        $company->forceFill([
            'slug' => $slug,
            'pending' => CompanyDraft::fromArray([...$draft->toArray(), 'slug' => $slug])->withLogo($logoId)->toArray(),
            'status' => ReviewStatus::Pending,
            'submitted_at' => Carbon::now(),
            'review_note' => null,
        ])->save();

        $this->events->dispatch(new CompanySubmitted($company));

        return $company;
    }

    public static function companyOf(User $user): Company
    {
        return Company::query()->firstOrCreate(
            ['user_id' => $user->getKey()],
            ['uuid' => (string) Str::uuid7(), 'status' => ReviewStatus::Draft],
        );
    }
}
