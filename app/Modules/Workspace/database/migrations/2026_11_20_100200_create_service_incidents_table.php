<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // وضعیت هر سرویس از رویدادهای باز همین جدول ساخته می‌شود و نوار ۴۵
        // روزه از رویدادهای گذشته‌اش؛ جدول جداگانه‌ای برای «وضعیت فعلی» نیست
        // تا دو منبع حقیقت با هم اختلاف پیدا نکنند (DEC-23).
        Schema::create('service_incidents', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->string('service', 32);
            $table->string('state', 16);
            $table->string('title');
            $table->text('body')->nullable();

            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable();

            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service', 'started_at']);
            $table->index('resolved_at');
        });

        Schema::create('incident_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incident_id')->constrained('service_incidents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['incident_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_subscriptions');
        Schema::dropIfExists('service_incidents');
    }
};
