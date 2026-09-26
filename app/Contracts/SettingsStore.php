<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * تنظیم عددی که مدیر از پنل عوض می‌کند، برای ماژول‌هایی که جدول تنظیمات Core
 * را نمی‌شناسند (قاعده ۱).
 *
 * Core با `SettingsRepository` پیاده‌اش می‌کند. ماژولی که این در را می‌پرسد
 * باید نبودنش را هم تاب بیاورد و به مقدار پیش‌فرض config خودش برگردد.
 */
interface SettingsStore
{
    public function integer(string $key, int $default = 0): int;

    public function set(string $key, mixed $value, ?string $group = null, ?string $description = null): void;
}
