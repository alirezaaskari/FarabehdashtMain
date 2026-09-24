<?php

declare(strict_types=1);

namespace App\Support\Payments;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * عملیات مالی در این نشست مجاز نیست. از نوع ۴۰۳ است تا اگر به کنترلر رسید،
 * کاربر همین پیام را ببیند، نه خطای ۵۰۰.
 */
final class FinancialActionBlocked extends AccessDeniedHttpException
{
    public static function whileImpersonating(): self
    {
        return new self('در حالت «مشاهده به‌عنوان کاربر» عملیات مالی مجاز نیست.');
    }
}
