<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Exception;

use Throwable;

/**
 * هر خطایی که از موتور محاسبات بیرون می‌آید این رابط را دارد، تا مصرف‌کننده
 * بتواند خطای موتور را از خطای خودش جدا کند بدون این‌که به کلاس‌های داخلی
 * موتور وابسته شود.
 */
interface CalcEngineException extends Throwable {}
