<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain;

use RuntimeException;

/** کاربر به سقف روزانه گزارش اشتباه رسیده است. */
final class ReportLimitReached extends RuntimeException
{
    public function __construct(public readonly int $perDay)
    {
        parent::__construct('daily error-report limit reached');
    }
}
