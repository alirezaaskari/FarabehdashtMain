<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * آیا یک محاسبه ذخیره‌شده جایی مبنای داده دیگری است.
 *
 * ماژول ابزارها پیش از حذف محاسبه از همه دارندگان این برچسب می‌پرسد:
 * محاسبه‌ای که قرائت پروژه یا گزارشی به آن ارجاع داده پاک نمی‌شود، فقط از
 * فهرست کاربر بیرون می‌رود. هر ماژولی که شناسه محاسبه نگه می‌دارد این را
 * پیاده می‌کند؛ ماژولی که نباشد، ارجاعی هم ندارد.
 */
interface CalculationReferences
{
    public const TAG = 'fbh.calculation-references';

    public function references(string $calculationUuid): bool;
}
