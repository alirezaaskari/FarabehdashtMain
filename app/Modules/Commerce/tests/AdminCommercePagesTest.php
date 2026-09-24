<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Domain\ProductVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * دسترسی صفحه‌های مدیریت بخش تجارت — منطق واقعی خودش در ProductLifecycleTest،
 * RefundTest و SettlementTest آزموده شده؛ این‌جا فقط دروازه دسترسی و
 * بالاآمدن صفحه سنجیده می‌شود.
 */
final class AdminCommercePagesTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    public function test_a_content_admin_can_open_the_product_review_page(): void
    {
        $admin = $this->adminWith(AdminRole::Content);

        Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'p-'.Str::random(8),
            'title' => 'محصول در انتظار',
            'price_toman' => 10_000,
            'status' => ProductStatus::InReview,
        ]);

        $this->actingAs($admin)
            ->get('/'.config('admin.path').'/commerce-review')
            ->assertOk()
            ->assertSee('محصول در انتظار');
    }

    public function test_the_review_page_lists_new_versions_of_published_products(): void
    {
        $admin = $this->adminWith(AdminRole::Content);

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'p-'.Str::random(8),
            'title' => 'محصول منتشرشده',
            'price_toman' => 10_000,
            'status' => ProductStatus::Published,
        ]);

        ProductVersion::query()->create([
            'product_id' => $product->id,
            'version' => '3.1.0',
            'file_path' => 'products/x/3.1.0.zip',
            'file_size' => 10,
            'checksum' => 'x',
        ]);

        $this->actingAs($admin)
            ->get('/'.config('admin.path').'/commerce-review')
            ->assertOk()
            ->assertSee('نسخه‌های تازه محصولات منتشرشده')
            ->assertSee('محصول منتشرشده')
            ->assertSee('3.1.0');
    }

    public function test_a_finance_admin_cannot_open_the_product_review_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/commerce-review')
            ->assertForbidden();
    }

    public function test_a_finance_admin_can_open_the_refund_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/commerce-refund')
            ->assertOk();
    }

    public function test_a_content_admin_cannot_open_the_refund_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get('/'.config('admin.path').'/commerce-refund')
            ->assertForbidden();
    }

    public function test_a_finance_admin_can_open_the_settlement_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/commerce-settlement')
            ->assertOk();
    }

    public function test_a_content_admin_cannot_open_the_settlement_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get('/'.config('admin.path').'/commerce-settlement')
            ->assertForbidden();
    }
}
