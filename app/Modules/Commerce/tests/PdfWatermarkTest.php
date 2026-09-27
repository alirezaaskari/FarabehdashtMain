<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Models\User;
use App\Modules\Commerce\Actions\AddProductVersion;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Services\PdfStamp;
use App\Modules\Commerce\Services\ProductVersionReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Tests\TestCase;

/** نشان خریدار روی دانلود PDF (بخش ۱۸-۱۱). */
final class PdfWatermarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pdf_downloads_stamped_with_every_page_kept(): void
    {
        $original = $this->pdf(3);
        [$product, $buyer] = $this->bought('guide.pdf', $original);

        $this->actingAs($buyer)->get(route('commerce.show', $product->slug))
            ->assertSee('فایل PDF با نشان خریدار دانلود می‌شود');

        $response = $this->actingAs($buyer)->get($this->signedUrl($product))->assertOk();
        $bytes = $response->streamedContent();

        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertNotSame($original, $bytes);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('quiet-template-1.0.0.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame(3, $this->pages($bytes));
    }

    public function test_a_broken_pdf_falls_back_to_the_original(): void
    {
        [$product, $buyer] = $this->bought('broken.pdf', 'not really a pdf');

        $response = $this->actingAs($buyer)->get($this->signedUrl($product))->assertOk();

        $this->assertSame('not really a pdf', $response->streamedContent());
    }

    public function test_other_formats_are_neither_stamped_nor_claimed(): void
    {
        [$product, $buyer] = $this->bought('template.zip', 'zip bytes');

        $this->actingAs($buyer)->get(route('commerce.show', $product->slug))
            ->assertDontSee('نشان خریدار');
        $this->assertSame('zip bytes', $this->actingAs($buyer)->get($this->signedUrl($product))->streamedContent());
    }

    public function test_the_switch_turns_it_off(): void
    {
        config()->set('commerce.watermark.enabled', false);
        $this->app->forgetInstance(PdfStamp::class);
        $original = $this->pdf(1);
        [$product, $buyer] = $this->bought('guide.pdf', $original);

        $this->assertSame($original, $this->actingAs($buyer)->get($this->signedUrl($product))->streamedContent());
    }

    /** @return array{Product, User} */
    private function bought(string $name, string $content): array
    {
        Storage::fake('local');

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'quiet-template',
            'title' => 'قالب گزارش',
            'price_toman' => 100_000,
            'status' => ProductStatus::Published,
        ])->refresh();

        $this->app->make(AddProductVersion::class)->handle($product, '1.0.0', null, UploadedFile::fake()->createWithContent($name, $content));
        $this->app->make(ProductVersionReview::class)->approve($product);

        $buyer = User::factory()->create();
        $order = Order::query()->create([
            'uuid' => (string) Str::uuid7(),
            'buyer_user_id' => $buyer->id,
            'status' => OrderStatus::Paid,
            'total_toman' => 100_000,
            'paid_at' => now(),
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $product->vendor_user_id,
            'unit_price_toman' => 100_000,
            'commission_rate_bp' => 2000,
            'commission_toman' => 20_000,
            'vendor_amount_toman' => 80_000,
        ]);

        return [$product->refresh(), $buyer];
    }

    private function signedUrl(Product $product): string
    {
        return URL::temporarySignedRoute('commerce.download', now()->addMinutes(10), ['product' => $product->id]);
    }

    private function pdf(int $pages): string
    {
        $pdf = new Mpdf(['tempDir' => storage_path('framework/cache/mpdf')]);

        for ($page = 1; $page <= $pages; $page++) {
            if ($page > 1) {
                $pdf->AddPage();
            }
            $pdf->WriteHTML('<p>Page '.$page.'</p>');
        }

        return $pdf->Output('', Destination::STRING_RETURN);
    }

    private function pages(string $bytes): int
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'fbh');
        file_put_contents($path, $bytes);

        return (new Mpdf(['tempDir' => storage_path('framework/cache/mpdf')]))->setSourceFile($path);
    }
}
