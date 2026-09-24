<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Services\ProductAccess;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
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

    public function show(Request $request, string $slug, ProductAccess $access): View
    {
        $product = Product::query()->published()->where('slug', $slug)->with('versions')->first();

        if ($product === null) {
            throw new NotFoundHttpException('این محصول پیدا نشد.');
        }

        // خریدار از همین صفحه دوباره دانلود می‌کند و همیشه آخرین نسخه را می‌گیرد؛
        // پیوند صفحه پرداخت فقط ۱۵ دقیقه اعتبار دارد.
        $userId = $request->user()?->getKey();
        $owned = $userId !== null && $access->userOwns((int) $userId, $product);

        return view('commerce::show', ['product' => $product, 'owned' => $owned, 'seo' => $this->seo($product)]);
    }

    private function seo(Product $product): SeoMeta
    {
        $url = route('commerce.show', $product->slug);

        $meta = new SeoMeta(title: $product->title, description: $product->description, canonical: $url);

        // فایل دیجیتال است و تا منتشر است، موجود است.
        return $meta->withSchema(Schema::graph(
            Schema::product($product->title, $url, $product->price(), description: $product->description),
            Schema::breadcrumbs([
                ['name' => 'فروشگاه', 'url' => route('commerce.index')],
                ['name' => $product->title, 'url' => $url],
            ]),
        ));
    }
}
