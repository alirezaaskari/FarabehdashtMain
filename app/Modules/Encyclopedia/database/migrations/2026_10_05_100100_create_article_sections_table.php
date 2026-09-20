<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بخش‌های یک محتوا.
 *
 * چرا بخش‌ها ردیف جدا هستند و نه یک ستون HTML:
 *
 * ۱. فهرست «در این مقاله» از همین ردیف‌ها ساخته می‌شود، نه از تجزیه HTML.
 * ۲. بلوک ابزار درون متن یک شناسه ابزار است، نه یک تگ در متن؛ پس اگر ابزاری
 *    برداشته شود، بلوک بی‌سروصدا حذف می‌شود نه اینکه پیوند مرده بماند.
 * ۳. متن ذخیره‌شده ساده است و هرگز به‌صورت HTML خام چاپ نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');

            $table->string('heading');
            $table->text('body');

            // بلوک «نکته کلیدی» — اختیاری، یک پاراگراف.
            $table->text('note')->nullable();

            // بلوک ابزار درون متن — شناسه ابزار، نه پیوند.
            $table->string('tool_slug')->nullable();

            $table->timestamps();

            $table->unique(['article_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_sections');
    }
};
