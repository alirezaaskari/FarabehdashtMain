<?php

declare(strict_types=1);

namespace App\Modules\Expert\Domain\Enums;

/**
 * حوزه پرسش.
 *
 * همان خطرهای دستیار ابزار، به‌علاوه دو حوزه‌ای که ابزار ندارند ولی بیشترین
 * پرسش را دارند. تکرار عمدی است: ماژول ابزار را import نمی‌کنیم (قاعده ۱).
 */
enum QuestionTopic: string
{
    case Noise = 'noise';
    case Heat = 'heat';
    case Lighting = 'lighting';
    case Chemical = 'chemical';
    case Ventilation = 'ventilation';
    case Vibration = 'vibration';
    case Ergonomics = 'ergonomics';
    case ExposureStatistics = 'statistics';
    case SafetyManagement = 'management';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Noise => 'صدا',
            self::Heat => 'گرما',
            self::Lighting => 'روشنایی',
            self::Chemical => 'مواد شیمیایی',
            self::Ventilation => 'تهویه',
            self::Vibration => 'ارتعاش',
            self::Ergonomics => 'ارگونومی',
            self::ExposureStatistics => 'آمار مواجهه',
            self::SafetyManagement => 'مدیریت ایمنی و بهداشت',
            self::Other => 'سایر',
        };
    }
}
