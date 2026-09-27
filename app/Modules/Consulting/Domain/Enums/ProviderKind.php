<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain\Enums;

/**
 * صاحب یک صفحه عمومی: مشاور (خدمت می‌فروشد) یا آزمایشگاه (فقط معرفی و
 * درخواست تماس، DEC-58).
 */
enum ProviderKind: string
{
    case Consultant = 'consultant';
    case Laboratory = 'laboratory';

    public function label(): string
    {
        return match ($this) {
            self::Consultant => 'مشاور',
            self::Laboratory => 'آزمایشگاه',
        };
    }

    /** مسیر صفحه عمومی هر نوع. */
    public function route(): string
    {
        return match ($this) {
            self::Consultant => 'consulting.show',
            self::Laboratory => 'consulting.labs.show',
        };
    }

    /** نشانی صفحه عمومی، برای پیش‌نمایش نشانی در فرم و صف مدیر. */
    public function pathPrefix(): string
    {
        return match ($this) {
            self::Consultant => '/consultants/',
            self::Laboratory => '/labs/',
        };
    }
}
