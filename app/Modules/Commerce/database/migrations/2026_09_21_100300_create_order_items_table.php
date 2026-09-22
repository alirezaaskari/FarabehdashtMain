<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * یک ردیف سفارش — قیمت، فروشنده و نرخ کمیسیون همگی در لحظه خرید Snapshot
 * می‌شوند. تغییر بعدی قیمت محصول یا نرخ کمیسیون هرگز روی سفارش‌های گذشته
 * اثر نمی‌گذارد (ADR-0003، اصل «مرجع یگانه کمیسیون»).
 *
 * مبلغ بازگشتی این ردیف این‌جا کش نمی‌شود؛ همیشه از جمع `refunds` همین
 * ردیف محاسبه می‌شود — دقیقاً همان دلیلی که موجودی کیف پول را کش می‌کند ولی
 * با یک دستور تطبیق اختصاصی، این‌جا چون هیچ مسیر پرترافیکی به آن نیاز ندارد
 * ارزش آن پیچیدگی را ندارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('vendor_user_id')->constrained('users')->restrictOnDelete();

            $table->unsignedBigInteger('unit_price_toman');
            $table->unsignedInteger('commission_rate_bp');
            $table->unsignedBigInteger('commission_toman');
            $table->unsignedBigInteger('vendor_amount_toman');

            $table->timestamps();

            $table->index('vendor_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
