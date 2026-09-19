<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نقش‌های مدیریتی.
 *
 * جدا از `user_profiles` است و عمداً هم باید جدا بماند: پروفایل را کاربر خودش
 * درخواست می‌دهد، نقش مدیریتی را فقط مدیر ارشد اعطا می‌کند. یکی‌کردن این دو
 * یعنی یک مسیر عمومی درخواست، به جدولی می‌رسد که دسترسی مالی می‌دهد.
 *
 * `granted_by` اجباری نیست فقط به این دلیل که مدیر اول با دستور کنسول ساخته
 * می‌شود و اعطاکننده‌ای ندارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);

            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'role']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
