<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Models\User;
use App\Modules\Commerce\Actions\AddProductVersion;
use App\Modules\Commerce\Actions\PublishProduct;
use App\Modules\Commerce\Actions\RejectProduct;
use App\Modules\Commerce\Actions\RetireProduct;
use App\Modules\Commerce\Actions\SubmitProductForReview;
use App\Modules\Commerce\Admin\PendingProducts;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Core\Domain\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * چرخه بررسی محصول: پیش‌نویس → در انتظار → منتشرشده/رد‌شده → بازنشسته.
 */
final class ProductLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function draftProduct(int $priceToman = 100_000): Product
    {
        $vendor = User::factory()->create();

        return Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $vendor->id,
            'slug' => 'test-'.Str::random(8),
            'title' => 'محصول آزمایشی',
            'price_toman' => $priceToman,
            'status' => ProductStatus::Draft,
        ])->refresh();
    }

    private function addVersion(Product $product): Product
    {
        Storage::fake('local');

        $this->app->make(AddProductVersion::class)->handle(
            $product,
            '1.0.0',
            'نسخه اول',
            UploadedFile::fake()->create('template.zip', 100),
        );

        return $product->refresh();
    }

    public function test_a_product_without_a_version_cannot_be_submitted(): void
    {
        $product = $this->draftProduct();

        $this->expectExceptionMessageMatches('/هیچ فایلی/u');

        $this->app->make(SubmitProductForReview::class)->handle($product);
    }

    public function test_a_product_without_a_price_cannot_be_submitted(): void
    {
        $product = $this->addVersion($this->draftProduct(priceToman: 0));

        $this->expectExceptionMessageMatches('/قیمت معتبر/u');

        $this->app->make(SubmitProductForReview::class)->handle($product);
    }

    public function test_a_fully_ready_product_moves_to_in_review(): void
    {
        $product = $this->addVersion($this->draftProduct());

        $submitted = $this->app->make(SubmitProductForReview::class)->handle($product);

        $this->assertSame(ProductStatus::InReview, $submitted->status);
        $this->assertContains('product', iterator_to_array($this->pendingKinds()));
    }

    public function test_a_draft_product_cannot_be_published_directly(): void
    {
        $product = $this->addVersion($this->draftProduct());

        $this->expectException(RuntimeException::class);

        $this->app->make(PublishProduct::class)->handle($product);
    }

    public function test_publishing_writes_an_audit_row_and_leaves_the_queue(): void
    {
        $admin = User::factory()->create();
        $product = $this->app->make(SubmitProductForReview::class)->handle($this->addVersion($this->draftProduct()));

        $published = $this->app->make(PublishProduct::class)->handle($product, $admin->id);

        $this->assertSame(ProductStatus::Published, $published->status);
        $this->assertNotNull($published->reviewed_at);
        $this->assertSame($admin->id, $published->reviewed_by);
        $this->assertNotContains('product', iterator_to_array($this->pendingKinds()));

        $row = AuditLog::query()->where('action', 'commerce.product_published')->sole();
        $this->assertSame($published->uuid, $row->subject_id);
    }

    public function test_rejecting_requires_a_note_and_writes_an_audit_row(): void
    {
        $admin = User::factory()->create();
        $product = $this->app->make(SubmitProductForReview::class)->handle($this->addVersion($this->draftProduct()));

        $this->expectExceptionMessageMatches('/یادداشت/u');
        $this->app->make(RejectProduct::class)->handle($product, $admin->id, '');
    }

    public function test_a_rejected_product_can_be_resubmitted(): void
    {
        $admin = User::factory()->create();
        $product = $this->app->make(SubmitProductForReview::class)->handle($this->addVersion($this->draftProduct()));

        $rejected = $this->app->make(RejectProduct::class)->handle($product, $admin->id, 'توضیحات ناقص است.');
        $this->assertSame(ProductStatus::Rejected, $rejected->status);
        $this->assertSame('توضیحات ناقص است.', $rejected->review_note);

        AuditLog::query()->where('action', 'commerce.product_rejected')->sole();

        $resubmitted = $this->app->make(SubmitProductForReview::class)->handle($rejected);
        $this->assertSame(ProductStatus::InReview, $resubmitted->status);
        $this->assertNull($resubmitted->review_note);
    }

    public function test_retiring_a_published_product_stops_purchases_but_keeps_it_intact(): void
    {
        $admin = User::factory()->create();
        $product = $this->app->make(PublishProduct::class)->handle(
            $this->app->make(SubmitProductForReview::class)->handle($this->addVersion($this->draftProduct())),
            $admin->id,
        );

        $retired = $this->app->make(RetireProduct::class)->handle($product);

        $this->assertSame(ProductStatus::Retired, $retired->status);
        $this->assertFalse($retired->status->purchasable());
    }

    public function test_two_versions_with_the_same_label_are_rejected(): void
    {
        $product = $this->addVersion($this->draftProduct());

        Storage::fake('local');

        $this->expectException(InvalidArgumentException::class);

        $this->app->make(AddProductVersion::class)->handle(
            $product,
            '1.0.0',
            null,
            UploadedFile::fake()->create('again.zip', 10),
        );
    }

    /** @return iterable<string> */
    private function pendingKinds(): iterable
    {
        foreach ($this->app->make(PendingProducts::class)->pendingItems() as $item) {
            yield $item->kind;
        }
    }
}
