<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * درخواست تسویه فروشنده و مدرس (بخش ۱۸-۶، DEC-45).
 *
 * شبا رمزنگاری‌شده ذخیره می‌شود (`text`، چون متن رمز از ۲۶ نویسه بلندتر است).
 * هر درخواست شبا و نام صاحب حساب را همان لحظه برمی‌دارد تا عوض‌کردن حساب
 * بعد از درخواست، مقصد واریزی را که مدیر دیده بی‌صدا عوض نکند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('sheba');
            $table->string('holder_name', 120);
            $table->timestamps();
        });

        Schema::create('payout_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained();
            $table->unsignedBigInteger('amount_toman');
            $table->string('status', 20)->default('requested');
            $table->text('sheba');
            $table->string('holder_name', 120);
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->string('bank_reference', 120)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
        Schema::dropIfExists('vendor_bank_accounts');
    }
};
