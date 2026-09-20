<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Support\PersianDigits;
use Farabehdasht\CalcEngine\Exception\InvalidInput;

/**
 * تبدیل خطاهای موتور به چیزی که کنار فیلد فرم می‌نشیند.
 *
 * موتور خطای عضو یک فهرست را با کلید `levels.1` می‌دهد (صفرمبنا). فرم فیلدی
 * به آن نام ندارد، پس خطا زیر `levels` جمع می‌شود و شماره ردیف — یک‌مبنا و با
 * ارقام فارسی، همان‌طور که کاربر می‌بیند — به متن اضافه می‌شود.
 */
final class ToolErrorBag
{
    /**
     * @return array<string, list<string>>
     */
    public static function fromInvalidInput(InvalidInput $exception): array
    {
        $bag = [];

        foreach ($exception->errors as $error) {
            [$field, $row] = self::split($error->key);

            $bag[$field][] = $row === null
                ? $error->message
                : sprintf('ردیف %s: %s', PersianDigits::from($row), $error->message);
        }

        return $bag;
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private static function split(string $key): array
    {
        if (! str_contains($key, '.')) {
            return [$key, null];
        }

        [$field, $index] = explode('.', $key, 2);

        return ctype_digit($index) ? [$field, (int) $index + 1] : [$key, null];
    }
}
