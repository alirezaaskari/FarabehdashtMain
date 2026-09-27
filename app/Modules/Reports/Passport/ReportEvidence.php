<?php

declare(strict_types=1);

namespace App\Modules\Reports\Passport;

use App\Contracts\PassportEvidenceSource;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Support\Passport\PassportEvidence;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * گزارش‌های صادرشده به تفکیک نوع اندازه‌گیری (شناسه فرمول سطرهای نتایج).
 * نسخه جایگزین‌شده یا باطل‌شده حساب نمی‌شود و هر گزارش برای هر نوع یک بار
 * شمرده می‌شود. نام متن گزارش یا کارفرمای آن در گذرنامه نمی‌آید.
 */
final readonly class ReportEvidence implements PassportEvidenceSource
{
    public function __construct(private Container $container) {}

    public function key(): string
    {
        return 'reports';
    }

    public function label(): string
    {
        return 'گزارش‌های اندازه‌گیری صادرشده';
    }

    public function evidence(int $userId): array
    {
        /** @var array<string, array{count: int, last: Carbon}> $types */
        $types = [];

        $reports = Report::query()->forUser($userId)->where('status', ReportStatus::Issued)->whereNotNull('issued_at')->get();

        foreach ($reports as $report) {
            $formulas = [];

            foreach ($report->document()?->data->measurements ?? [] as $measurement) {
                if ($measurement->formula !== null && $measurement->formula !== '') {
                    $formulas[strtok($measurement->formula, ' @') ?: $measurement->formula] = true;
                }
            }

            foreach (array_keys($formulas) as $formula) {
                $issued = $report->issued_at ?? $report->created_at;
                $current = $types[$formula] ?? null;
                $types[$formula] = [
                    'count' => ($current['count'] ?? 0) + 1,
                    'last' => $current === null || $issued->greaterThan($current['last']) ? $issued : $current['last'],
                ];
            }
        }

        uasort($types, static fn (array $a, array $b): int => $b['last'] <=> $a['last']);

        $evidence = [];

        foreach ($types as $formula => $row) {
            $evidence[] = new PassportEvidence(
                title: $this->title((string) $formula),
                earnedAt: $row['last'],
                detail: 'گزارش صادرشده با کد رهگیری',
                count: $row['count'],
                tags: ['formula:'.$formula],
            );
        }

        return $evidence;
    }

    private function title(string $formula): string
    {
        if (! $this->container->bound(FormulaRegistry::class)) {
            return $formula;
        }

        try {
            return $this->container->make(FormulaRegistry::class)->get($formula)->title();
        } catch (Throwable) {
            return $formula;
        }
    }
}
