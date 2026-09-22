<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ردیف‌های دفتر کل — فقط‌افزودنی.
 *
 * ناوردای اصلی ADR-0003 روی همین جدول است: در هر `transaction_id`، مجموع
 * بستانکار منهای مجموع بدهکار باید دقیقاً صفر باشد. `LedgerRecorder` این را
 * پیش از نوشتن بررسی می‌کند و تستی روی کل جدول همین را دوباره می‌سنجد.
 *
 * `amount_toman` هرگز منفی نیست؛ جهت با ستون `direction` بیان می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('ledger_transactions')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('ledger_accounts')->restrictOnDelete();

            $table->string('direction', 8);
            $table->unsignedBigInteger('amount_toman');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_id', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
