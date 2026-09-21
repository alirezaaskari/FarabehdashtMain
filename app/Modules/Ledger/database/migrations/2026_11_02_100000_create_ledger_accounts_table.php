<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حساب‌های دفتر کل.
 *
 * یکتایی روی (نوع، نوع مالک، شناسه مالک): یک کاربر بیش از یک کیف پول ندارد،
 * یک پروژه بیش از یک حساب امانت ندارد. حساب‌های تک‌نمونه‌ای مثل خزانه یا
 * درآمد پلتفرم، `owner_id` را null می‌گذارند.
 *
 * این جدول **فقط‌افزودنی** است؛ هیچ حسابی بعد از ساخته‌شدن تغییر نمی‌کند —
 * حتی نوعش. اگر یک حساب اشتباه ساخته شود، حساب اصلاح‌شده تازه می‌سازیم، نه
 * ویرایش این یکی.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();

            $table->string('type', 32);
            $table->string('owner_type', 32)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();

            // پول فقط تومان است (قاعده ۶)؛ این ستون فقط برای هم‌خوانی با
            // طرحی است که ADR-0003 برای گسترش آینده در نظر گرفته و امروز
            // همیشه «toman» می‌ماند.
            $table->string('currency', 8)->default('toman');

            $table->timestamps();

            $table->unique(['type', 'owner_type', 'owner_id'], 'ledger_accounts_owner_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_accounts');
    }
};
