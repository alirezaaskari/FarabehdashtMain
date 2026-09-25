<?php

declare(strict_types=1);

namespace App\Support\Help;

use Illuminate\Support\HtmlString;

/**
 * متن راهنمای بخش‌ها (config/help.php) برای چاپ در قالب.
 *
 * مقدار اندازه‌گیری و کد لاتین در متن راهنما داخل `[[…]]` نوشته می‌شود
 * («[[50 ppm]]»، «[[71-43-2]]») و این‌جا `data-numeric` چپ‌به‌راست می‌گیرد
 * تا در جمله فارسی وارونه نشود (قواعد لایه طراحی). بقیه متن escape می‌شود.
 */
final class HelpText
{
    public static function render(string $text): HtmlString
    {
        return new HtmlString((string) preg_replace(
            '/\[\[(.+?)\]\]/u',
            '<span data-numeric dir="ltr">$1</span>',
            e($text),
        ));
    }
}
