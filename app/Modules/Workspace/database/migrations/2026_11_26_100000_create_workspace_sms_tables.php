<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // پیامک اعلان (DEC-39). شماره موبایل این‌جا نیست؛ هنگام ارسال از
        // حساب کاربر خوانده می‌شود تا شماره عوض‌شده جا نماند.
        Schema::create('workspace_sms_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_id')->unique()->constrained('workspace_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('topic', 32);
            $table->string('status', 16);
            $table->timestamp('due_at');
            $table->timestamp('sent_at')->nullable();
            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index(['user_id', 'status', 'sent_at']);
        });

        Schema::create('workspace_sms_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('muted_topics');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_sms_preferences');
        Schema::dropIfExists('workspace_sms_deliveries');
    }
};
