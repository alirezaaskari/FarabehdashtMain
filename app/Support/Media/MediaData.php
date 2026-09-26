<?php

declare(strict_types=1);

namespace App\Support\Media;

/** یک تصویر ذخیره‌شده، بیرون از مدل Core. */
final readonly class MediaData
{
    public function __construct(
        public int $id,
        public string $url,
        public int $width,
        public int $height,
        public string $originalName,
        public int $sizeBytes,
    ) {}
}
