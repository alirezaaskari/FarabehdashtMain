<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use App\Modules\Workspace\Domain\Enums\LegalChange;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use App\Modules\Workspace\Domain\LegalAcceptance;
use App\Modules\Workspace\Domain\LegalVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * نسخه‌های صفحات حقوقی و وضعیت پذیرش هر کاربر.
 *
 * قاعده پذیرش دوباره (DEC-24): کاربر وقتی متوقف می‌شود که تازه‌ترین نسخه
 * «اساسیِ» درحال‌اثر یک سند را، یا نسخه‌ای بعد از آن را، نپذیرفته باشد.
 * نسخه جزئی بعد از آخرین پذیرش کسی را متوقف نمی‌کند.
 */
final readonly class LegalLibrary
{
    public function current(LegalDocument $document, ?Carbon $at = null): ?LegalVersion
    {
        return LegalVersion::query()->of($document)->inEffect($at)->orderByDesc('version')->first();
    }

    /** @return Collection<int, LegalVersion> تازه‌ترین اول */
    public function history(LegalDocument $document, ?Carbon $at = null): Collection
    {
        return LegalVersion::query()->of($document)->inEffect($at)->orderByDesc('version')->get();
    }

    public function find(LegalDocument $document, int $version, ?Carbon $at = null): ?LegalVersion
    {
        return LegalVersion::query()->of($document)->inEffect($at)->where('version', $version)->first();
    }

    public function nextVersionNumber(LegalDocument $document): int
    {
        return (int) LegalVersion::query()->of($document)->max('version') + 1;
    }

    /**
     * نسخه‌هایی که کاربر باید پیش از ادامه بپذیرد — همان نسخه جاری هر سندی
     * که پذیرشش عقب افتاده.
     *
     * @return list<LegalVersion>
     */
    public function pendingFor(int $userId, ?Carbon $at = null): array
    {
        $pending = [];

        foreach (LegalDocument::acceptable() as $document) {
            $required = LegalVersion::query()
                ->of($document)
                ->inEffect($at)
                ->where('change', LegalChange::Material->value)
                ->orderByDesc('version')
                ->first();

            if ($required === null || $this->hasAccepted($userId, $document, $required->version)) {
                continue;
            }

            $current = $this->current($document, $at);

            if ($current !== null) {
                $pending[] = $current;
            }
        }

        return $pending;
    }

    /** آیا کاربر این نسخه یا نسخه‌ای بعد از آن را پذیرفته است. */
    private function hasAccepted(int $userId, LegalDocument $document, int $version): bool
    {
        return LegalAcceptance::query()
            ->where('user_id', $userId)
            ->whereHas('version', function ($query) use ($document, $version): void {
                $query->where('document', $document->value)->where('version', '>=', $version);
            })
            ->exists();
    }
}
