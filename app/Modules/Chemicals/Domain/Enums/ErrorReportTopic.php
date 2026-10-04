<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Enums;

/**
 * بخشی از صفحه ماده که گزارش اشتباه درباره آن است.
 *
 * مدیر با دیدن موضوع می‌داند کدام بخش ویرایشگر را باز کند و کدام منبع را
 * دوباره بخواند.
 */
enum ErrorReportTopic: string
{
    case LimitValue = 'limit_value';
    case LimitSource = 'limit_source';
    case Identity = 'identity';
    case Health = 'health';
    case Sampling = 'sampling';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LimitValue => 'عدد یا واحد یک حد مواجهه',
            self::LimitSource => 'منبع یا پیوند یک حد',
            self::Identity => 'نام، شماره CAS، فرمول یا مشخصات',
            self::Health => 'مسیر مواجهه، علائم یا حفاظت',
            self::Sampling => 'روش نمونه‌برداری و تحلیل',
            self::Other => 'موضوع دیگر',
        };
    }
}
