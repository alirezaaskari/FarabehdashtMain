<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Actions;

use App\Modules\Chemicals\Domain\Enums\ErrorReportTopic;
use App\Modules\Chemicals\Domain\ReportLimitReached;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Domain\SubstanceErrorReport;
use App\Modules\Chemicals\Events\SubstanceErrorReported;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * ثبت گزارش اشتباه یک کاربر درباره صفحه ماده.
 *
 * سقف روزانه هر کاربر از پنل «قیمت‌ها و زمان‌ها» عوض می‌شود. شمارنده جدا از
 * throttle مسیرهاست تا کاربر پرکار به‌خاطر گزارش، جای دیگری ۴۲۹ نگیرد.
 */
final readonly class ReportSubstanceError
{
    private const DAY = 86_400;

    public function __construct(
        private RateLimiter $limiter,
        private Config $config,
        private Dispatcher $events,
    ) {}

    /** @throws ReportLimitReached */
    public function handle(Substance $substance, int $userId, ErrorReportTopic $topic, string $message, ?string $sourceUrl): SubstanceErrorReport
    {
        $perDay = max(1, (int) $this->config->get('chemicals.reports.per_day', 5));
        $key = 'chemicals.error-report:'.$userId;

        if ($this->limiter->tooManyAttempts($key, $perDay)) {
            throw new ReportLimitReached($perDay);
        }

        $report = SubstanceErrorReport::query()->create([
            'substance_id' => $substance->id,
            'user_id' => $userId,
            'topic' => $topic,
            'message' => trim($message),
            'source_url' => $sourceUrl,
        ]);
        $report->setRelation('substance', $substance);

        $this->limiter->hit($key, self::DAY);
        $this->events->dispatch(new SubstanceErrorReported($report));

        return $report;
    }
}
