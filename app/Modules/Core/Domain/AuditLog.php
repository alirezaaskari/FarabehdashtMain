<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک ردیف دفتر رویداد.
 *
 * فقط افزودنی: ویرایش و حذف در سطح کد هم منع می‌شود، نه فقط با قرارداد شفاهی.
 * دفتری که بشود دستکاری‌اش کرد، دفتر نیست.
 *
 * @property int $id
 * @property string $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property int|null $actor_id
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property array<string, mixed>|null $context
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
final class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'action',
        'subject_type',
        'subject_id',
        'actor_id',
        'before',
        'after',
        'context',
        'ip',
        'user_agent',
    ];

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @param  Builder<$this>  $query */
    public function scopeForAction(Builder $query, string $action): void
    {
        $query->where('action', $action);
    }

    /** @param  Builder<$this>  $query */
    public function scopeForSubject(Builder $query, Model $subject): void
    {
        $query->where('subject_type', $subject::class)
            ->where('subject_id', (string) $subject->getKey());
    }

    /** ردیف ثبت‌شده هرگز تغییر نمی‌کند. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('دفتر رویداد فقط افزودنی است؛ ردیف ثبت‌شده ویرایش نمی‌شود.');
    }

    /** ردیف ثبت‌شده هرگز حذف نمی‌شود. */
    public function delete(): bool
    {
        throw new RuntimeException('دفتر رویداد فقط افزودنی است؛ ردیف ثبت‌شده حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
