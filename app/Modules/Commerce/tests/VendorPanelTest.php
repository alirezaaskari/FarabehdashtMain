<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Models\User;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class VendorPanelTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(): User
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $profile = $this->app->make(RequestProfileActivation::class)->handle($user, ProfileType::Vendor);
        $this->app->make(ReviewProfileRequest::class)->approve($profile, $admin);

        return $user->fresh() ?? $user;
    }

    public function test_a_non_vendor_cannot_open_the_vendor_products_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('commerce.vendor.products.index'))->assertForbidden();
    }

    public function test_a_vendor_can_create_a_product(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($vendor)
            ->post(route('commerce.vendor.products.store'), [
                'title' => 'قالب چک‌لیست ایمنی',
                'description' => 'قالب آماده Excel',
                'price' => '۵۰۰۰۰',
            ])
            ->assertRedirect();

        $product = Product::query()->where('vendor_user_id', $vendor->id)->sole();
        $this->assertSame('قالب چک‌لیست ایمنی', $product->title);
        $this->assertSame(50_000, $product->price_toman);
        $this->assertSame(ProductStatus::Draft, $product->status);
    }

    public function test_a_vendor_can_add_a_version_and_submit_for_review(): void
    {
        $vendor = $this->vendor();
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $vendor->id,
            'slug' => 'p-'.Str::random(8),
            'title' => 'محصول من',
            'price_toman' => 30_000,
            'status' => ProductStatus::Draft,
        ]);

        Storage::fake('local');

        $this->actingAs($vendor)
            ->post(route('commerce.vendor.products.versions', $product), [
                'version' => '1.0.0',
                'changelog' => 'اولین نسخه',
                'file' => UploadedFile::fake()->create('checklist.xlsx', 20),
            ])
            ->assertRedirect();

        $this->assertCount(1, $product->refresh()->versions);

        $this->actingAs($vendor)
            ->post(route('commerce.vendor.products.submit', $product))
            ->assertRedirect();

        $this->assertSame(ProductStatus::InReview, $product->refresh()->status);
    }

    public function test_a_vendor_cannot_edit_another_vendors_product(): void
    {
        $vendor = $this->vendor();
        $other = User::factory()->create();

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $other->id,
            'slug' => 'not-mine',
            'title' => 'محصول دیگری',
            'price_toman' => 10_000,
            'status' => ProductStatus::Draft,
        ]);

        $this->actingAs($vendor)->get(route('commerce.vendor.products.edit', $product))->assertForbidden();
    }

    public function test_the_settlement_page_shows_zero_for_a_vendor_with_no_sales(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($vendor)->get(route('commerce.vendor.settlement'))->assertOk();
    }
}
