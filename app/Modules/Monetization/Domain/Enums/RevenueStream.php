<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain\Enums;

/**
 * یک جریان درآمدی، با کلید مستقل روشن و خاموش.
 *
 * پاسخ به پرسش مدیر در `docs/architecture/monetization-toggles.md`: هر جریان
 * کلید خودش را دارد و خاموش‌کردنش هیچ داده‌ای حذف نمی‌کند.
 *
 * چند جریان این فهرست ماژولشان هنوز ساخته نشده (آگهی شغلی، بانک رزومه،
 * رویداد…). کلیدشان از حالا هست چون صفحه کلیدهای مدیر باید کل نقشه درآمدی
 * را نشان بدهد، نه فقط بخش ساخته‌شده‌اش؛ و کلیدی که کسی نمی‌پرسدش هیچ اثری
 * ندارد.
 */
enum RevenueStream: string
{
    case FileSale = 'file_sale';
    case CourseSale = 'course_sale';
    case ProSubscription = 'pro_subscription';
    case TeamSeat = 'team_seat';
    case ExamPack = 'exam_pack';
    case PaidReportBuilder = 'paid_report_builder';
    case JobPosting = 'job_posting';
    case ResumeBankAccess = 'resume_bank_access';
    case ProjectMarketCommission = 'project_market_commission';
    case EventWebinar = 'event_webinar';
    case DirectoryFeature = 'directory_feature';

    public function label(): string
    {
        return match ($this) {
            self::FileSale => 'تک‌فروشی فایل',
            self::CourseSale => 'تک‌فروشی دوره',
            self::ProSubscription => 'اشتراک Pro',
            self::TeamSeat => 'صندلی تیمی',
            self::ExamPack => 'بسته‌های آزمون',
            self::PaidReportBuilder => 'تک‌فروشی گزارش',
            self::JobPosting => 'ثبت آگهی شغلی',
            self::ResumeBankAccess => 'دسترسی کارفرما به بانک رزومه',
            self::ProjectMarketCommission => 'کمیسیون بازار پروژه',
            self::EventWebinar => 'رویداد و وبینار',
            self::DirectoryFeature => 'نمایش ویژه دایرکتوری',
        };
    }

    /**
     * آیا ماژولی که این جریان را اجرا می‌کند ساخته شده.
     *
     * صفحه مدیر جریان نساخته را خاموش و غیرقابل‌تغییر نشان می‌دهد؛ کلیدی که
     * روشنش هیچ کاری نمی‌کند، وعده دروغ است.
     */
    public function isBuilt(): bool
    {
        return match ($this) {
            self::FileSale, self::CourseSale, self::ProSubscription, self::TeamSeat, self::PaidReportBuilder => true,
            default => false,
        };
    }
}
