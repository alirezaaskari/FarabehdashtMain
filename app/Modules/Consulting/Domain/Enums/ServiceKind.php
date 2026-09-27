<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain\Enums;

enum ServiceKind: string
{
    case Online = 'online';
    case Visit = 'visit';
    case ReportReview = 'report_review';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'جلسه آنلاین',
            self::Visit => 'بازدید حضوری',
            self::ReportReview => 'بررسی گزارش',
        };
    }

    /** جریان کمیسیون و کلید فروش هر نوع. بررسی گزارش جریان درآمدی جداست (بخش ۱۹-۴). */
    public function flow(): string
    {
        return $this === self::ReportReview ? 'report_review' : 'consulting';
    }

    /** بررسی گزارش جلسه و زمان ندارد؛ مهلت تحویل دارد. */
    public function isScheduled(): bool
    {
        return $this !== self::ReportReview;
    }
}
