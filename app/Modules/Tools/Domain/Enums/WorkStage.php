<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Enums;

/**
 * سؤال دوم دستیار: «الان در چه مرحله‌ای هستید؟»
 *
 * یک خطر، در هر مرحله کار چیز دیگری لازم دارد: سر اندازه‌گیری ابزار و روش،
 * هنگام طراحی کنترل مقاله و بسته راه‌حل، و موقع نوشتن گزارش قالب و گزارش‌ساز.
 * مرحله فقط ترتیب پیشنهادها را عوض می‌کند، نه اینکه چه چیزی پیدا شود.
 */
enum WorkStage: string
{
    case Measure = 'measure';
    case Compare = 'compare';
    case Control = 'control';
    case Report = 'report';

    public function label(): string
    {
        return match ($this) {
            self::Measure => 'در محل اندازه‌گیری می‌کنم',
            self::Compare => 'نتیجه را با حد مجاز مقایسه می‌کنم',
            self::Control => 'کنترل یا راه‌حل طراحی می‌کنم',
            self::Report => 'گزارش می‌نویسم',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Measure => 'ابزار محاسبه و روش اندازه‌گیری',
            self::Compare => 'حد مجاز، تفسیر نتیجه و مواد',
            self::Control => 'راهکار کنترل، بسته راه‌حل و دوره',
            self::Report => 'قالب گزارش و گزارش‌ساز میزکار',
        };
    }

    /**
     * ترتیب نوع پیشنهادها در این مرحله؛ کلیدها همان کلید گروه‌های جست‌وجو هستند.
     *
     * @return list<string>
     */
    public function priority(): array
    {
        return match ($this) {
            self::Measure => ['tools', 'encyclopedia', 'courses', 'commerce', 'chemicals'],
            self::Compare => ['tools', 'chemicals', 'encyclopedia', 'courses', 'commerce'],
            self::Control => ['encyclopedia', 'commerce', 'courses', 'tools', 'chemicals'],
            self::Report => ['tools', 'commerce', 'encyclopedia', 'courses', 'chemicals'],
        };
    }
}
