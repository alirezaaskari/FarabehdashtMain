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
    case Vibration = 'vibration';
    case Ergonomics = 'ergonomics';

    public function label(): string
    {
        return match ($this) {
            self::Heat => 'استرس گرمایی',
            self::Noise => 'صدا',
            self::Chemical => 'عوامل شیمیایی',
            self::Lighting => 'روشنایی',
            self::Ventilation => 'تهویه',
            self::Vibration => 'ارتعاش',
            self::Ergonomics => 'ارگونومی',
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
            self::Vibration => 'مواجهه روزانه با ارتعاش دست و بازو و تمام بدن.',
            self::Ergonomics => 'ارزیابی کار دستی و بلندکردن بار.',
        };
    }

    /**
     * راهنمای تفسیر زیر نتیجه: به چه چیزهایی جز خود عدد بستگی دارد.
     *
     * بدون ادعای انطباق یا ایمنی قطعی؛ فقط اینکه قضاوت کارشناس چه چیزی را
     * باید کنار عدد بگذارد.
     */
    public function interpretation(): string
    {
        return match ($this) {
            self::Heat => 'مقایسه این عدد با حد مرجع به بار متابولیکی کار، پوشش لباس کار و برنامه کار–استراحت بستگی دارد.',
            self::Noise => 'مقایسه این عدد با حد مرجع به مدت مواجهه، نرخ تبادل مرجع انتخابی و کلاس و کالیبراسیون صداسنج بستگی دارد.',
            self::Chemical => 'مقایسه این عدد با حد مجاز به نوع حد (TWA، STEL یا سقفی)، مرجع و ویرایش آن و نماینده‌بودن نمونه‌برداری بستگی دارد.',
            self::Lighting => 'مقایسه این عدد با مقدار توصیه‌شده به نوع کار، سن کاربران و آرایش نقاط اندازه‌گیری بستگی دارد.',
            self::Ventilation => 'کافی‌بودن این عدد به نوع فضا، منبع آلاینده و محل ورود و خروج هوا بستگی دارد، نه فقط به خود نرخ.',
            self::Vibration => 'مقایسه این عدد با مقدار اقدام یا حد مرجع به روش اندازه‌گیری، مدت واقعی تماس و شرایطی مثل سرما و نیروی گرفتن بستگی دارد.',
            self::Ergonomics => 'این عدد خطر نسبی کار را نشان می‌دهد؛ تفسیرش به تکرار کار، سابقه کارکنان و عواملی که معادله نمی‌بیند (لغزندگی، بار ناپایدار) بستگی دارد.',
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
            self::Vibration => 'pulse',
            self::Ergonomics => 'user',
        };
    }
}
