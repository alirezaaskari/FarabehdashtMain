<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Input;

/**
 * دلیل رد شدن یک ورودی — نه متن، بلکه کد.
 *
 * متن فارسی همراه خطا می‌آید ولی ارقامش لاتین است چون موتور از Laravel و از
 * ابزارهای بومی‌سازی پروژه خبر ندارد. لایه نمایش می‌تواند با همین کد و
 * پارامترها پیام را با ارقام فارسی از نو بسازد.
 */
enum InputErrorCode: string
{
    case Missing = 'missing';
    case Unexpected = 'unexpected';
    case NotNumeric = 'not_numeric';
    case NotFinite = 'not_finite';
    case ExpectedList = 'expected_list';
    case ExpectedSingle = 'expected_single';
    case TooFewItems = 'too_few_items';
    case TooManyItems = 'too_many_items';
    case OutOfRange = 'out_of_range';
}
