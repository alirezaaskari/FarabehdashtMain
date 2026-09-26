<?php

declare(strict_types=1);

/*
 * بردارهای هم‌ارزی فرمول‌ها برای نسخه جاوااسکریپت (بخش ۱۸-۱۰).
 *
 * برای هر نسخه فرمول: تعریف ورودی‌ها، موردهای طلایی پکیج، و موردهای تصادفیِ
 * بذرداده که خود موتور PHP حسابشان کرده است. تست node (`npm run test:formulas`)
 * همین‌ها را به نسخه JS می‌دهد و باید همان خروجی، همان یادداشت و همان کلید خطا
 * را بگیرد؛ پس دو پیاده‌سازی هرگز بی‌صدا از هم جدا نمی‌شوند.
 *
 * اجرا: php scripts/formula-vectors.php > <file>
 */

use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Verification\GoldenVectors;

require dirname(__DIR__).'/vendor/autoload.php';

const RANDOM_CASES = 40;
const MAX_RANDOM_ITEMS = 6;

$engine = Engine::withDefaultFormulas();

/** @param array<string, mixed> $inputs */
$run = static function (string $id, string $version, array $inputs) use ($engine): array {
    try {
        $calculation = $engine->run($id, $inputs, $version);
    } catch (InvalidInput $exception) {
        $keys = array_values(array_unique(array_map(static fn ($error): string => $error->key, $exception->errors)));
        sort($keys);

        return ['errors' => $keys];
    }

    $outputs = [];

    foreach ($calculation->outputs as $key => $quantity) {
        $outputs[$key] = is_finite($quantity->value) ? $quantity->value : (is_nan($quantity->value) ? 'NAN' : ($quantity->value > 0 ? 'INF' : '-INF'));
    }

    return ['outputs' => $outputs, 'notes' => array_values($calculation->notes)];
};

$random = static function (InputDefinition $definition): float {
    $value = $definition->min + (mt_rand() / mt_getrandmax()) * ($definition->max - $definition->min);

    // نیمی از موردها عدد گرد می‌گیرند تا شاخه‌های «برابر مرز» و کدهای صحیح هم دیده شوند.
    return mt_rand(0, 1) === 1 ? round($value) : round($value, 3);
};

$formulas = [];

foreach (DefaultFormulas::all() as $formula) {
    $id = $formula->id();
    $version = $formula->version();
    $definitions = $formula->inputs();

    $spec = [];

    foreach ($definitions as $key => $definition) {
        $spec[$key] = [
            'label' => $definition->label,
            'unit_label' => $definition->unit->label(),
            'min' => $definition->min,
            'max' => $definition->max,
            'list' => $definition->list,
            'min_items' => $definition->minItems,
            'max_items' => $definition->maxItems,
        ];
    }

    $cases = [];

    foreach (GoldenVectors::for($formula) as $golden) {
        $cases[] = ['name' => $golden->name, 'inputs' => $golden->inputs, ...$run($id, $version, $golden->inputs)];
    }

    mt_srand(crc32($id.'@'.$version));

    for ($i = 0; $i < RANDOM_CASES; $i++) {
        $count = mt_rand(1, MAX_RANDOM_ITEMS);
        $inputs = [];

        foreach ($definitions as $key => $definition) {
            if (! $definition->list) {
                $inputs[$key] = $random($definition);

                continue;
            }

            $items = max($definition->minItems, min($definition->maxItems, $count));
            $inputs[$key] = array_map(static fn (): float => $random($definition), range(1, $items));
        }

        $cases[] = ['name' => 'random '.$i, 'inputs' => $inputs, ...$run($id, $version, $inputs)];
    }

    $formulas[$id.'@'.$version] = ['inputs' => $spec, 'cases' => $cases];
}

echo json_encode(['formulas' => $formulas], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR), "\n";
