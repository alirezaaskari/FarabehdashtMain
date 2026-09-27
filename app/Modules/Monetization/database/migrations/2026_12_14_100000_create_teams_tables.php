<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تیم خریدنی (بخش ۱۹-۶، DEC-61): جدا از اشتراک تکی صاحبش، چون هر کاربر
        // فقط یک ردیف `subscriptions` دارد و تیم تعداد صندلی و دوره خودش را دارد.
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('owner_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('name', 80);
            $table->unsignedSmallInteger('seat_count')->default(0);
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        // فقط‌افزودنی، مثل subscription_periods: تعداد صندلی و قیمت واحد روی دوره Snapshot می‌شود.
        Schema::create('team_periods', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedSmallInteger('seats');
            $table->string('billing_cycle', 16);
            $table->unsignedBigInteger('unit_price_toman');
            $table->unsignedBigInteger('price_toman');
            $table->string('status', 16)->default('pending');
            $table->string('payment_source', 16)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('gateway_authority')->nullable()->unique();
            $table->string('gateway_ref_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
        });

        // صندلی یا از اشتراکی می‌آید که مدیر داده (بخش ۱۴) یا از تیم خریده‌شده.
        Schema::table('team_seats', function (Blueprint $table): void {
            $table->foreignId('subscription_id')->nullable()->change();
            $table->foreignId('team_id')->nullable()->after('subscription_id')->constrained('teams')->cascadeOnDelete();
            $table->unique(['team_id', 'member_user_id']);
        });

        Schema::create('team_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('mobile', 11);
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 16)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['mobile', 'status']);
            $table->index(['team_id', 'status']);
        });

        // فقط فایل‌هایی که خود اعضا بارگذاری می‌کنند (DEC-62).
        Schema::create('team_files', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('uploader_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 120);
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 120);
            $table->unsignedInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_files');
        Schema::dropIfExists('team_invitations');
        Schema::table('team_seats', function (Blueprint $table): void {
            $table->dropUnique(['team_id', 'member_user_id']);
            $table->dropConstrainedForeignId('team_id');
        });
        Schema::dropIfExists('team_periods');
        Schema::dropIfExists('teams');
    }
};
