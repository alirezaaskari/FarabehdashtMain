<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تک‌فروشی گزارش (بخش ۱۸-۵، DEC-44). هر پیش‌نویس حداکثر یک خرید دارد:
        // پرداخت رهاشده همان ردیف را با قیمت امروز دوباره «در انتظار» می‌کند،
        // مثل ثبت‌نام دوره. قیمت Snapshot لحظه خرید است.
        Schema::create('report_purchases', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('report_id')->unique()->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('price_toman');
            $table->string('payment_source', 16)->default('gateway');
            $table->string('gateway_authority', 64)->nullable()->unique();
            $table->string('gateway_ref_id', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_purchases');
    }
};
