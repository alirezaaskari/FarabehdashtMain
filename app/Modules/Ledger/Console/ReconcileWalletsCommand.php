<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Console;

use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\Wallet;
use App\Support\Ledger\EntryDirection;
use Illuminate\Console\Command;

/**
 * تطبیق موجودی کش‌شده کیف پول با جمع واقعی دفتر کل (ADR-0003).
 *
 * `wallets.cached_balance_toman` فقط از راه `Wallet::applyLedgerEntry()`
 * نوشته می‌شود، ولی کش همیشه کش است — این دستور ضامن آن است که این دو عدد
 * واقعاً برابرند، نه اینکه امیدوار باشیم برابرند.
 *
 * همیشه اول گزارش می‌دهد؛ فقط با `--apply` کش را با عدد واقعی جایگزین می‌کند.
 */
final class ReconcileWalletsCommand extends Command
{
    protected $signature = 'fbh:reconcile-wallets {--apply : جایگزینی کش با عدد واقعی، نه فقط گزارش}';

    protected $description = 'مقایسه موجودی کش‌شده کیف پول‌ها با جمع واقعی دفتر کل';

    public function handle(): int
    {
        $actualBalances = LedgerEntry::query()
            ->selectRaw(
                'account_id, SUM(CASE WHEN direction = ? THEN amount_toman ELSE -amount_toman END) as actual',
                [EntryDirection::Credit->value],
            )
            ->groupBy('account_id')
            ->pluck('actual', 'account_id');

        $mismatches = [];

        foreach (Wallet::query()->cursor() as $wallet) {
            $actual = (int) ($actualBalances[$wallet->ledger_account_id] ?? 0);

            if ($actual === $wallet->cached_balance_toman) {
                continue;
            }

            $mismatches[] = [
                $wallet->uuid,
                $wallet->user_id,
                $wallet->cached_balance_toman,
                $actual,
                $actual - $wallet->cached_balance_toman,
            ];

            if ($this->option('apply')) {
                $wallet->forceFill(['cached_balance_toman' => $actual])->save();
            }
        }

        if ($mismatches === []) {
            $this->info('همه کیف پول‌ها با دفتر کل تطبیق دارند.');

            return self::SUCCESS;
        }

        $this->table(['کیف پول', 'کاربر', 'کش‌شده', 'واقعی', 'اختلاف'], $mismatches);

        if (! $this->option('apply')) {
            $this->warn(sprintf('%d مغایرت پیدا شد. برای اصلاح: --apply', count($mismatches)));

            return self::FAILURE;
        }

        $this->info(sprintf('%d کیف پول اصلاح شد.', count($mismatches)));

        return self::SUCCESS;
    }
}
