<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * گزارش اشتباه کاربران درباره صفحه یک ماده.
 *
 * گزارش‌دهنده فقط با شناسه نگه داشته می‌شود؛ متن گزارش خودش منبع پیشنهادی
 * را می‌گوید و مدیر عدد را فقط از همان منبع اصلاح می‌کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substance_error_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('substance_id')->constrained('substances')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('topic', 32);
            $table->text('message');
            $table->string('source_url', 500)->nullable();
            $table->string('status', 16)->default('open');
            $table->string('admin_note', 500)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substance_error_reports');
    }
};
