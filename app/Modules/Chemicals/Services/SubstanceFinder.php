<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Services;

use App\Modules\Chemicals\Domain\CasNumber;
use App\Modules\Chemicals\Domain\Substance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * جست‌وجوی ماده بر اساس نام فارسی، نام انگلیسی، مترادف یا شماره CAS.
 *
 * فقط ماده‌های منتشرشده را برمی‌گرداند — پیش‌نویس صفحه عمومی ندارد و نباید
 * در نتیجه جست‌وجو هم پیدا شود.
 */
final readonly class SubstanceFinder
{
    /** @return Collection<int, Substance> */
    public function search(?string $term, int $limit = 50): Collection
    {
        $term = trim((string) $term);

        $query = Substance::query()->published()->orderBy('name_fa');

        if ($term !== '') {
            $this->applyTermFilter($query, $term);
        }

        // فهرست برای هر ردیف حد مجاز و راه ورود را نشان می‌دهد؛ بدون این،
        // پنجاه ماده صد پرس‌وجوی اضافه است.
        return $query->with(['limits', 'facts'])->limit($limit)->get();
    }

    /** بهترین تطبیق برای یک عبارت — برای مقایسه‌گر که یک ماده به ازای هر فیلد می‌خواهد. */
    public function findOne(string $term): ?Substance
    {
        $term = trim($term);

        if ($term === '') {
            return null;
        }

        if (CasNumber::isValid($term)) {
            $exact = Substance::query()->published()->where('cas_number', (string) CasNumber::fromString($term))->first();

            if ($exact !== null) {
                return $exact;
            }
        }

        $query = Substance::query()->published();
        $this->applyTermFilter($query, $term);

        return $query->orderBy('name_fa')->first();
    }

    /** @param  Builder<Substance>  $query */
    private function applyTermFilter(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        $query->where(function (Builder $q) use ($term, $like): void {
            $q->where('name_fa', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('cas_number', 'like', $like)
                ->orWhereHas('synonyms', function (Builder $s) use ($like): void {
                    $s->where('name', 'like', $like);
                });

            if (CasNumber::isValid($term)) {
                $q->orWhere('cas_number', (string) CasNumber::fromString($term));
            }
        });
    }
}
