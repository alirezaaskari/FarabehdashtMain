<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Models\User;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ShopPagesTest extends TestCase
{
    use RefreshDatabase;

    private function published(): Product
    {
        return Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'quiet-template',
            'title' => 'قالب گزارش سکوت‌سنجی',
            'description' => 'قالب آماده برای گزارش نمونه‌برداری صدا.',
            'price_toman' => 75_000,
            'status' => ProductStatus::Published,
        ])->refresh();
    }

    public function test_the_shop_index_lists_published_products(): void
    {
        $product = $this->published();

        $this->get(route('commerce.index'))
            ->assertOk()
            ->assertSee($product->title);
    }

    public function test_a_draft_product_is_not_listed(): void
    {
        Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'draft-one',
            'title' => 'پیش‌نویس نادیده',
            'price_toman' => 10_000,
            'status' => ProductStatus::Draft,
        ]);

        $this->get(route('commerce.index'))->assertDontSee('پیش‌نویس نادیده');
    }

    public function test_the_product_page_renders(): void
    {
        $product = $this->published();

        $this->get(route('commerce.show', $product->slug))
            ->assertOk()
            ->assertSee($product->title)
            ->assertSee($product->price()->format());
    }

    public function test_a_draft_products_page_is_not_found(): void
    {
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'hidden-draft',
            'title' => 'پیش‌نویس پنهان',
            'price_toman' => 10_000,
            'status' => ProductStatus::Draft,
        ]);

        $this->get(route('commerce.show', $product->slug))->assertNotFound();
    }

    public function test_the_cart_page_shows_added_products(): void
    {
        $product = $this->published();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post(route('commerce.cart.add', $product))->assertRedirect();

        $this->actingAs($buyer)->get(route('commerce.cart'))
            ->assertOk()
            ->assertSee($product->title);
    }
}
