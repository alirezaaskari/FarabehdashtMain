<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پیوند متقابل دستی میان دو محتوا.
 *
 * جهت‌دار است: «الف به ب» لزوماً «ب به الف» نیست. اگر سرمقاله هر دو را
 * بخواهد، دو ردیف ثبت می‌کند — پیوند خودکارِ دوطرفه، صفحه‌ها را با ارجاع‌های
 * بی‌ربط پر می‌کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('linked_article_id')->constrained('articles')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'linked_article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_links');
    }
};
