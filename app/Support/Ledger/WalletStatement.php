<?php

declare(strict_types=1);

namespace App\Support\Ledger;

/**
 * یک صفحه از گردش کیف پول.
 */
final readonly class WalletStatement
{
    /** @param  list<WalletLine>  $lines */
    public function __construct(
        public array $lines,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
