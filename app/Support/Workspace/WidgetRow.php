<?php

declare(strict_types=1);

namespace App\Support\Workspace;

/** یک ردیف فهرست در کارت میزکار. */
final readonly class WidgetRow
{
    public function __construct(
        public string $label,
        public ?string $url = null,
        public ?string $meta = null,
    ) {}
}
