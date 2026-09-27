<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Modules\Jobs\Domain\Enums\EntryKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک سطر «به اظهار خود کاربر»: تحصیلات، سابقه کار یا گواهی بیرونی.
 *
 * @property int $id
 * @property int $passport_id
 * @property EntryKind $kind
 * @property string $title
 * @property string|null $organization
 * @property int|null $start_year
 * @property int|null $end_year
 * @property string|null $note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Passport $passport
 */
final class PassportEntry extends Model
{
    protected $fillable = [
        'passport_id',
        'kind',
        'title',
        'organization',
        'start_year',
        'end_year',
        'note',
    ];

    /** @return BelongsTo<Passport, $this> */
    public function passport(): BelongsTo
    {
        return $this->belongsTo(Passport::class);
    }

    /** بازه سال‌ها به شمسی، مثل «۱۳۹۸ تا ۱۴۰۲» یا «از ۱۴۰۰». */
    public function years(): ?string
    {
        return match (true) {
            $this->start_year !== null && $this->end_year !== null => $this->start_year.' تا '.$this->end_year,
            $this->start_year !== null => 'از '.$this->start_year,
            $this->end_year !== null => (string) $this->end_year,
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'kind' => EntryKind::class,
            'start_year' => 'integer',
            'end_year' => 'integer',
        ];
    }
}
