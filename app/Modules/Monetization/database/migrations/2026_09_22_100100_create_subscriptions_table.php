<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();

            // یک اشتراک برای هر کاربر: خرید دوباره، دوره تازه به همین ردیف
            // اضافه می‌کند و `ends_at` را جلو می‌برد. دو ردیف فعال برای یک
            // کاربر یعنی دو پاسخ متفاوت به یک پرسش.
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();

            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();

            $table->string('status', 16)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
