<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * هشدار شغل (۲۰-۴). هر هشدار یک پالایش ذخیره‌شده است یا «مطابق گذرنامه من».
 * `job_alert_matches` هر آگهی را برای هر کاربر یک بار نگه می‌دارد تا اعلان
 * تکراری نرود و خلاصه روزانه پیامکی (DEC-73) بداند چه چیزی تازه است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('city', 40)->nullable();
            $table->unsignedBigInteger('skill_id')->nullable();
            $table->string('employment_type', 20)->nullable();
            $table->boolean('match_passport')->default(false);
            $table->boolean('sms')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('job_alert_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->timestamp('digested_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'posting_id']);
            $table->index(['digested_at', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_alert_matches');
        Schema::dropIfExists('job_alerts');
    }
};
