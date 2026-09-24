<?php

declare(strict_types=1);

namespace App\Support\Workspace;

/**
 * نمایی از میزکار: «شخصی» یا یکی از پروفایل‌های فعال کاربر.
 *
 * `profile` مقدار رشته‌ای نوع پروفایل است (`vendor`، `instructor`…)، نه Enum
 * ماژول هویت: ماژول‌ها حق import آن را ندارند (قاعده ۱) و با رشته مقایسه
 * می‌کنند.
 */
final readonly class WorkspaceView
{
    public const PERSONAL = 'personal';

    private function __construct(
        public string $key,
        public string $label,
    ) {}

    public static function personal(): self
    {
        return new self(self::PERSONAL, 'شخصی');
    }

    public static function profile(string $type, string $label): self
    {
        return new self($type, $label);
    }

    public function isPersonal(): bool
    {
        return $this->key === self::PERSONAL;
    }

    /** آیا این نمای پروفایل مشخصی است؟ */
    public function is(string $profileType): bool
    {
        return $this->key === $profileType;
    }
}
