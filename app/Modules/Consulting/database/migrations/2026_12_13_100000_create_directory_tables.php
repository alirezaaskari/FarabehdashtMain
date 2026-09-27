<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دایرکتوری خدمات تخصصی (بخش ۱۹-۵): آزمایشگاه‌ها کنار مشاوران، خدمت‌هایی
 * که هر کدام ارائه می‌دهد و درخواست تماس با آزمایشگاه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultant_profiles', function (Blueprint $table): void {
            // consultant یا laboratory (DEC-58)؛ از نقش تأییدشده صاحب صفحه می‌آید.
            $table->string('kind', 16)->default('consultant')->after('user_id');
            // کلید خدمت‌های فهرست ثابت config/consulting.php (directory.services)، نسخه منتشرشده.
            $table->json('offerings')->nullable()->after('education');

            $table->index(['kind', 'published_at']);
            $table->index(['city', 'published_at']);
        });

        Schema::create('directory_contacts', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('profile_id')->constrained('consultant_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('service', 32)->nullable();
            $table->string('city', 32)->nullable();
            $table->text('message');
            // شماره درخواست‌دهنده فقط با تیک خودش به آزمایشگاه نشان داده می‌شود.
            $table->boolean('share_mobile')->default(false);
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_contacts');

        Schema::table('consultant_profiles', function (Blueprint $table): void {
            $table->dropIndex(['kind', 'published_at']);
            $table->dropIndex(['city', 'published_at']);
            $table->dropColumn(['kind', 'offerings']);
        });
    }
};
