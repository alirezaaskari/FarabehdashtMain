<?php

declare(strict_types=1);

namespace App\Support\Taxonomy;

/** یک برچسب دسته‌بندی، بیرون از مدل Core. */
final readonly class TermData
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ?string $description = null,
    ) {}
}
