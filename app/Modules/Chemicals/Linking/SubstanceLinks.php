<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Linking;

use App\Contracts\LinkTargetSource;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Linking\LinkTarget;
use Illuminate\Support\Facades\Route;

/**
 * مواد شیمیایی به‌عنوان مقصد پیوند: نام فارسی، نام انگلیسی و شماره CAS.
 *
 * مترادف‌ها عمداً نیستند: مترادف‌های کوتاه («الکل»، «اسید») به چند ماده
 * می‌خورند و پیوند غلط می‌سازند.
 */
final readonly class SubstanceLinks implements LinkTargetSource
{
    public static function key(Substance $substance): string
    {
        return 'chemicals:'.$substance->slug;
    }

    /** @return iterable<LinkTarget> */
    public function linkTargets(): iterable
    {
        if (! Route::has('chemicals.show')) {
            return;
        }

        foreach (Substance::query()->published()->orderBy('id')->cursor() as $substance) {
            yield new LinkTarget(
                self::key($substance),
                $substance->name_fa,
                route('chemicals.show', $substance->slug),
                [$substance->name_fa, $substance->name_en, $substance->cas_number],
            );
        }
    }
}
