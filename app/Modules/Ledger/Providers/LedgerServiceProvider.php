<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Providers;

use App\Contracts\LedgerRecorder;
use App\Modules\Ledger\Services\LedgerService;
use App\Support\Modules\ModuleProvider;

/**
 * ماژول هسته مالی.
 *
 * تنها دری که ماژول‌های دیگر برای ثبت تراکنش مالی از آن عبور می‌کنند
 * {@see LedgerRecorder} است. اگر این ماژول خاموش باشد، قرارداد بسته
 * نمی‌شود و مصرف‌کننده باید قبل از فراخوانی با `app()->bound()` بررسی کند.
 */
final class LedgerServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Ledger';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(LedgerRecorder::class, LedgerService::class);
    }
}
