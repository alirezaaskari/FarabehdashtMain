<?php

declare(strict_types=1);

namespace App\Modules\Linking\Actions;

use App\Modules\Linking\Domain\LinkBlock;
use InvalidArgumentException;

/**
 * ثبت قاعده مسدودسازی مدیر. دست‌کم یکی از سه شرط لازم است؛ قاعده خالی
 * همه پیوندها را می‌بست.
 */
final readonly class BlockLink
{
    /** @throws InvalidArgumentException */
    public function handle(?string $phrase, ?string $sourceKey, ?string $targetKey, ?int $actorId): LinkBlock
    {
        $phrase = self::clean($phrase);
        $sourceKey = self::clean($sourceKey);
        $targetKey = self::clean($targetKey);

        if ($phrase === null && $sourceKey === null && $targetKey === null) {
            throw new InvalidArgumentException('دست‌کم یکی از عبارت، صفحه مبدأ یا صفحه مقصد را بنویسید.');
        }

        return LinkBlock::query()->create([
            'phrase' => $phrase,
            'source_key' => $sourceKey,
            'target_key' => $targetKey,
            'created_by' => $actorId,
        ]);
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
