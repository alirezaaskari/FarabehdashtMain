<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Commerce\Domain\VendorBankAccount;
use App\Support\Audit\AuditEntry;

/**
 * حساب مقصد تسویه ثبت یا عوض شد. دفتر رویداد فقط شناسه را می‌نویسد، نه شبا و
 * نه نام صاحب حساب (قاعده ۷).
 */
final readonly class BankAccountSaved implements AuditableEvent
{
    public function __construct(public VendorBankAccount $account) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.bank_account_saved',
            subjectType: VendorBankAccount::class,
            subjectId: (string) $this->account->id,
            actorId: $this->account->user_id,
        );
    }
}
