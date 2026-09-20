<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Modules\Tools\Domain\ResultRow;
use Farabehdasht\CalcEngine\Calculation;
use Farabehdasht\CalcEngine\Unit;
use ValueError;

/**
 * تبدیل خروجی موتور به سطرهای آماده نمایش.
 *
 * دو مسیر ورودی دارد چون دو منبع داریم: نتیجه تازه (اشیای `Quantity`) و
 * محاسبه ذخیره‌شده (آرایه JSON). هر دو به یک شکل بیرون می‌آیند تا قالب یکی
 * باشد و نسخه چاپی با صفحه نتیجه فرق نکند.
 */
final readonly class ResultPresenter
{
    /**
     * @param  array<string, string>  $labels
     */
    public function __construct(private array $labels) {}

    /**
     * @return list<ResultRow>
     */
    public function fromCalculation(Calculation $calculation): array
    {
        $rows = [];

        foreach ($calculation->outputs as $key => $quantity) {
            $rows[] = $this->row($key, $quantity->value, $quantity->unit);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $outputs  شکل ذخیره‌شده: کلید => {value, unit}
     * @return list<ResultRow>
     */
    public function fromStored(array $outputs): array
    {
        $rows = [];

        foreach ($outputs as $key => $stored) {
            if (! is_array($stored)) {
                continue;
            }

            if (array_key_exists('value', $stored)) {
                $rows[] = $this->row($key, (float) $stored['value'], $this->unitOf($stored));

                continue;
            }

            // ورودی فهرستی (مثل ترازهای هر بازه) یک سطر می‌شود، وگرنه از
            // گزارش چاپی حذف می‌شد و خواننده نمی‌فهمید چه اندازه گرفته شده.
            $rows[] = $this->listRow($key, $stored);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $stored
     */
    private function unitOf(array $stored): ?Unit
    {
        try {
            return Unit::from((string) ($stored['unit'] ?? ''));
        } catch (ValueError) {
            // واحدی که دیگر در موتور نیست، نباید صفحه گزارش قدیمی را بترکاند.
            return null;
        }
    }

    /**
     * @param  list<mixed>|array<string, mixed>  $items
     */
    private function listRow(string $key, array $items): ResultRow
    {
        $unit = null;
        $values = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! array_key_exists('value', $item)) {
                continue;
            }

            $unit ??= $this->unitOf($item);
            $values[] = MeasurementNumber::format((float) $item['value']);
        }

        return new ResultRow(
            key: $key,
            label: $this->labels[$key] ?? $key,
            value: implode(' · ', $values),
            unit: $unit === null || $unit->dimensionless() ? null : $unit->symbol(),
        );
    }

    private function row(string $key, float $value, ?Unit $unit): ResultRow
    {
        return new ResultRow(
            key: $key,
            label: $this->labels[$key] ?? $key,
            value: MeasurementNumber::format($value),
            unit: $unit === null || $unit->dimensionless() ? null : $unit->symbol(),
        );
    }
}
