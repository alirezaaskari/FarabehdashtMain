<?php

declare(strict_types=1);

namespace App\Support\Ledger;

/**
 * درخواست ثبت یک تراکنش در دفتر کل.
 *
 * `idempotencyKey` قلب معیار پذیرش بخش ۱۱ است: اجرای دوباره همین درخواست با
 * همان کلید، ردیف تازه نمی‌سازد — رسید همان تراکنش قبلی برمی‌گردد. ماژول
 * فراخوان کلید را می‌سازد (مثلاً «شناسه رویداد پرداخت» یا «شناسه سفارش +
 * نوع عملیات») تا برای همان عملیات همیشه یکسان بماند.
 *
 * جمع بستانکار منهای بدهکار `entries` باید صفر شود؛ این بررسی در
 * `LedgerService::record()` انجام می‌شود، نه این‌جا — DTO فقط داده نگه
 * می‌دارد.
 */
final readonly class LedgerTransactionRequest
{
    /** @param  list<LedgerEntryLine>  $entries */
    public function __construct(
        public string $kind,
        public string $idempotencyKey,
        public array $entries,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public ?string $memo = null,
        public ?int $createdBy = null,
    ) {}
}
