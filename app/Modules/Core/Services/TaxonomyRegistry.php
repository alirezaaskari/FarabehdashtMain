<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Domain\TaxonomyTerm;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * دسته‌بندی‌های مشترک میان همه محتواها.
 *
 * فهرست دسته‌بندی‌های مجاز در `config/core.php` است، نه آزاد: اگر هر ماژول
 * بتواند دسته‌بندی تازه بسازد، شش ماه بعد پنج نام مختلف برای یک مفهوم داریم.
 */
final readonly class TaxonomyRegistry
{
    /** @param  array<string, string>  $taxonomies  کلید فنی => نام فارسی */
    public function __construct(private array $taxonomies) {}

    /** @return array<string, string> */
    public function known(): array
    {
        return $this->taxonomies;
    }

    public function label(string $taxonomy): string
    {
        return $this->taxonomies[$taxonomy] ?? $taxonomy;
    }

    /**
     * @return Collection<int, TaxonomyTerm>
     *
     * @throws InvalidArgumentException اگر دسته‌بندی ثبت‌نشده باشد
     */
    public function terms(string $taxonomy): Collection
    {
        $this->guard($taxonomy);

        return TaxonomyTerm::query()->ofTaxonomy($taxonomy)->get();
    }

    /**
     * @return Collection<int, TaxonomyTerm>
     *
     * @throws InvalidArgumentException اگر دسته‌بندی ثبت‌نشده باشد
     */
    public function tree(string $taxonomy): Collection
    {
        $this->guard($taxonomy);

        return TaxonomyTerm::query()->ofTaxonomy($taxonomy)->roots()->with('children')->get();
    }

    /**
     * برچسب‌های یک محتوا در یک دسته‌بندی.
     *
     * @return Collection<int, TaxonomyTerm>
     */
    public function termsOf(Model $model, ?string $taxonomy = null): Collection
    {
        $query = TaxonomyTerm::query()
            ->whereIn('id', fn ($sub) => $sub
                ->select('term_id')
                ->from('taxonomables')
                ->where('taxonomable_type', $model::class)
                ->where('taxonomable_id', $model->getKey()));

        if ($taxonomy !== null) {
            $this->guard($taxonomy);
            $query->where('taxonomy', $taxonomy);
        }

        return $query->orderBy('position')->get();
    }

    /**
     * @throws InvalidArgumentException اگر دسته‌بندی ثبت‌نشده باشد
     */
    private function guard(string $taxonomy): void
    {
        if (! array_key_exists($taxonomy, $this->taxonomies)) {
            throw new InvalidArgumentException("دسته‌بندی ثبت‌نشده: {$taxonomy}");
        }
    }
}
