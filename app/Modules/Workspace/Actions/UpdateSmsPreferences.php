<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Modules\Workspace\Domain\Enums\SmsTopic;
use App\Modules\Workspace\Domain\SmsPreference;

/**
 * گروه‌های روشن پیامک یک کاربر را ذخیره می‌کند؛ بقیه خاموش می‌شوند.
 */
final readonly class UpdateSmsPreferences
{
    /**
     * @param  list<SmsTopic>  $enabled
     */
    public function handle(int $userId, array $enabled): SmsPreference
    {
        $muted = array_values(array_map(
            static fn (SmsTopic $topic): string => $topic->value,
            array_filter(SmsTopic::cases(), static fn (SmsTopic $topic): bool => ! in_array($topic, $enabled, true)),
        ));

        return SmsPreference::query()->updateOrCreate(['user_id' => $userId], ['muted_topics' => $muted]);
    }
}
