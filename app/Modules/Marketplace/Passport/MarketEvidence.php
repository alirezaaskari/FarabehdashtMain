<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Passport;

use App\Contracts\PassportEvidenceSource;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Services\MarketCatalog;
use App\Support\Passport\PassportEvidence;
use Illuminate\Support\Carbon;

/**
 * پروژه‌های تحویل‌شده در بازار پروژه، به تفکیک نوع کار (بخش ۲۱-۶).
 *
 * فقط قرارداد تمام‌شده که کارفرما همه مرحله‌هایش را آزاد کرده شمرده
 * می‌شود. نام پروژه و کارفرما در گذرنامه نمی‌آید (DEC-84).
 */
final readonly class MarketEvidence implements PassportEvidenceSource
{
    public function __construct(private MarketCatalog $catalog) {}

    public function key(): string
    {
        return 'market';
    }

    public function label(): string
    {
        return 'پروژه‌های تحویل‌شده در بازار پروژه';
    }

    public function evidence(int $userId): array
    {
        $contracts = MarketContract::query()
            ->where('provider_user_id', $userId)
            ->where('status', ContractStatus::Completed)
            ->whereNotNull('completed_at')
            ->with('project:id,service')
            ->get();

        /** @var array<string, array{count: int, last: Carbon}> $services */
        $services = [];

        foreach ($contracts as $contract) {
            $service = $contract->project->service;
            $completed = $contract->completed_at ?? $contract->updated_at;
            $current = $services[$service] ?? null;
            $services[$service] = [
                'count' => ($current['count'] ?? 0) + 1,
                'last' => $current === null || $completed->greaterThan($current['last']) ? $completed : $current['last'],
            ];
        }

        uasort($services, static fn (array $a, array $b): int => $b['last'] <=> $a['last']);

        $evidence = [];

        foreach ($services as $service => $row) {
            $evidence[] = new PassportEvidence(
                title: $this->catalog->serviceName((string) $service) ?? (string) $service,
                earnedAt: $row['last'],
                detail: 'پروژه تحویل‌شده با پرداخت امانی در فرابهداشت',
                count: $row['count'],
                tags: ['market-service:'.$service],
            );
        }

        return $evidence;
    }
}
