<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * محتوای دانشنامه.
 *
 * `reviewer_id` و `reviewed_at` عمداً nullable هستند و در پایگاه داده اجبار
 * نمی‌شوند: پیش‌نویس هنوز بازبین ندارد. قاعده «بدون بازبین منتشر نمی‌شود» در
 * اکشن انتشار است، نه در ستون — چون شرط، **وضعیت** است نه وجود ردیف.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->string('slug')->unique();
            $table->string('type', 32);
            $table->string('status', 32)->default('draft');

            $table->string('title');
            $table->string('summary', 500);

            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('review_due_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->unsignedInteger('view_count')->default(0);

            $table->timestamps();

            // پرس‌وجوی همیشگی فهرست: منتشرشده‌های یک نوع، به ترتیب بازبینی.
            $table->index(['status', 'type', 'reviewed_at']);

            // صف کار مدیر: چه چیزی موعدش گذشته.
            $table->index(['status', 'review_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
