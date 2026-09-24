<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Providers;

use App\Contracts\LedgerBalanceReader;
use App\Contracts\LedgerRecorder;
use App\Contracts\WalletStatementReader;
use App\Modules\Ledger\Console\ReconcileWalletsCommand;
use App\Modules\Ledger\Services\LedgerService;
use App\Modules\Ledger\Services\WalletStatementService;
use App\Support\Modules\ModuleProvider;

/**
 * ماژول هسته مالی.
 *
 * دو دری که ماژول‌های دیگر از آن‌ها عبور می‌کنند: {@see LedgerRecorder} برای
 * نوشتن، {@see LedgerBalanceReader} برای خواندن موجودی یک حساب غیرکیف‌پولی
 * (مثل بدهی فروشنده، برای تسویه) و {@see WalletStatementReader} برای کیف پول
 * کاربر در میزکار. اگر این ماژول خاموش باشد، هیچ‌کدام بسته
 * نمی‌شوند و مصرف‌کننده باید قبل از فراخوانی با `app()->bound()` بررسی کند.
 */
final class LedgerServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Ledger';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(LedgerService::class);
        $this->app->singleton(LedgerRecorder::class, fn (): LedgerService => $this->app->make(LedgerService::class));
        $this->app->singleton(LedgerBalanceReader::class, fn (): LedgerService => $this->app->make(LedgerService::class));

        $this->app->singleton(WalletStatementReader::class, static fn (): WalletStatementService => new WalletStatementService(
            (array) config('ledger.kind_labels', []),
        ));
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ReconcileWalletsCommand::class]);
        }
    }
}
