<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Models\User;
use App\Modules\Jobs\Domain\Enums\EntryKind;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * گذرنامه مهارتی یک کاربر (بخش ۲۰-۳).
 *
 * صفحه اشتراکی با `share_token` ثابت است، پیش‌فرض خاموش و حتی روشن هم
 * noindex (DEC-69). گذرنامه گواهی یا مدرک رسمی نیست.
 *
 * @property int $id
 * @property int $user_id
 * @property string $share_token
 * @property bool $shared
 * @property string|null $headline
 * @property string|null $province
 * @property string|null $city
 * @property int|null $experience_years
 * @property bool $in_bank
 * @property string|null $bank_token
 * @property Carbon|null $bank_joined_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Collection<int, PassportEntry> $entries
 */
final class Passport extends Model
{
    protected $fillable = [
        'user_id',
        'share_token',
        'shared',
        'headline',
        'province',
        'city',
        'experience_years',
        'in_bank',
        'bank_token',
        'bank_joined_at',
    ];

    public static function of(int $userId): self
    {
        return self::query()->firstOrCreate(['user_id' => $userId], ['share_token' => (string) Str::uuid7()]);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PassportEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(PassportEntry::class)->orderByDesc('end_year')->orderByDesc('start_year')->orderByDesc('id');
    }

    /** @return Collection<int, PassportEntry> */
    public function entriesOf(EntryKind $kind): Collection
    {
        return $this->entries->filter(static fn (PassportEntry $entry): bool => $entry->kind === $kind)->values();
    }

    protected function casts(): array
    {
        return [
            'shared' => 'boolean',
            'experience_years' => 'integer',
            'in_bank' => 'boolean',
            'bank_joined_at' => 'datetime',
        ];
    }
}
