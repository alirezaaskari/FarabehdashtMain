<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Http\Controllers;

use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Services\SubstanceComparer;
use App\Modules\Chemicals\Services\SubstanceFinder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * مقایسه تا سه ماده — از نشانی، تا صفحه ایندکس‌پذیر و قابل اشتراک باشد.
 *
 * عبارت‌های جست‌وجو (نه شناسه‌های عددی) در نشانی می‌آیند تا پیوند به این
 * صفحه، بدون دانستن شناسه پایگاه داده، خوانا و قابل ساخت دستی باشد:
 * ‎?terms[]=تولوئن&terms[]=زایلن
 */
final readonly class ChemicalCompareController
{
    public function __construct(
        private SubstanceFinder $finder,
        private SubstanceComparer $comparer,
    ) {}

    public function __invoke(Request $request): View
    {
        $max = (int) config('chemicals.compare.max', 3);

        /** @var list<string> $terms */
        $terms = array_values(array_filter(
            array_map('strval', (array) $request->query('terms', [])),
            static fn (string $t): bool => trim($t) !== '',
        ));
        $terms = array_slice($terms, 0, $max);

        $substances = [];
        $notFound = [];

        foreach ($terms as $term) {
            $substance = $this->finder->findOne($term);

            if ($substance === null) {
                $notFound[] = $term;
            } else {
                $substances[] = $substance;
            }
        }

        // یک ماده دو بار انتخاب نشود — مقایسه یک ماده با خودش بی‌فایده است.
        $seen = [];
        $substances = array_values(array_filter($substances, function (Substance $s) use (&$seen): bool {
            if (isset($seen[$s->id])) {
                return false;
            }

            $seen[$s->id] = true;

            return true;
        }));

        return view('chemicals::compare', [
            'terms' => $terms,
            'notFound' => $notFound,
            'substances' => $substances,
            'rows' => count($substances) >= 2 ? $this->comparer->compare($substances) : [],
            'max' => $max,
        ]);
    }
}
