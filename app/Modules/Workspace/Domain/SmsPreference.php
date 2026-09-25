<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain;

use App\Modules\Workspace\Domain\Enums\SmsTopic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * گروه‌هایی که کاربر پیامکشان را خاموش کرده.
 *
 * خاموش‌ها ذخیره می‌شوند، نه روشن‌ها: گروهی که بعداً اضافه شود برای همه
 * روشن است و کسی که هرگز این صفحه را باز نکرده ردیفی ندارد.
 *
 * @property int $id
 * @property int $user_id
 * @property list<string> $muted_topics
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class SmsPreference extends Model
{
    protected $table = 'workspace_sms_preferences';

    protected $fillable = ['user_id', 'muted_topics'];

    public function mutes(SmsTopic $topic): bool
    {
        return in_array($topic->value, $this->muted_topics, true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['muted_topics' => 'array'];
    }
}
