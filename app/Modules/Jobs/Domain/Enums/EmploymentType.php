<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/**
 * نوع همکاری. مقدار schema.org همان فهرست بسته گوگل برای `employmentType` است.
 */
enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Project = 'project';
    case Internship = 'internship';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'تمام‌وقت',
            self::PartTime => 'پاره‌وقت',
            self::Project => 'پروژه‌ای',
            self::Internship => 'کارآموزی',
        };
    }

    public function schemaValue(): string
    {
        return match ($this) {
            self::FullTime => 'FULL_TIME',
            self::PartTime => 'PART_TIME',
            self::Project => 'CONTRACTOR',
            self::Internship => 'INTERN',
        };
    }
}
