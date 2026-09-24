<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پیوند داخلی: عکس فوری مقصدها، پیوندهای گذاشته‌شده و فهرست مسدود.
 *
 * دو جدول اول هر بار از نو ساخته می‌شوند (DEC-31) و داده‌ای از دست نمی‌رود؛
 * فقط فهرست مسدود ورودی مدیر است. عنوان و نشانی کنار کلید نگه داشته می‌شود
 * تا نمایش «مرتبط» به ماژول صاحب صفحه سر نزند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_targets', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191)->unique();
            $table->string('title');
            $table->string('url', 500);
            $table->timestamps();
        });

        Schema::create('internal_links', function (Blueprint $table): void {
            $table->id();
            $table->string('source_key', 191);
            $table->string('source_title');
            $table->string('source_url', 500);
            $table->string('target_key', 191)->index();
            $table->string('target_title');
            $table->string('target_url', 500);
            $table->string('phrase');
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['source_key', 'target_key']);
        });

        Schema::create('link_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('phrase')->nullable();
            $table->string('source_key', 191)->nullable();
            $table->string('target_key', 191)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_blocks');
        Schema::dropIfExists('internal_links');
        Schema::dropIfExists('link_targets');
    }
};
