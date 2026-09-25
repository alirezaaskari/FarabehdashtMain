<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Enums;

/**
 * سؤال اول دستیار: «چه چیزی می‌خواهید اندازه بگیرید؟»
 *
 * گسترده‌تر از {@see ToolCategory}: آمار مواجهه هنوز ابزار ندارد، ولی کاربرش
 * مقاله، فایل و دوره لازم دارد. دستیار برای چنین خطری همان محتوا را پیشنهاد
 * می‌کند و صادقانه می‌گوید ابزارش هنوز نیامده.
 */
enum Hazard: string
{
    case Noise = 'noise';
    case Heat = 'heat';
    case Lighting = 'lighting';
    case Chemical = 'chemical';
    case Ventilation = 'ventilation';
    case Vibration = 'vibration';
    case Ergonomics = 'ergonomics';
    case ExposureStatistics = 'statistics';

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
        };
    }

    /** نمونه‌هایی که کاربر میدانی با آن‌ها خطر را می‌شناسد. */
    public function hint(): string
    {
        return match ($this) {
            self::Noise => 'تراز، دوز، Leq، حفاظ شنوایی',
            self::Heat => 'WBGT، Heat Index، کار و استراحت',
            self::Lighting => 'شدت، یکنواختی',
            self::Chemical => 'تبدیل واحد، TWA، STEL، مخلوط',
            self::Ventilation => 'ACH، دبی مورد نیاز',
            self::Vibration => 'دست و بازو، تمام‌بدن',
            self::Ergonomics => 'بلندکردن بار، پوسچر',
            self::ExposureStatistics => 'میانگین هندسی، UCL',
        };
    }

    /**
     * واژه‌هایی که محتوای ماژول‌های دیگر با آن‌ها پیدا می‌شود.
     *
     * هر واژه جداگانه جست‌وجو می‌شود؛ ریشه کوتاه («گرما») شکل‌های بلندتر
     * («گرمایی») را هم می‌گیرد. بیش از دو واژه نه: هر واژه چند پرس‌وجو است.
     *
     * @return list<string>
     */
    public function keywords(): array
    {
        return match ($this) {
            self::Noise => ['صدا', 'شنوایی'],
            self::Heat => ['گرما', 'WBGT'],
            self::Lighting => ['روشنایی'],
            self::Chemical => ['شیمیایی', 'TWA'],
            self::Ventilation => ['تهویه'],
            self::Vibration => ['ارتعاش'],
            self::Ergonomics => ['ارگونومی', 'پوسچر'],
            self::ExposureStatistics => ['آمار', 'میانگین هندسی'],
        };
    }

    /** گروه ابزارهای همین خطر، اگر ابزاری برایش تعریف شده باشد. */
    public function category(): ?ToolCategory
    {
        return ToolCategory::tryFrom($this->value);
    }
}
