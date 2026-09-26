<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webinars', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('instructor_name');
            $table->timestamp('starts_at')->index();
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedInteger('capacity');
            $table->unsignedBigInteger('price_toman')->default(0);
            // پیوند جلسه رمزنگاری‌شده ذخیره می‌شود و فقط به ثبت‌نام‌شده در زمان ورود داده می‌شود.
            $table->text('join_url');
            $table->string('recording_url')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('webinar_registrations', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('webinar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->unsignedBigInteger('price_toman')->default(0);
            $table->string('payment_source')->default('gateway');
            $table->string('gateway_authority')->nullable()->unique();
            $table->string('gateway_ref_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['webinar_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinar_registrations');
        Schema::dropIfExists('webinars');
    }
};
