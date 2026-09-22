<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain;

use App\Contracts\Revisable;
use App\Modules\Chemicals\Domain\Enums\FactKind;
use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\Enums\LimitType;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک ماده شیمیایی در بانک.
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $cas_number
 * @property string $name_fa
 * @property string $name_en
 * @property string|null $formula
 * @property float|null $molar_mass
 * @property string|null $physical_state
 * @property string|null $description
 * @property string|null $sampling_media
 * @property string|null $sampling_flow
 * @property string|null $analysis_method
 * @property string|null $method_number
 * @property SubstanceStatus $status
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Substance extends Model implements Revisable
{
    protected $fillable = [
        'uuid', 'slug', 'cas_number', 'name_fa', 'name_en', 'formula', 'molar_mass',
        'physical_state', 'description', 'sampling_media', 'sampling_flow',
        'analysis_method', 'method_number', 'status', 'reviewed_at',
    ];

    /** @return HasMany<SubstanceSynonym, $this> */
    public function synonyms(): HasMany
    {
        return $this->hasMany(SubstanceSynonym::class)->orderBy('name');
    }

    /** @return HasMany<ExposureLimit, $this> */
    public function limits(): HasMany
    {
        return $this->hasMany(ExposureLimit::class);
    }

    /** @return HasMany<SubstanceFact, $this> */
    public function facts(): HasMany
    {
        return $this->hasMany(SubstanceFact::class)->orderBy('position');
    }

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', SubstanceStatus::Published->value);
    }

    /**
     * حدود مواجهه به ترتیب مرجع و نوع — همان ترتیبی که Enumها اعلام کرده‌اند.
     *
     * مرتب‌سازی این‌جاست و نه در پرس‌وجو، چون ترتیب **معنایی** است (مرجع
     * ایرانی اول، چون کاربر این سایت کارشناس ایرانی است) و نه الفبایی؛
     * ORDER BY روی رشته، «acgih» را اول می‌آورد.
     *
     * @return list<ExposureLimit>
     */
    public function orderedLimits(): array
    {
        $limits = $this->limits->all();

        usort($limits, static fn (ExposureLimit $a, ExposureLimit $b): int => [
            self::rank(LimitAuthority::cases(), $a->authority),
            self::rank(LimitType::cases(), $a->type),
        ] <=> [
            self::rank(LimitAuthority::cases(), $b->authority),
            self::rank(LimitType::cases(), $b->type),
        ]);

        return $limits;
    }

    /**
     * جای یک Enum در ترتیب اعلامش.
     *
     * @param  list<LimitAuthority|LimitType>  $cases
     */
    private static function rank(array $cases, LimitAuthority|LimitType $needle): int
    {
        $index = array_search($needle, $cases, strict: true);

        return $index === false ? count($cases) : $index;
    }

    /** نکته‌های یک نوع مشخص.
     *
     * @return Collection<int, SubstanceFact>
     */
    public function factsOf(FactKind $kind): Collection
    {
        /** @var Collection<int, SubstanceFact> $facts */
        $facts = $this->facts->where('kind', $kind)->values();

        return $facts;
    }

    /** نخستین حد TWA، برای ستون فهرست. */
    public function headlineLimit(): ?ExposureLimit
    {
        foreach ($this->orderedLimits() as $limit) {
            if ($limit->type === LimitType::Twa) {
                return $limit;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function revisionSnapshot(): array
    {
        return [
            'cas_number' => $this->cas_number,
            'name_fa' => $this->name_fa,
            'name_en' => $this->name_en,
            'formula' => $this->formula,
            'molar_mass' => $this->molar_mass,
            'status' => $this->status->value,
            'limits' => $this->limits->map(static fn (ExposureLimit $limit): array => [
                'authority' => $limit->authority->value,
                'type' => $limit->type->value,
                'value' => $limit->value,
                'unit' => $limit->unit,
                'reference' => $limit->referenceLine(),
            ])->all(),
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SubstanceStatus::class,
            'molar_mass' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }
}
