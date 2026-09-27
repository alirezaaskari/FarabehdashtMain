<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * آیا یک جریان فروش همین حالا باز است.
 *
 * کلیدهای درآمدزایی مال ماژول Monetization است، ولی صفحه‌های فروش مال
 * فروشگاه و دوره‌ها. آن‌ها Enum درآمدزایی را import نمی‌کنند (قاعده ۱) و
 * کلید را با نام رشته‌ای‌اش از همین در می‌پرسند.
 *
 * مثل `EntitlementGate` همیشه بسته است: پیش‌فرضش `AlwaysOpen` در
 * `AppServiceProvider` است و Monetization بازنویسی‌اش می‌کند.
 */
interface SalesSwitch
{
    public const FILE_SALE = 'file_sale';

    public const COURSE_SALE = 'course_sale';

    public const REPORT_SALE = 'paid_report_builder';

    public const EXAM_PACK = 'exam_pack';

    public const SOLUTION_BUNDLE = 'solution_bundle';

    public const EVENT_WEBINAR = 'event_webinar';

    public const CONSULTING_SERVICE = 'consulting_service';

    public const REPORT_REVIEW = 'report_review';

    public const JOB_POSTING = 'job_posting';

    public function isOpen(string $stream): bool;
}
