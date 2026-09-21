<?php

declare(strict_types=1);

namespace App\Support\Ledger;

/**
 * نتیجه ثبت یک تراکنش.
 *
 * `alreadyRecorded` به فراخوان می‌گوید این همان اجرای اول بوده یا بازپخش یک
 * درخواست قبلی با همان `idempotencyKey` — مثلاً برای تصمیم «آیا پیامک تازه
 * بفرستم یا نه».
 */
final readonly class LedgerReceipt
{
    public function __construct(
        public string $transactionUuid,
        public bool $alreadyRecorded,
    ) {}
}
