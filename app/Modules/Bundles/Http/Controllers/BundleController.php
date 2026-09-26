<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Http\Controllers;

use App\Contracts\SalesSwitch;
use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Bundles\Domain\BundlePurchase;
use App\Modules\Bundles\Services\ComponentCatalog;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class BundleController
{
    public function __construct(
        private ComponentCatalog $catalog,
        private SalesSwitch $sales,
    ) {}

    public function index(): View
    {
        $bundles = Bundle::query()->published()->with('items')->orderBy('title')->get();

        return view('bundles::index', [
            'rows' => $bundles->map(fn (Bundle $bundle): array => [
                'bundle' => $bundle,
                'listTotal' => $this->listTotal($bundle),
            ])->all(),
        ]);
    }

    /** بسته برداشته‌شده فقط برای خریدارش باز می‌ماند. */
    public function show(Request $request, Bundle $bundle): View
    {
        $userId = (int) $request->user()?->getKey();
        $bought = $userId > 0 && BundlePurchase::query()->paid()->where('bundle_id', $bundle->id)->where('user_id', $userId)->exists();

        abort_unless($bundle->isPublished() || $bought, 404);

        $bundle->load('items');
        $rows = [];

        foreach ($this->catalog->resolve($bundle) as $row) {
            $component = $row['component'];
            [$kind] = explode(':', $row['key'], 2);
            $source = $this->catalog->source($kind);

            $rows[] = [
                'component' => $component,
                'kind' => $source?->label() ?? '',
                'owned' => $component !== null && $userId > 0 && ($source?->owns($userId, $component->ref) ?? false),
            ];
        }

        $available = $this->catalog->available($bundle) !== null;
        $listTotal = $this->listTotal($bundle);

        return view('bundles::show', [
            'bundle' => $bundle,
            'rows' => $rows,
            'listTotal' => $listTotal,
            'saving' => $listTotal->isGreaterThan($bundle->price()) ? $listTotal->minus($bundle->price()) : Money::zero(),
            'onSale' => $available && $bundle->isPublished() && $this->sales->isOpen(SalesSwitch::SOLUTION_BUNDLE),
            'bought' => $bought,
        ]);
    }

    private function listTotal(Bundle $bundle): Money
    {
        $total = Money::zero();

        foreach ($this->catalog->resolve($bundle) as $row) {
            if ($row['component'] !== null) {
                $total = $total->plus($row['component']->listPrice);
            }
        }

        return $total;
    }
}
