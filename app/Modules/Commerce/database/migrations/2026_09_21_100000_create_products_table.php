<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * محصول دیجیتال (فایل، قالب) یک فروشنده.
 *
 * دسته‌بندی موضوعی عمداً نیست: تاکسونومی فروشگاه هنوز تصمیم‌گیری نشده
 * (همان وضعیت باز بانک مواد شیمیایی)؛ اختراعش حدس بود، نه تصمیم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('vendor_user_id')->constrained('users')->restrictOnDelete();

            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_toman')->default(0);

            $table->string('status', 16)->default('draft');
            $table->string('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
