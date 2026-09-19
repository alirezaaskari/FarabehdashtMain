<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('status', 20);

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_note')->nullable();

            /** داده اختصاصی هر نقش — بدون اثر بر ساختار جدول. */
            $table->json('meta')->nullable();

            $table->timestamps();

            // هر کاربر از هر نقش فقط یک پروفایل دارد. حساب دوم هرگز ساخته نمی‌شود.
            $table->unique(['user_id', 'type']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
