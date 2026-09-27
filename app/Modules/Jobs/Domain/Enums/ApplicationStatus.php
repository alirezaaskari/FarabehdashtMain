<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/**
 * وضعیت درخواست کارجو در صندوق کارفرما (۲۰-۲). هر تغییر به کارجو اعلان می‌شود.
 */
enum ApplicationStatus: string
{
    case Received = 'received';
    case Seen = 'seen';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
    case Hired = 'hired';
    // کارجو پس گرفت؛ رزومه پاک می‌شود و کارفرما دیگر چیزی نمی‌بیند.
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'رسیده',
            self::Seen => 'دیده‌شده',
            self::Shortlisted => 'در فهرست کوتاه',
            self::Rejected => 'رد',
            self::Hired => 'استخدام',
            self::Withdrawn => 'پس‌گرفته',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Shortlisted, self::Hired => 'primary',
            self::Rejected, self::Withdrawn => 'danger',
            self::Received => 'caution',
            self::Seen => 'neutral',
        };
    }

    /** هنوز تصمیم نهایی نگرفته؛ گفت‌وگو و پس‌گرفتن فقط در این حالت‌ها. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Received, self::Seen, self::Shortlisted], true);
    }

    /** گفت‌وگو تا تصمیم باز است و پس از استخدام هم برای هماهنگی می‌ماند. */
    public function allowsMessages(): bool
    {
        return $this->isOpen() || $this === self::Hired;
    }

    /**
     * وضعیت‌هایی که کارفرما خودش انتخاب می‌کند؛ «دیده‌شده» با باز کردن درخواست خودکار است.
     *
     * @return list<self>
     */
    public static function employerChoices(): array
    {
        return [self::Shortlisted, self::Rejected, self::Hired];
    }
}
