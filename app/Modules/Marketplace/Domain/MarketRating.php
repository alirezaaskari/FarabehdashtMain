<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use App\Modules\Marketplace\Domain\Enums\RatingSide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * امتیاز ۱ تا ۵ یک طرف قرارداد به طرف دیگر (DEC-85).
 *
 * امتیاز فقط وقتی «دیده‌شدنی» است که طرف دیگر هم امتیاز داده باشد یا مهلت
 * نمایش گذشته باشد، تا هیچ‌کدام امتیاز تلافی‌جویانه ندهد.
 *
 * @property int $id
 * @property int $contract_id
 * @property int $rater_user_id
 * @property int $ratee_user_id
 * @property RatingSide $side
 * @property int $stars
 * @property string|null $comment
 * @property Carbon|null $hidden_at
 * @property int|null $hidden_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketContract $contract
 */
final class MarketRating extends Model
{
    protected $table = 'market_ratings';

    protected $fillable = [
        'contract_id',
        'rater_user_id',
        'ratee_user_id',
        'side',
        'stars',
        'comment',
    ];

    /** @return BelongsTo<MarketContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(MarketContract::class, 'contract_id');
    }

    /**
     * طرف دیگر هم امتیاز داده، یا از ثبت این امتیاز چند روز گذشته.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRevealed(Builder $query, Carbon $cutoff): void
    {
        $query->where(static function (Builder $query) use ($cutoff): void {
            $query->where('created_at', '<=', $cutoff)->orWhereExists(static function (QueryBuilder $other): void {
                $other->selectRaw('1')
                    ->from('market_ratings as other')
                    ->whereColumn('other.contract_id', 'market_ratings.contract_id')
                    ->whereColumn('other.id', '!=', 'market_ratings.id');
            });
        });
    }

    /** متن برای نمایش؛ متن پنهان‌شده توسط مدیر هیچ‌جا نمی‌آید. */
    public function visibleComment(): ?string
    {
        return $this->hidden_at === null && $this->comment !== null && $this->comment !== '' ? $this->comment : null;
    }

    protected function casts(): array
    {
        return [
            'side' => RatingSide::class,
            'stars' => 'integer',
            'hidden_at' => 'datetime',
        ];
    }
}
