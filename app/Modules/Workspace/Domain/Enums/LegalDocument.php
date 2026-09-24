<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain\Enums;

/**
 * صفحات حقوقی سایت.
 *
 * `slug` بخشی از نشانی عمومی است و هرگز عوض نمی‌شود — به این صفحه‌ها از
 * رسید، ایمیل پشتیبانی و گزارش PDF پیوند داده می‌شود.
 */
enum LegalDocument: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case Disclaimer = 'disclaimer';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::Terms => 'قوانین استفاده',
            self::Privacy => 'حریم خصوصی',
            self::Disclaimer => 'سلب مسئولیت',
            self::Contact => 'تماس با ما',
        };
    }

    /**
     * آیا کاربر باید این سند را بپذیرد.
     *
     * قوانین و حریم خصوصی قرارداد با کاربرند؛ سلب مسئولیت و تماس اطلاع‌رسانی‌اند
     * و پذیرفتنشان معنایی ندارد.
     */
    public function requiresAcceptance(): bool
    {
        return match ($this) {
            self::Terms, self::Privacy => true,
            self::Disclaimer, self::Contact => false,
        };
    }

    /** @return list<self> */
    public static function acceptable(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $document): bool => $document->requiresAcceptance()));
    }
}
