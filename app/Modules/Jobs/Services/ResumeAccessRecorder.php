<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Services;

use App\Modules\Jobs\Domain\Enums\AccessKind;
use App\Modules\Jobs\Domain\Enums\AccessSource;
use App\Modules\Jobs\Domain\ResumeAccessLog;

/**
 * تنها راه ثبت دیده‌شدن شماره یا رزومه کارجو (DEC-68). درخواست ۲۰-۲ و بانک
 * رزومه ۲۰-۵ هر دو از همین‌جا می‌نویسند تا فهرست کارجو کامل بماند.
 */
final readonly class ResumeAccessRecorder
{
    public function record(int $jobseekerId, int $viewerId, ?int $companyId, AccessSource $source, AccessKind $kind, ?int $applicationId = null): void
    {
        ResumeAccessLog::query()->create([
            'jobseeker_id' => $jobseekerId,
            'viewer_id' => $viewerId,
            'company_id' => $companyId,
            'application_id' => $applicationId,
            'source' => $source,
            'kind' => $kind,
        ]);
    }
}
