<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Domain\Setting;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * خواندن و نوشتن تنظیمات مدیر.
 *
 * همه تنظیمات یک‌جا کش می‌شوند، چون تعدادشان کم است و تقریباً هر درخواست به
 * چند تایشان نیاز دارد. هر نوشتن، کش را باطل می‌کند.
 *
 * `default` جدی است: تنظیمی که هنوز در دیتابیس نیست نباید باعث خطا شود، چون
 * استقرار تازه همیشه قبل از پرشدن جدول تنظیمات بالا می‌آید.
 */
final readonly class SettingsRepository
{
    private const CACHE_KEY = 'core.settings';

    public function __construct(private Cache $cache) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * نوشتن یک تنظیم.
     *
     * `group` و `description` فقط وقتی داده شوند نوشته می‌شوند. به‌روزرسانی
     * ساده مقدار نباید دسته‌بندی و توضیحی را که مدیر قبلاً گذاشته پاک کند.
     */
    public function set(string $key, mixed $value, ?string $group = null, ?string $description = null): void
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);

        $setting->fill([
            'value' => $value,
            'group' => $group ?? $setting->group ?? 'general',
            'description' => $description ?? $setting->description,
        ])->save();

        $this->forget();
    }

    public function forget(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        /** @var array<string, mixed> $settings */
        $settings = $this->cache->rememberForever(
            self::CACHE_KEY,
            static fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );

        return $settings;
    }
}
