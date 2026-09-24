<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * پذیرش یک نسخه حقوقی توسط یک کاربر.
 *
 * @property int $id
 * @property int $user_id
 * @property int $legal_version_id
 * @property Carbon $accepted_at
 */
final class LegalAcceptance extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'legal_version_id', 'accepted_at'];

    /** @return BelongsTo<LegalVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(LegalVersion::class, 'legal_version_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }
}
