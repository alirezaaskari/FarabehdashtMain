<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Actions\AddProductVersion;
use App\Modules\Commerce\Actions\RetireProduct;
use App\Modules\Commerce\Actions\SubmitProductForReview;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * مدیریت محصول توسط فروشنده — پیش از یکپارچگی با میزکار مشترک (بخش ۱۵)،
 * فعلاً روی پوسته عمومی است.
 */
final readonly class VendorProductController
{
    public function __construct(
        private SubmitProductForReview $submit,
        private AddProductVersion $addVersion,
        private RetireProduct $retire,
    ) {}

    public function index(Request $request): View
    {
        $products = Product::query()
            ->ownedBy((int) $request->user()->id)
            ->latest('id')
            ->get();

        return view('commerce::vendor.products', ['products' => $products]);
    }

    public function create(): View
    {
        return view('commerce::vendor.product-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'string'],
        ]);

        try {
            $price = Money::fromInput($data['price']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['price' => $exception->getMessage()])->withInput();
        }

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $request->user()->id,
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_toman' => $price->toman,
            'status' => ProductStatus::Draft,
        ]);

        return redirect()->route('commerce.vendor.products.edit', $product);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorizeOwnership($request, $product);

        return view('commerce::vendor.product-edit', ['product' => $product->load('versions')]);
    }

    public function addVersion(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwnership($request, $product);

        $data = $request->validate([
            'version' => ['required', 'string', 'max:32'],
            'changelog' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:51200'],
        ]);

        try {
            $this->addVersion->handle($product, $data['version'], $data['changelog'] ?? null, $data['file']);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()->route('commerce.vendor.products.edit', $product);
    }

    public function submit(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwnership($request, $product);

        try {
            $this->submit->handle($product);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['product' => $exception->getMessage()]);
        }

        return redirect()->route('commerce.vendor.products.edit', $product);
    }

    public function retire(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwnership($request, $product);

        try {
            $this->retire->handle($product);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['product' => $exception->getMessage()]);
        }

        return redirect()->route('commerce.vendor.products.edit', $product);
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        abort_unless($product->vendor_user_id === $request->user()->id, 403, 'این محصول متعلق به شما نیست.');
    }
}
