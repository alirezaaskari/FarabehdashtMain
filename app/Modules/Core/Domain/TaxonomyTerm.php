<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * یک برچسب در یک دسته‌بندی مشترک.
 *
 * @property int $id
 * @property string $taxonomy
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $position
 * @property Collection<int, TaxonomyTerm> $children
 */
final class TaxonomyTerm extends Model
{
    protected $fillable = ['taxonomy', 'slug', 'name', 'description', 'parent_id', 'position'];

    /** @return BelongsTo<TaxonomyTerm, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<TaxonomyTerm, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    /** @param  Builder<$this>  $query */
    public function scopeOfTaxonomy(Builder $query, string $taxonomy): void
    {
        $query->where('taxonomy', $taxonomy)->orderBy('position');
    }

    /** @param  Builder<$this>  $query */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
