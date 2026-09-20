<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک محاسبه ذخیره‌شده — فقط افزودنی.
 *
 * معیار پذیرش بخش ۷ این است که محاسبه ذخیره‌شده با **نسخه فرمول لحظه ثبت**
 * بازتولید شود. برای همین شناسه و نسخه فرمول، و ورودی‌های اعتبارسنجی‌شده،
 * کنار خروجی ذخیره می‌شوند: بدون نسخه، بازتولید یعنی «امیدواریم فرمول عوض
 * نشده باشد».
 *
 * ویرایش و حذف در سطح کد منع شده‌اند. اگر کاربر عدد را اشتباه زده، محاسبه
 * تازه‌ای ثبت می‌کند؛ گزارشی که به محاسبه ارجاع داده باشد نباید زیر پایش
 * عوض شود.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $tool_slug
 * @property string $formula_id
 * @property string $formula_version
 * @property string|null $label
 * @property array<string, mixed> $inputs
 * @property array<string, mixed> $outputs
 * @property list<string> $notes
 * @property Carbon $created_at
 */
final class SavedCalculation extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'tool_calculations';

    protected $fillable = [
        'uuid',
        'user_id',
        'tool_slug',
        'formula_id',
        'formula_version',
        'label',
        'inputs',
        'outputs',
        'notes',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** @param  Builder<$this>  $query */
    public function scopeUsingFormulaVersion(Builder $query, string $formulaId, string $version): void
    {
        $query->where('formula_id', $formulaId)->where('formula_version', $version);
    }

    /** محاسبه ثبت‌شده هرگز تغییر نمی‌کند. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('محاسبه ذخیره‌شده تغییرناپذیر است؛ برای اصلاح، محاسبه تازه ثبت کنید.');
    }

    /** محاسبه ثبت‌شده هرگز حذف نمی‌شود. */
    public function delete(): bool
    {
        throw new RuntimeException('محاسبه ذخیره‌شده تغییرناپذیر است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'outputs' => 'array',
            'notes' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
