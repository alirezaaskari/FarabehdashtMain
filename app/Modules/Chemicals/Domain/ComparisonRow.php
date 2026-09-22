<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain;

/**
 * یک ردیف جدول مقایسه — یک ویژگی، یک مقدار به ازای هر ماده.
 */
final readonly class ComparisonRow
{
    /** @param  list<string>  $values  هم‌طول با فهرست مواد؛ خانه خالی «—» است */
    public function __construct(
        public string $label,
        public array $values,
        public bool $numeric = false,
    ) {}
}
