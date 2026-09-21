<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Domain\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * فهرست و صفحه محصول فروشگاه.
 *
 * فقط منتشرشده نشانی عمومی دارد؛ پیش‌نویس یا رد‌شده ۴۰۴ می‌گیرد، نه ۴۰۳.
 */
final readonly class ShopController
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->published()
            ->when($query !== '', fn ($q) => $q->where('title', 'like', '%'.$query.'%'))
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('commerce::index', ['products' => $products, 'query' => $query]);
    }

    public function show(string $slug): View
    {
        $product = Product::query()->published()->where('slug', $slug)->with('versions')->first();

        if ($product === null) {
            throw new NotFoundHttpException('این محصول پیدا نشد.');
        }

        return view('commerce::show', ['product' => $product]);
    }
}
