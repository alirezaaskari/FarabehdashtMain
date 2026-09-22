<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * منابع نسخه‌دار یک محتوا.
 *
 * `edition` و `year` جدا از عنوان‌اند و نه داخل آن: بدون ویرایش، استناد به
 * «ISO 9612» معنایی ندارد — ویرایش ۱۹۹۷ و ۲۰۰۹ دو سند متفاوت‌اند. فهرست
 * منابع در نسخه چاپی از همین ردیف‌ها ساخته می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');

            $table->string('title');
            $table->string('publisher')->nullable();
            $table->string('edition', 64)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('url', 512)->nullable();

            $table->timestamps();

            $table->unique(['article_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_references');
    }
};
