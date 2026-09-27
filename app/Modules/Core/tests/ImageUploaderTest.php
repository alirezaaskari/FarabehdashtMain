<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Contracts\MediaLibrary;
use App\Models\User;
use App\Modules\Core\Domain\MediaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

/** کتابخانه مدیا: پردازش و ذخیره تصویر (بخش ۱۸-۱۱). */
final class ImageUploaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_a_wide_jpeg_is_scaled_and_stored_as_webp(): void
    {
        $media = $this->app->make(MediaLibrary::class)->storeImage($this->jpeg(2400, 1200), 'site.jpg', null);

        $this->assertSame(1600, $media->width);
        $this->assertSame(800, $media->height);
        $this->assertStringEndsWith('.webp', $media->url);

        $item = MediaItem::query()->sole();
        $this->assertSame('image/webp', $item->mime_type);
        $this->assertSame('site.jpg', $item->original_name);
        Storage::disk('public')->assertExists($item->path);
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring(Storage::disk('public')->get($item->path) ?? '')[2] ?? null);

        $this->assertEquals($media, $this->app->make(MediaLibrary::class)->find($item->id));
        $this->assertSame([$item->id], array_keys($this->app->make(MediaLibrary::class)->findMany([$item->id, 999])));
    }

    public function test_a_small_png_keeps_its_size(): void
    {
        $path = $this->temp();
        $image = imagecreatetruecolor(300, 200);
        imagepng($image, $path);

        $user = User::factory()->create();
        $media = $this->app->make(MediaLibrary::class)->storeImage($path, 'chart.png', $user->id);

        $this->assertSame([300, 200], [$media->width, $media->height]);
        $this->assertSame($user->id, MediaItem::query()->sole()->uploaded_by);
    }

    public function test_anything_but_an_image_is_refused(): void
    {
        $path = $this->temp();
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(MediaLibrary::class)->storeImage($path, 'x.svg', null);
    }

    public function test_an_image_over_the_pixel_limit_is_refused(): void
    {
        config()->set('core.media.max_pixels', 1000);
        $this->app->forgetInstance(MediaLibrary::class);

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(MediaLibrary::class)->storeImage($this->jpeg(100, 100), 'big.jpg', null);
    }

    private function jpeg(int $width, int $height): string
    {
        $path = $this->temp();
        imagejpeg(imagecreatetruecolor($width, $height), $path);

        return $path;
    }

    private function temp(): string
    {
        return (string) tempnam(sys_get_temp_dir(), 'fbh');
    }
}
