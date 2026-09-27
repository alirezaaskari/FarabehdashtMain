<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Contracts\MediaLibrary;
use App\Modules\Core\Domain\MediaItem;
use App\Support\Media\MediaData;
use GdImage;
use Illuminate\Contracts\Filesystem\Factory as Filesystems;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * پیاده‌سازی {@see MediaLibrary} با GD.
 *
 * چرا بازنویسی همیشگی و نه کپی فایل اصلی: عکس گوشی مختصات GPS و مدل دستگاه
 * را در EXIF دارد و نباید روی سایت عمومی برود؛ WebP با عرض محدود هم صفحه را
 * روی اینترنت ضعیف سبک نگه می‌دارد. سقف پیکسل جلوی تصویری را می‌گیرد که حافظه
 * هاست اشتراکی را در بازکردن تمام کند.
 */
final readonly class ImageUploader implements MediaLibrary
{
    private const TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    /** @param  array{disk: string, max_width: int, quality: int, max_bytes: int, max_pixels: int}  $config */
    public function __construct(
        private Filesystems $files,
        private array $config,
    ) {}

    public function storeImage(string $sourcePath, string $originalName, ?int $uploaderId): MediaData
    {
        $size = @filesize($sourcePath);
        $info = @getimagesize($sourcePath);

        if ($size === false || $info === false || ! in_array($info[2], self::TYPES, true)) {
            throw new InvalidArgumentException('فقط تصویر JPEG، PNG یا WebP پذیرفته می‌شود.');
        }

        if ($size > $this->config['max_bytes'] || $this->config['max_pixels'] < $info[0] * $info[1]) {
            throw new InvalidArgumentException('تصویر بزرگ‌تر از سقف مجاز است؛ نسخه کوچک‌تری بفرستید.');
        }

        $image = $this->scaled($this->oriented($this->open($sourcePath, $info[2]), $sourcePath, $info[2]));

        ob_start();
        imagewebp($image, null, $this->config['quality']);
        $webp = (string) ob_get_clean();

        $path = 'media/'.now()->format('Y/m').'/'.Str::uuid7().'.webp';
        $disk = $this->files->disk($this->config['disk']);
        $disk->put($path, $webp, 'public');

        try {
            $item = $this->record($path, $originalName, $webp, $image, $uploaderId);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }

        return $this->data($item);
    }

    private function record(string $path, string $originalName, string $webp, GdImage $image, ?int $uploaderId): MediaItem
    {
        return MediaItem::query()->create([
            'disk' => $this->config['disk'],
            'path' => $path,
            'original_name' => Str::limit($originalName, 250, ''),
            'mime_type' => 'image/webp',
            'size_bytes' => strlen($webp),
            'width' => imagesx($image),
            'height' => imagesy($image),
            'uploaded_by' => $uploaderId,
        ]);
    }

    public function find(int $id): ?MediaData
    {
        $item = MediaItem::query()->find($id);

        return $item === null ? null : $this->data($item);
    }

    public function findMany(array $ids): array
    {
        $found = [];

        foreach (MediaItem::query()->whereIn('id', $ids)->get() as $item) {
            $found[$item->id] = $this->data($item);
        }

        return $found;
    }

    private function open(string $path, int $type): GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            default => @imagecreatefromwebp($path),
        };

        if (! $image instanceof GdImage) {
            throw new InvalidArgumentException('تصویر خوانده نشد؛ شاید فایل خراب است.');
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    /** عکس گوشی جهتش را در EXIF می‌گوید؛ بعد از پاک‌شدن EXIF باید خود پیکسل‌ها بچرخند. */
    private function oriented(GdImage $image, string $path, int $type): GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $angle = match (is_array($exif) ? ($exif['Orientation'] ?? 1) : 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private function scaled(GdImage $image): GdImage
    {
        if (imagesx($image) <= $this->config['max_width']) {
            return $image;
        }

        $scaled = imagescale($image, $this->config['max_width'], -1, IMG_BICUBIC);

        if (! $scaled instanceof GdImage) {
            return $image;
        }

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);

        return $scaled;
    }

    private function data(MediaItem $item): MediaData
    {
        return new MediaData(
            id: $item->id,
            url: $this->files->disk($item->disk)->url($item->path),
            width: (int) $item->width,
            height: (int) $item->height,
            originalName: $item->original_name,
            sizeBytes: $item->size_bytes,
        );
    }
}
