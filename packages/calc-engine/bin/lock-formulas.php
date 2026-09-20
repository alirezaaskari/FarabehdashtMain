#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ثبت اثر انگشت نسخه‌های تازه فرمول در قفل
|--------------------------------------------------------------------------
|
| فقط اضافه می‌کند. اگر نسخه‌ای از قبل در قفل باشد و اثر انگشتش فرق کرده
| باشد، این اسکریپت آن را به‌روز نمی‌کند و با خطا بیرون می‌آید — چون تغییر
| رفتار یک نسخه منتشرشده دقیقاً همان چیزی است که قفل جلویش را می‌گیرد.
|
|     php packages/calc-engine/bin/lock-formulas.php
|
*/

use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;
use Farabehdasht\CalcEngine\Verification\Fingerprint;
use Farabehdasht\CalcEngine\Verification\FormulaLock;

$autoload = null;

foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../vendor/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;

        break;
    }
}

if ($autoload === null) {
    fwrite(STDERR, "autoload پیدا نشد. اول composer install را اجرا کنید.\n");

    exit(1);
}

require $autoload;

$entries = is_file(FormulaLock::path()) ? FormulaLock::load()->entries() : [];

$added = [];
$changed = [];

foreach (DefaultFormulas::all() as $formula) {
    $key = FormulaLock::keyFor($formula);
    $fingerprint = Fingerprint::of($formula);

    if (! isset($entries[$key])) {
        $entries[$key] = $fingerprint;
        $added[] = $key;

        continue;
    }

    if ($entries[$key] !== $fingerprint) {
        $changed[] = $key;
    }
}

if ($changed !== []) {
    fwrite(STDERR, "رفتار این نسخه‌ها عوض شده ولی نسخه‌شان همان مانده:\n");

    foreach ($changed as $key) {
        fwrite(STDERR, "  - {$key}\n");
    }

    fwrite(STDERR, "قفل به‌روز نشد. برای تغییر رفتار، کلاس نسخه تازه بسازید و نسخه قبلی را دست‌نخورده بگذارید.\n");

    exit(1);
}

if ($added === []) {
    echo "قفل از قبل به‌روز است.\n";

    exit(0);
}

FormulaLock::write($entries);

echo "به قفل اضافه شد:\n";

foreach ($added as $key) {
    echo "  + {$key}\n";
}
