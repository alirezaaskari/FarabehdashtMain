<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Modules\Marketplace\Domain\MarketStrike;
use Illuminate\Contracts\Config\Repository;

/**
 * اخطارهای پیام (DEC-80): هر پیام ردشده یک اخطار است و با رسیدن اخطارهای
 * پاک‌نشده به سقف، پیشنهاد و تعریف پروژه بسته می‌شود تا مدیر باز کند.
 */
final readonly class StrikeBook
{
    public function __construct(private Repository $config) {}

    public function limit(): int
    {
        return max(1, (int) $this->config->get('marketplace.messages.strikes_limit', 3));
    }

    public function active(int $userId): int
    {
        return MarketStrike::query()->where('user_id', $userId)->whereNull('cleared_at')->count();
    }

    public function isBlocked(int $userId): bool
    {
        return $this->active($userId) >= $this->limit();
    }
}
