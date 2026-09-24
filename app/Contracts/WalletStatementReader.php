<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Ledger\WalletStatement;
use App\Support\Money;

/**
 * خواندن کیف پول یک کاربر: موجودی و گردش.
 *
 * فقط خواندن. شارژ کیف پول فقط از پنل مدیر و از مسیر دفتر کل است
 * (ADR-0003)؛ این قرارداد عمداً هیچ متد نوشتنی ندارد.
 */
interface WalletStatementReader
{
    public function balanceOf(int $userId): Money;

    /** گردش کیف پول، تازه‌ترین اول. */
    public function statement(int $userId, int $page, int $perPage): WalletStatement;
}
