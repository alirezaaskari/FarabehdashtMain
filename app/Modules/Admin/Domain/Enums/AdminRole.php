<?php

declare(strict_types=1);

namespace App\Modules\Admin\Domain\Enums;

/**
 * نقش‌های مدیریتی.
 *
 * این‌ها **پروفایل نیستند.** پروفایل (ماژول Identity) نقش تجاری است که کاربر
 * خودش درخواست می‌دهد؛ نقش مدیریتی را فقط مدیر ارشد اعطا می‌کند و از هیچ مسیر
 * عمومی قابل درخواست نیست (قاعده ADR-0002).
 *
 * تفکیک اصلی: مدیر محتوا به بخش مالی دسترسی ندارد و مدیر مالی محتوا منتشر
 * نمی‌کند. این معیار پذیرش بخش ۵ است.
 */
enum AdminRole: string
{
    case Super = 'super';
    case Content = 'content';
    case Finance = 'finance';
    case Jobs = 'jobs';

    public function label(): string
    {
        return match ($this) {
            self::Super => 'مدیر ارشد',
            self::Content => 'مدیر محتوا',
            self::Finance => 'مدیر مالی',
            self::Jobs => 'مدیر کاریابی',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Super => 'دسترسی کامل، شامل اعطای نقش مدیریتی به دیگران.',
            self::Content => 'بررسی و انتشار محتوا، دوره، محصول و بانک مواد. بدون دسترسی مالی.',
            self::Finance => 'کیف پول، تسویه، بازگشت وجه و گزارش مالی. بدون انتشار محتوا.',
            self::Jobs => 'آگهی‌های شغلی و گزارش‌های کاریابی. بدون دسترسی مالی و محتوایی.',
        };
    }

    /**
     * توانایی‌های این نقش.
     *
     * مدیر ارشد عمداً فهرست صریح دارد و با ستاره یا میان‌بُر «همه‌چیز» تعریف
     * نمی‌شود، تا افزودن توانایی تازه یک تصمیم آگاهانه بماند.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Super => [
                ...self::Content->abilities(),
                ...self::Finance->abilities(),
                ...self::Jobs->abilities(),
                'admin.roles.grant',
                'admin.settings.manage',
                'admin.impersonate',
            ],
            self::Content => [
                'admin.panel.access',
                'admin.audit.view',
                'admin.content.review',
                'admin.content.publish',
                'admin.chemicals.manage',
                'admin.taxonomy.manage',
            ],
            self::Finance => [
                'admin.panel.access',
                'admin.audit.view',
                'admin.wallet.manage',
                'admin.settlement.approve',
                'admin.refund.issue',
                'admin.finance.reports',
            ],
            self::Jobs => [
                'admin.panel.access',
                'admin.audit.view',
                'admin.jobs.review',
                'admin.jobs.reports',
            ],
        };
    }

    /** @return list<string> */
    public static function allAbilities(): array
    {
        $abilities = [];

        foreach (self::cases() as $role) {
            $abilities = [...$abilities, ...$role->abilities()];
        }

        $unique = array_values(array_unique($abilities));
        sort($unique);

        return $unique;
    }
}
