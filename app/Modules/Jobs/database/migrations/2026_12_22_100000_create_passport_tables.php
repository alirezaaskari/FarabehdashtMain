<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * گذرنامه مهارتی (۲۰-۳): یک ردیف برای هر کاربر و سطرهای «به اظهار خود کاربر».
 *
 * بخش «ثبت‌شده در فرابهداشت» جدول ندارد؛ هر بار از منبع‌های ماژول‌ها خوانده
 * می‌شود تا با بازپرداخت دوره یا باطل‌شدن گزارش خودش به‌روز بماند.
 * مهارت‌های اظهاری برچسب‌های `job_skill` روی همین ردیف‌اند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('share_token')->unique();
            $table->boolean('shared')->default(false);
            $table->string('headline', 150)->nullable();
            $table->string('province', 40)->nullable();
            $table->string('city', 40)->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->timestamps();
        });

        Schema::create('passport_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('passport_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title', 150);
            $table->string('organization', 150)->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['passport_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_entries');
        Schema::dropIfExists('passports');
    }
};
