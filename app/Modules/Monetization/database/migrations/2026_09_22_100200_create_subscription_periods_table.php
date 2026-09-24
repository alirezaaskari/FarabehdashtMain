<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فقط‌افزودنی: هر دوره پرداخت یک ردیف است و هیچ‌وقت پاک نمی‌شود.
        // قیمت و دوره صورتحساب روی همین ردیف Snapshot می‌شوند تا تغییر قیمت
        // پلن، صورتحساب‌های گذشته را جابه‌جا نکند.
        Schema::create('subscription_periods', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->restrictOnDelete();

            $table->string('billing_cycle', 16);
            $table->unsignedBigInteger('price_toman');

            $table->string('status', 16)->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->string('gateway_authority')->nullable()->unique();
            $table->string('gateway_ref_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_periods');
    }
};
