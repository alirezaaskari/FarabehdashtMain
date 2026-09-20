<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Domain\Enums;

/**
 * شش نوع محتوای دانشنامه.
 *
 * فهرست بسته است و در کد می‌ماند، نه در دیتابیس: هر نوع دوره بازبینی متفاوت
 * و انتظار متفاوتی از منابع دارد. نوع تازه یعنی تصمیم سرمقاله‌ای، نه یک ردیف
 * که مدیر شبانه اضافه کند.
 */
enum ArticleType: string
{
    case Article = 'article';
    case Guide = 'guide';
    case Glossary = 'glossary';
    case Method = 'method';
    case CaseStudy = 'case_study';
    case Regulation = 'regulation';

    public function label(): string
    {
        return match ($this) {
            self::Article => 'مقاله',
            self::Guide => 'راهنما',
            self::Glossary => 'واژه‌نامه',
            self::Method => 'روش اندازه‌گیری',
            self::CaseStudy => 'نمونه موردی',
            self::Regulation => 'قوانین و الزامات',
        };
    }

    /**
     * دوره بازبینی به ماه.
     *
     * قانون و روش اندازه‌گیری زودتر کهنه می‌شوند چون به ویرایش سند بیرونی
     * وابسته‌اند؛ واژه‌نامه دیرتر، چون تعریف پایه کمتر عوض می‌شود.
     */
    public function reviewIntervalMonths(): int
    {
        return match ($this) {
            self::Regulation => 6,
            self::Method => 12,
            self::Guide, self::Article, self::CaseStudy => 18,
            self::Glossary => 24,
        };
    }

    /**
     * کمترین تعداد منبع برای انتشار.
     *
     * نمونه موردی داده خودش را دارد و یک منبع روشی کافی است؛ قانون بدون
     * استناد به سند اصلی بی‌معناست.
     */
    public function minimumReferences(): int
    {
        return match ($this) {
            self::Regulation, self::Method => 2,
            self::Article, self::Guide, self::Glossary => 1,
            self::CaseStudy => 1,
        };
    }
}
