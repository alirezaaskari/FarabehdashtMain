<?php

declare(strict_types=1);

namespace App\Modules\Linking\Listeners;

use App\Contracts\LinkableContentChanged;
use App\Modules\Linking\Actions\RebuildLinks;

/**
 * بازسازی پیوندها وقتی محتوای عمومی عوض شد.
 *
 * بازسازی کامل است، نه جزئی: عنوان تازه یک مقاله می‌تواند در متن ده مقاله
 * دیگر پیوند تازه بسازد. روی سایتی به اندازه امروز چند صد میلی‌ثانیه است.
 */
final readonly class RebuildLinksOnChange
{
    public function __construct(private RebuildLinks $rebuild) {}

    public function handle(LinkableContentChanged $event): void
    {
        if ($event->affectsPublicLinks()) {
            $this->rebuild->handle();
        }
    }
}
