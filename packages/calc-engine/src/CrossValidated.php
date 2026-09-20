<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

use Farabehdasht\CalcEngine\Input\InputError;
use Farabehdasht\CalcEngine\Input\InputSet;

/**
 * فرمولی که علاوه بر اعتبارسنجی تک‌تک ورودی‌ها، به بررسی رابطه بین آن‌ها هم
 * نیاز دارد — مثل «فهرست غلظت‌ها و فهرست مدت‌ها باید هم‌طول باشند».
 *
 * عمداً یک رابط جداگانه است و نه متدی روی `Formula`: بیشتر فرمول‌ها چنین
 * بررسی‌ای ندارند و مجبورکردنشان به پیاده‌سازی یک متد خالی، نویز است.
 *
 * موتور این بررسی را **پیش از** `compute()` اجرا می‌کند، پس `compute()` همچنان
 * می‌تواند فرض کند ورودی‌هایش سالم‌اند.
 */
interface CrossValidated
{
    /**
     * @return list<InputError> فهرست خالی یعنی ورودی‌ها با هم سازگارند
     */
    public function crossCheck(InputSet $inputs): array;
}
