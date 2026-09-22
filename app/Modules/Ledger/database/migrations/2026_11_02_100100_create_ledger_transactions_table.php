<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تراکنش‌های دفتر کل — فقط‌افزودنی.
 *
 * `idempotency_key` یکتاست و همان چیزی است که معیار پذیرش این بخش را
 * برآورده می‌کند: «اجرای دوباره با همان کلید اثر دوم ندارد.» تلاش دوم برای
 * نوشتن با همان کلید، در `LedgerRecorder` به تراکنش موجود برمی‌گردد، نه به
 * ردیف تازه.
 *
 * بازگشت وجه، تراکنش این جدول را هرگز ویرایش یا حذف نمی‌کند (ADR-0003)؛
 * یک تراکنش معکوس تازه می‌سازد که `reference` به تراکنش اصلی اشاره کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();

            // نوع تراکنش، مثل «wallet_topup»، «purchase»، «refund».
            $table->string('kind', 64);

            $table->string('idempotency_key')->unique();

            // موجودیتی که این تراکنش برایش نوشته شده — اختیاری، آزاد از نوع
            // ماژول: خودِ Ledger معنای reference_type را نمی‌داند.
            $table->string('reference_type', 64)->nullable();
            $table->string('reference_id', 64)->nullable();

            $table->string('memo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
