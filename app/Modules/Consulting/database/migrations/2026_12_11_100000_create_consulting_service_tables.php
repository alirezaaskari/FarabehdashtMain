<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // خدمتی که مشاور می‌فروشد (بخش ۱۹-۳). پیش از انتشار تأیید مدیر می‌خواهد.
        Schema::create('consulting_services', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('profile_id')->constrained('consultant_profiles')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('title');
            $table->text('description');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedBigInteger('price_toman');
            // کلید شهرهایی که بازدید حضوری در آن‌ها ممکن است (config/regions.php).
            $table->json('cities')->nullable();

            $table->string('status', 16);
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });

        // هر خرید خدمت. پول از لحظه پرداخت تا پایان کار در امانت دفتر کل است
        // (`escrow_uuid`)؛ قیمت و کمیسیون همان لحظه ثابت می‌شوند.
        Schema::create('consulting_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('service_id')->constrained('consulting_services')->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('consultant_id')->constrained('users')->restrictOnDelete();

            $table->unsignedBigInteger('price_toman');
            $table->unsignedBigInteger('commission_toman')->default(0);
            $table->text('need');
            $table->json('proposed_times');
            $table->string('city', 40)->nullable();
            // DEC-55: شماره خریدار فقط با این تیک به مشاور نشان داده می‌شود.
            $table->boolean('share_mobile')->default(false);

            $table->string('status', 24);
            $table->string('payment_source', 16)->nullable();
            $table->string('gateway_authority')->nullable()->index();
            $table->string('gateway_ref_id')->nullable();
            $table->uuid('escrow_uuid')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('scheduled_for')->nullable();
            $table->string('meeting_link')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('close_note')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('refunded_toman')->default(0);
            $table->timestamps();

            $table->index(['buyer_id', 'created_at']);
            $table->index(['consultant_id', 'status']);
            $table->index(['status', 'paid_at']);
            $table->index(['status', 'delivered_at']);
        });

        // گفت‌وگوی خریدار و مشاور داخل صفحه درخواست (DEC-55).
        Schema::create('consulting_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('consulting_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_messages');
        Schema::dropIfExists('consulting_orders');
        Schema::dropIfExists('consulting_services');
    }
};
