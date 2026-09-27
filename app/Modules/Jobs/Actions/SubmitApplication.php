<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Models\User;
use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
use App\Modules\Jobs\Domain\JobApplication;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Events\ApplicationSubmitted;
use App\Modules\Jobs\Services\JobPricing;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * ارسال درخواست کارجو برای یک آگهی زنده (۲۰-۲). همیشه رایگان است؛ تنها
 * حفاظ سقف روزانه پنل است (DEC-74). رزومه PDF روی دیسک local می‌ماند و
 * شماره و ایمیل فقط با تیک همین درخواست به کارفرما می‌رسد (DEC-68).
 */
final readonly class SubmitApplication
{
    public const ABILITY = 'jobs.applications.manage';

    public function __construct(
        private Factory $storage,
        private Repository $config,
        private JobPricing $pricing,
        private Dispatcher $events,
    ) {}

    public function handle(User $user, JobPosting $posting, string $coverNote, UploadedFile $resume, bool $shareContact): JobApplication
    {
        $userId = (int) $user->getKey();

        if (! $user->can(self::ABILITY)) {
            throw new RuntimeException('برای ارسال درخواست، نقش کارجو را در «نقش‌های من» فعال کنید؛ همان لحظه فعال می‌شود.');
        }

        if (! $posting->isLive()) {
            throw new RuntimeException('این آگهی دیگر درخواست نمی‌گیرد.');
        }

        if ($posting->company->user_id === $userId) {
            throw new RuntimeException('برای آگهی شرکت خودتان نمی‌شود درخواست فرستاد.');
        }

        if (JobApplication::query()->where('posting_id', $posting->id)->where('user_id', $userId)->exists()) {
            throw new RuntimeException('برای این آگهی پیش‌تر درخواست فرستاده‌اید.');
        }

        $limit = $this->pricing->applicationsPerDay();

        if (JobApplication::query()->where('user_id', $userId)->where('created_at', '>', Carbon::now()->subDay())->count() >= $limit) {
            throw new RuntimeException('در ۲۴ ساعت بیش از '.$limit.' درخواست نمی‌شود فرستاد؛ فردا دوباره سر بزنید.');
        }

        $uuid = (string) Str::uuid7();
        $path = $this->storage->disk('local')->putFileAs(
            (string) $this->config->get('jobs.applications.directory', 'jobs/resumes'),
            $resume,
            $uuid.'.pdf',
        );

        if ($path === false) {
            throw new RuntimeException('رزومه ذخیره نشد؛ دوباره تلاش کنید.');
        }

        $application = JobApplication::query()->create([
            'uuid' => $uuid,
            'posting_id' => $posting->id,
            'user_id' => $userId,
            'cover_note' => trim($coverNote),
            'resume_path' => $path,
            'resume_name' => Str::limit($resume->getClientOriginalName(), 180, ''),
            'resume_size_bytes' => (int) $resume->getSize(),
            'share_contact' => $shareContact,
            'status' => ApplicationStatus::Received,
        ]);

        $application->setRelation('posting', $posting);
        $this->events->dispatch(new ApplicationSubmitted($application));

        return $application;
    }
}
