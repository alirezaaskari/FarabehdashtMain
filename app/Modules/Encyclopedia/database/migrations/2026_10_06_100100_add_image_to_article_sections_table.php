<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تصویر اختیاری هر بخش مقاله (بخش ۱۸-۱۱).
 *
 * `image_id` شناسه تصویر در کتابخانه مدیای Core است؛ کلید خارجی ندارد چون
 * جدول مال ماژول دیگری است (قاعده ۱). متن جایگزین و زیرنویس مال همین کاربرد
 * است، نه خود تصویر: یک عکس در دو مقاله می‌تواند دو توضیح متفاوت بخواهد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_sections', static function (Blueprint $table): void {
            $table->unsignedBigInteger('image_id')->nullable()->after('tool_slug');
            $table->string('image_alt', 255)->nullable()->after('image_id');
            $table->string('image_caption', 500)->nullable()->after('image_alt');
        });
    }

    public function down(): void
    {
        Schema::table('article_sections', static function (Blueprint $table): void {
            $table->dropColumn(['image_id', 'image_alt', 'image_caption']);
        });
    }
};
