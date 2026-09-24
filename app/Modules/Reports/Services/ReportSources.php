<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Contracts\ReportSource;
use App\Modules\Reports\Domain\Report;
use App\Support\Reporting\ReportData;

/**
 * فهرست منابع گزارش، همان‌طور که ماژول‌ها ثبت کرده‌اند.
 */
final readonly class ReportSources
{
    /** @var array<string, ReportSource> */
    private array $sources;

    /** @param  iterable<ReportSource>  $sources */
    public function __construct(iterable $sources)
    {
        $keyed = [];

        foreach ($sources as $source) {
            $keyed[$source->key()] = $source;
        }

        $this->sources = $keyed;
    }

    /** @return list<ReportSource> */
    public function all(): array
    {
        return array_values($this->sources);
    }

    public function find(string $key): ?ReportSource
    {
        return $this->sources[$key] ?? null;
    }

    /**
     * داده تازه منبع یک پیش‌نویس، یا null اگر منبع خاموش شده یا دیگر مال
     * این کاربر نیست.
     */
    public function load(Report $report): ?ReportData
    {
        return $this->find($report->source_key)?->load($report->user_id, $report->source_references);
    }
}
