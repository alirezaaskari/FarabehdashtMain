<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * حساب بانکی مقصد تسویه یک فروشنده یا مدرس؛ هر کاربر یکی.
 *
 * @property int $id
 * @property int $user_id
 * @property string $sheba
 * @property string $holder_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class VendorBankAccount extends Model
{
    protected $fillable = ['user_id', 'sheba', 'holder_name'];

    protected $hidden = ['sheba'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sheba(): Sheba
    {
        return Sheba::fromInput($this->sheba);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sheba' => 'encrypted'];
    }
}
