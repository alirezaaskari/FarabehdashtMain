<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کیف پول کاربر — تنها جدولی در این ماژول که تغییر می‌کند.
 *
 * `cached_balance_toman` صرفاً کش جمع `ledger_entries` است (ADR-0003): فقط
 * داخل همان تراکنش دیتابیسی که ردیف دفتر کل را می‌نویسد به‌روز می‌شود و
 * هرگز از بیرون سرویس دفتر کل نوشته نمی‌شود. `Wallet::applyLedgerEntry()`
 * تنها راه مجاز تغییر این ستون است؛ به همین دلیل خارج از `$fillable` است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->unique()->constrained('ledger_accounts')->restrictOnDelete();

            $table->unsignedBigInteger('cached_balance_toman')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
