<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Services\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class CartController
{
    public function __construct(private Cart $cart) {}

    public function index(): View
    {
        $products = Product::query()
            ->published()
            ->whereIn('id', $this->cart->productIds())
            ->get();

        return view('commerce::cart', [
            'products' => $products,
            'total' => $products->sum('price_toman'),
        ]);
    }

    public function add(Product $product): RedirectResponse
    {
        if ($product->status->purchasable()) {
            $this->cart->add($product->id);
        }

        return redirect()->route('commerce.cart');
    }

    public function remove(Product $product): RedirectResponse
    {
        $this->cart->remove($product->id);

        return redirect()->route('commerce.cart');
    }
}
