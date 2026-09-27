<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * امانت‌های خدمت (بخش ۱۹-۱).
 *
 * پول خودش در دفتر کل است (حساب `service_escrow`)؛ این جدول فقط می‌گوید هر
 * تکه از آن پول مال کدام خرید است و هنوز باز است یا نه. `key` همان کلید
 * idempotency ماژول فراخوان است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_holds', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->string('key', 128)->unique();

            $table->foreignId('payer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('payee_user_id')->constrained('users')->restrictOnDelete();

            $table->unsignedBigInteger('amount_toman');
            $table->unsignedBigInteger('commission_toman');
            $table->unsignedBigInteger('refunded_toman')->default(0);
            $table->string('payment_source', 16);

            $table->string('status', 16)->index();
            $table->string('reference_type', 64);
            $table->string('reference_id', 64);

            $table->timestamp('held_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('close_reason')->nullable();

            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_holds');
    }
};
