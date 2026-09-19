<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات قابل تغییر توسط مدیر.
 *
 * هر چیزی که مدیر باید بدون استقرار تازه عوضش کند اینجاست: کلیدهای درآمدزایی،
 * نرخ کمیسیون، متن‌های ثابت. مقدار به‌صورت JSON ذخیره می‌شود تا نوعش حفظ شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 120)->unique();
            $table->json('value')->nullable();
            $table->string('group', 60)->default('general');
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
