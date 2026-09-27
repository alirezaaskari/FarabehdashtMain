<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بانک رزومه (۲۰-۵). عضویت روی گذرنامه است و پیش‌فرض خاموش (Opt-in).
 * `bank_token` جدا از نشانی اشتراکی است تا کارت ناشناس به صفحه‌ای با نام
 * راه نبرد.
 *
 * اعتبار کارفرما جدول جدا ندارد: جمع بسته‌های پرداخت‌شده منهای درخواست‌هایی
 * که اعتبار گرفته‌اند و هنوز «در انتظار» یا «پذیرفته» هستند (DEC-72).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passports', function (Blueprint $table): void {
            $table->boolean('in_bank')->default(false)->index();
            $table->uuid('bank_token')->nullable()->unique();
            $table->timestamp('bank_joined_at')->nullable();
        });

        Schema::create('resume_bank_packages', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedInteger('credits');
            $table->unsignedBigInteger('price_toman');
            $table->string('status', 16)->index();
            $table->string('payment_source', 16)->nullable();
            $table->string('gateway_authority')->nullable()->index();
            $table->string('gateway_ref_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('resume_bank_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('jobseeker_id')->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->string('status', 16);
            // وقتی کلید درآمد خاموش بود، درخواست اعتبار نگرفت و چیزی برنمی‌گردد.
            $table->boolean('charged')->default(true);
            $table->timestamp('expires_at');
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'jobseeker_id']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_bank_requests');
        Schema::dropIfExists('resume_bank_packages');

        Schema::table('passports', function (Blueprint $table): void {
            $table->dropUnique(['bank_token']);
            $table->dropIndex(['in_bank']);
            $table->dropColumn(['in_bank', 'bank_token', 'bank_joined_at']);
        });
    }
};
