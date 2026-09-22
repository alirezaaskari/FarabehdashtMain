<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain;

use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\Enums\LimitType;
use App\Support\Measurement\MeasurementNumber;
use App\Support\PersianNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * یک حد مواجهه از یک مرجع مشخص.
 *
 * @property int $id
 * @property int $substance_id
 * @property LimitAuthority $authority
 * @property LimitType $type
 * @property float $value
 * @property string $unit
 * @property string|null $note
 * @property string|null $reference_title
 * @property string|null $reference_edition
 * @property int|null $reference_year
 */
final class ExposureLimit extends Model
{
    protected $fillable = [
        'substance_id', 'authority', 'type', 'value', 'unit', 'note',
        'reference_title', 'reference_edition', 'reference_year',
    ];

    /** @return BelongsTo<Substance, $this> */
    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }

    /**
     * آیا این حد منبع نسخه‌دار دارد.
     *
     * عنوان به‌تنهایی کافی نیست: «ACGIH TLVs» بدون سال، به هر ویرایشی از
     * ۱۹۴۶ تا امسال اشاره می‌کند و مقدارها بینشان فرق دارند. همین است که
     * اکشن انتشار بررسی می‌کند.
     */
    public function hasVersionedReference(): bool
    {
        return $this->reference_title !== null
            && trim($this->reference_title) !== ''
            && ($this->reference_edition !== null || $this->reference_year !== null);
    }

    /** مقدار با ارقام لاتین — مقدار اندازه‌گیری است و نه عدد در جمله. */
    public function formattedValue(): string
    {
        return MeasurementNumber::format($this->value);
    }

    /** خط استناد کامل: «ACGIH TLVs and BEIs — ed. 2023» یا null. */
    public function referenceLine(): ?string
    {
        if ($this->reference_title === null || trim($this->reference_title) === '') {
            return null;
        }

        $latin = preg_match('/\p{Arabic}/u', $this->reference_title) !== 1;

        $version = array_filter([
            $this->reference_edition === null
                ? null
                : ($latin ? 'ed. '.$this->reference_edition : 'ویرایش '.PersianNumber::digitsOnly($this->reference_edition)),
            $this->reference_year === null
                ? null
                : ($latin ? (string) $this->reference_year : PersianNumber::digitsOnly($this->reference_year)),
        ]);

        if ($version === []) {
            return $this->reference_title;
        }

        return $this->reference_title.' — '.implode($latin ? ', ' : '، ', $version);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'authority' => LimitAuthority::class,
            'type' => LimitType::class,
            'value' => 'float',
            'reference_year' => 'integer',
        ];
    }
}
