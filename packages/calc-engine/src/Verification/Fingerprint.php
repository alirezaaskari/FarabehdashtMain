<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Verification;

use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Farabehdasht\CalcEngine\Quantity;
use Farabehdasht\CalcEngine\Unit;

/**
 * اثر انگشت رفتاری یک نسخه فرمول.
 *
 * چه چیزی داخل اثر انگشت است: شناسه، نسخه، قرارداد ورودی‌ها، واحد خروجی‌ها،
 * و نتیجه عددی همه موردهای مرجع تا ده رقم اعشار، به‌علاوه تعداد یادداشت‌های
 * هر مورد.
 *
 * چه چیزی عمداً بیرون است: متن فارسی برچسب‌ها، عنوان، منبع، محدودیت‌ها و متن
 * یادداشت‌ها. ویرایش نگارشی نباید فرمول را وادار به گرفتن نسخه تازه کند؛
 * تغییر عدد یا تغییر منطق یادداشت باید بکند.
 */
final class Fingerprint
{
    private const int PRECISION = 10;

    public static function of(Formula $formula): string
    {
        $engine = new Engine(new FormulaRegistry([$formula]));

        $cases = [];

        foreach (GoldenVectors::for($formula) as $case) {
            $calculation = $engine->run($formula->id(), $case->inputs, $formula->version());

            $cases[] = [
                'inputs' => $case->inputs,
                'outputs' => array_map(
                    static fn (Quantity $q): string => number_format($q->value, self::PRECISION, '.', ''),
                    $calculation->outputs,
                ),
                'note_count' => count($calculation->notes),
            ];
        }

        $payload = [
            'id' => $formula->id(),
            'version' => $formula->version(),
            'inputs' => array_map(
                static fn ($definition): array => $definition->toArray(),
                $formula->inputs(),
            ),
            'outputs' => array_map(static fn (Unit $unit): string => $unit->value, $formula->outputs()),
            'cases' => $cases,
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
