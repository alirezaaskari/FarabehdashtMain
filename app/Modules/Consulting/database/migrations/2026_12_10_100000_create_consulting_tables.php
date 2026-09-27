<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // صفحه عمومی مشاور (بخش ۱۹-۲). ستون‌های نمایشی همان نسخه منتشرشده‌اند؛
        // ویرایش تازه در `pending` می‌ماند تا مدیر تأییدش کند و تا آن وقت نسخه
        // قبلی دیده می‌شود. شماره موبایل و ایمیل این‌جا نیست (DEC-51).
        Schema::create('consultant_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            // نشانی صفحه، با نخستین ارسال رزرو و پس از نخستین انتشار ثابت می‌شود.
            $table->string('slug', 60)->nullable()->unique();

            $table->string('display_name', 120)->nullable();
            $table->string('headline', 160)->nullable();
            $table->text('bio')->nullable();
            $table->string('province', 40)->nullable();
            $table->string('city', 40)->nullable();
            $table->unsignedBigInteger('photo_id')->nullable();
            $table->text('experience')->nullable();
            $table->text('education')->nullable();
            $table->timestamp('published_at')->nullable();
            // وقتی نقش مشاور کاربر غیرفعال شود، صفحه بی‌آنکه پاک شود پنهان می‌شود.
            $table->timestamp('hidden_at')->nullable();

            $table->json('pending')->nullable();
            $table->string('status', 16);
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['published_at', 'hidden_at', 'province']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('consultant_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('profile_id')->constrained('consultant_profiles')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->unsignedInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_documents');
        Schema::dropIfExists('consultant_profiles');
    }
};
