<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Import;

/**
 * نتیجه بررسی یک ردیف CSV — پیش از هرگونه نوشتن در پایگاه داده.
 */
final readonly class ImportRowResult
{
    /**
     * @param  array<string, string>  $data  داده خام ردیف، ستون => مقدار
     * @param  list<FieldChange>  $changes  فقط برای action=Update پر است
     */
    public function __construct(
        public int $line,
        public ImportAction $action,
        public array $data,
        public array $changes = [],
        public ?string $reason = null,
    ) {}
}
