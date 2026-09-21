<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نسخه‌های یک محصول — فقط‌افزودنی.
 *
 * نسخه یک بار ثبت می‌شود و هرگز تغییر نمی‌کند؛ Changelog تاریخچه واقعی است،
 * نه فیلدی که رونویسی شود. خریدار همیشه آخرین نسخه منتشرشده را دانلود
 * می‌کند؛ نسخه‌های قدیمی فقط برای دیدن تاریخچه تغییرات می‌مانند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('version', 32);
            $table->text('changelog')->nullable();

            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 64);

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['product_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_versions');
    }
};
