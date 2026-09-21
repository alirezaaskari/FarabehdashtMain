<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بازگشت وجه — فقط‌افزودنی. هیچ بازگشتی ویرایش یا حذف نمی‌شود؛ لغو یک
 * بازگشت (در صورت نیاز روزی) خودش یک ردیف مالی تازه در دفتر کل است، نه
 * تغییر این ردیف.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();

            $table->unsignedBigInteger('amount_toman');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
