<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Media\MediaData;
use InvalidArgumentException;

/**
 * آپلود و خواندن تصویر مشترک (بخش ۱۸-۱۱).
 *
 * هر تصویر یک بار پردازش می‌شود: قالب و اندازه بررسی، EXIF (مکان و دستگاه)
 * با بازنویسی پاک، عرض به سقف پیکربندی کوچک و WebP ذخیره می‌شود. ماژول‌ها
 * فقط شناسه نگه می‌دارند و نشانی را از این قرارداد می‌گیرند.
 */
interface MediaLibrary
{
    /**
     * @param  string  $sourcePath  مسیر فایل موقت روی همین سرور
     *
     * @throws InvalidArgumentException اگر فایل تصویر JPEG/PNG/WebP معتبر نباشد یا از سقف بزرگ‌تر باشد
     */
    public function storeImage(string $sourcePath, string $originalName, ?int $uploaderId): MediaData;

    public function find(int $id): ?MediaData;

    /**
     * @param  list<int>  $ids
     * @return array<int, MediaData> کلید: شناسه
     */
    public function findMany(array $ids): array;
}
