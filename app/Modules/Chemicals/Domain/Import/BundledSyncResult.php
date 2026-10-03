<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Import;

/**
 * نتیجه همگام‌سازی داده اولیه: هر فهرست شماره‌های CAS است.
 */
final readonly class BundledSyncResult
{
    /**
     * @param  list<string>  $created  ساخته و منتشر شد
     * @param  list<string>  $skipped  از پیش در بانک بود و دست نخورد
     * @param  array<string, string>  $failed  CAS => دلیل
     */
    public function __construct(
        public array $created,
        public array $skipped,
        public array $failed,
    ) {}
}
