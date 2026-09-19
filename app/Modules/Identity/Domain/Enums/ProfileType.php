<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

/**
 * نقش‌های تجاری که روی همان یک حساب فعال می‌شوند.
 *
 * نقش مدیر عمداً اینجا نیست: از مسیر Profile فعال نمی‌شود.
 */
enum ProfileType: string
{
    case Jobseeker = 'jobseeker';
    case Employer = 'employer';
    case Vendor = 'vendor';
    case Instructor = 'instructor';
    case Consultant = 'consultant';

    public function label(): string
    {
        return match ($this) {
            self::Jobseeker => 'کارجو',
            self::Employer => 'کارفرما',
            self::Vendor => 'فروشنده',
            self::Instructor => 'مدرس',
            self::Consultant => 'مشاور',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Jobseeker => 'رزومه تخصصی، ارسال درخواست شغلی، هشدار شغلی',
            self::Employer => 'ثبت آگهی، مدیریت متقاضیان، بسته‌های آگهی',
            self::Vendor => 'انتشار فایل و قالب تخصصی، گزارش فروش، تسویه',
            self::Instructor => 'ساخت دوره، جلسه و آزمون — هویت مالی مشترک با فروشنده',
            self::Consultant => 'ارائه خدمات مشاوره و پاسخ به پرسش تخصصی',
        };
    }

    /**
     * مدرس و فروشنده یک هویت مالی مشترک دارند: یک کیف پول، یک تسویه.
     * مدرس زیرمجموعه فروشنده است، نه یک موجودیت مالی جدا.
     */
    public function financialIdentity(): self
    {
        return $this === self::Instructor ? self::Vendor : $this;
    }
}
