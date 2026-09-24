<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تخفیف مشترک روی یک ردیف سفارش (بخش ۱۴).
 *
 * `unit_price_toman` همان معنای قبلی را دارد: مبلغی که خریدار برای این ردیف
 * پرداخت کرده — پس بازگشت وجه، جمع سفارش و دفتر کل هیچ‌کدام تغییر نمی‌کنند.
 * این ستون فقط می‌گوید چقدر از قیمت فهرست کم شده و چرا آن دو عدد با هم فرق
 * دارند.
 *
 * تخفیف از سهم پلتفرم کم می‌شود نه از سهم فروشنده: فروشنده نباید بابت
 * مشترک‌بودن خریدار کمتر بگیرد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('discount_toman')->default(0)->after('unit_price_toman');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('discount_toman');
        });
    }
};
