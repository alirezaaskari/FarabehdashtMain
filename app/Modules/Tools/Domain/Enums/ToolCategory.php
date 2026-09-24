<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Enums;

/**
 * گروه عامل زیان‌آوری که ابزار به آن می‌پردازد.
 *
 * گروه‌بندی از دید کاربر میدانی است، نه از دید ریاضیات: کسی که صداسنج دستش
 * است، دنبال «صدا» می‌گردد، نه دنبال «رابطه لگاریتمی».
 */
enum ToolCategory: string
{
    case Heat = 'heat';
    case Noise = 'noise';
    case Chemical = 'chemical';
    case Lighting = 'lighting';
    case Ventilation = 'ventilation';

    public function label(): string
    {
        return match ($this) {
            self::Heat => 'استرس گرمایی',
            self::Noise => 'صدا',
            self::Chemical => 'عوامل شیمیایی',
            self::Lighting => 'روشنایی',
            self::Ventilation => 'تهویه',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Heat => 'شاخص‌های محیط گرم و سرد.',
            self::Noise => 'تراز، مواجهه و تصحیح اندازه‌گیری صدا.',
            self::Chemical => 'تبدیل واحد غلظت و میانگین مواجهه.',
            self::Lighting => 'روشنایی سطح کار و یکنواختی آن.',
            self::Ventilation => 'نرخ تعویض هوا و دبی مورد نیاز.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Heat => 'sun',
            self::Noise => 'wave',
            self::Chemical => 'chemical',
            self::Lighting => 'bulb',
            self::Ventilation => 'wind',
        };
    }
}
