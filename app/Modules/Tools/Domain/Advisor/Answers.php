<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Advisor;

use App\Modules\Tools\Domain\Enums\Hazard;
use App\Modules\Tools\Domain\Enums\WorkStage;

/**
 * پاسخ‌های کاربر به دستیار، از رشته پرس‌وجوی نشانی.
 *
 * هر مرحله یک فرم GET است تا دستیار بدون جاوااسکریپت هم کار کند و نشانی
 * هر مرحله قابل اشتراک باشد. پاسخ نامعتبر یا بی‌مقدمه نادیده گرفته می‌شود:
 * مرحله بدون خطر معنا ندارد و موقعیت بدون مرحله هم.
 */
final readonly class Answers
{
    public function __construct(
        public ?Hazard $hazard = null,
        public ?WorkStage $stage = null,
        public ?string $situation = null,
    ) {}

    /** @param  array<string, mixed>  $query */
    public static function from(array $query): self
    {
        $hazard = Hazard::tryFrom(self::string($query, 'hazard'));
        $stage = $hazard ? WorkStage::tryFrom(self::string($query, 'stage')) : null;
        $situation = $stage ? self::string($query, 'situation') : '';

        return new self($hazard, $stage, $situation !== '' ? $situation : null);
    }

    /**
     * پارامترهای نشانی همین پاسخ‌ها — برای پیوند «برگشت» و «ویرایش».
     *
     * @return array<string, string>
     */
    public function query(): array
    {
        $query = [];

        foreach (['hazard' => $this->hazard?->value, 'stage' => $this->stage?->value, 'situation' => $this->situation] as $key => $value) {
            if ($value !== null) {
                $query[$key] = $value;
            }
        }

        return $query;
    }

    public function withoutSituation(): self
    {
        return new self($this->hazard, $this->stage);
    }

    public function withoutStage(): self
    {
        return new self($this->hazard);
    }

    /** @param  array<string, mixed>  $query */
    private static function string(array $query, string $key): string
    {
        $value = $query[$key] ?? '';

        return is_string($value) ? mb_substr(trim($value), 0, 60) : '';
    }
}
