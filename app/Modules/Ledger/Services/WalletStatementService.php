<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Contracts\WalletStatementReader;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\Wallet;
use App\Support\Ledger\WalletLine;
use App\Support\Ledger\WalletStatement;
use App\Support\Money;

/**
 * کیف پول از چشم صاحبش: موجودی و گردش.
 *
 * موجودی از کش `wallets` خوانده می‌شود، همان عددی که خرید با کیف پول به آن
 * تکیه می‌کند؛ گردش از `ledger_entries`، که منبع حقیقت است.
 *
 * شرح هر ردیف از نوع تراکنش ساخته می‌شود، نه از `memo`: یادداشت را مدیر مالی
 * برای خودش می‌نویسد (شماره پیگیری بانکی، نام کارمند) و جای نمایش به کاربر
 * نیست.
 */
final readonly class WalletStatementService implements WalletStatementReader
{
    /** @param  array<string, string>  $kindLabels */
    public function __construct(private array $kindLabels) {}

    public function balanceOf(int $userId): Money
    {
        return Wallet::query()->where('user_id', $userId)->first()?->balance() ?? Money::zero();
    }

    public function statement(int $userId, int $page, int $perPage): WalletStatement
    {
        $page = max(1, $page);
        $wallet = Wallet::query()->where('user_id', $userId)->first();

        if ($wallet === null) {
            return new WalletStatement([], 0, $page, $perPage);
        }

        $entries = LedgerEntry::query()
            ->where('account_id', $wallet->ledger_account_id)
            ->with('transaction')
            ->orderByDesc('id');

        $total = (clone $entries)->count();

        $lines = $entries
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (LedgerEntry $entry): WalletLine => new WalletLine(
                reference: $entry->transaction->uuid,
                occurredAt: $entry->created_at,
                description: $this->kindLabels[$entry->transaction->kind] ?? 'تراکنش کیف پول',
                direction: $entry->direction,
                amount: $entry->amount(),
            ))
            ->values()
            ->all();

        return new WalletStatement($lines, $total, $page, $perPage);
    }
}
