<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/** از کدام مسیر کارفرما به اطلاعات کارجو رسید. */
enum AccessSource: string
{
    case Application = 'application';
    case ResumeBank = 'resume_bank';

    public function label(): string
    {
        return match ($this) {
            self::Application => 'درخواست شغلی',
            self::ResumeBank => 'بانک رزومه',
        };
    }
}
