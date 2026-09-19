<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فایل‌های آپلودشده.
 *
 * فایل روی دیسک می‌ماند و این جدول فقط فراداده‌اش را نگه می‌دارد. ستون
 * `disk` جداست تا بعداً جابه‌جایی به فضای ابری، مهاجرت داده نخواهد.
 *
 * `alt` برای تصویر اجباری است — قاعده دسترس‌پذیری پروژه — ولی در سطح دیتابیس
 * nullable می‌ماند چون فایل غیرتصویری آن را ندارد؛ اجبار در لایه اعتبارسنجی است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table): void {
            $table->id();

            $table->string('disk', 40)->default('public');
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');

            $table->string('alt', 255)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->nullableMorphs('attachable');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
