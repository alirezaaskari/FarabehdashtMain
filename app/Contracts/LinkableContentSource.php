<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Linking\LinkableDocument;

/**
 * ماژولی که متن بلند دارد و پیوند داخلی خودکار در آن گذاشته می‌شود.
 *
 * موتور پیوند متن را هنگام بازسازی می‌خواند و تصمیم می‌گیرد کدام مقصدها
 * پیوند بخورند؛ ماژول صاحب متن هنگام نمایش از {@see InternalLinker} تکه‌های
 * پیوندخورده را می‌گیرد.
 */
interface LinkableContentSource
{
    public const TAG = 'linking.content';

    /** @return iterable<LinkableDocument> */
    public function linkableDocuments(): iterable;
}
