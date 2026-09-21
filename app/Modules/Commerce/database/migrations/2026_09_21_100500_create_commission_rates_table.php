<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نرخ کمیسیون با تاریخ اثر — فقط‌افزودنی.
 *
 * تغییر نرخ یعنی ثبت یک ردیف تازه با `effective_from` در آینده، نه ویرایش
 * ردیف موجود؛ نرخ هر سفارش در لحظه ثبت روی خود سفارش Snapshot می‌شود، پس
 * تغییر این جدول هرگز سفارش‌های گذشته را دستکاری نمی‌کند
 * (`docs/architecture/admin-panel.md` §۳).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('flow', 32);
            $table->unsignedInteger('rate_bp');
            $table->date('effective_from');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['flow', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rates');
    }
};
