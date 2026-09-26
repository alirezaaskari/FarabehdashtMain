<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌های راه‌حل (بخش ۱۸-۸).
 *
 * اجزا با «نوع + شناسه» ذخیره می‌شوند، نه کلید خارجی به جدول ماژول دیگر
 * (قاعده ۱). هر خرید سهم هر جزء را همان لحظه در bundle_purchase_lines
 * نگه می‌دارد تا تغییر قیمت یا کمیسیون بعدی خرید گذشته را عوض نکند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundles', static function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->string('slug', 120)->unique();
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('price_toman');
            $table->string('status', 16)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bundle_items', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bundle_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('ref', 64);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unique(['bundle_id', 'kind', 'ref']);
        });

        Schema::create('bundle_purchases', static function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('bundle_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('price_toman');
            $table->string('payment_source', 16)->default('gateway');
            $table->string('gateway_authority')->nullable()->unique();
            $table->string('gateway_ref_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('bundle_purchase_lines', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bundle_purchase_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('ref', 64);
            $table->string('title');
            $table->unsignedBigInteger('list_price_toman');
            $table->unsignedBigInteger('allocated_toman');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('owner_amount_toman')->default(0);
            $table->unsignedBigInteger('platform_amount_toman');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_purchase_lines');
        Schema::dropIfExists('bundle_purchases');
        Schema::dropIfExists('bundle_items');
        Schema::dropIfExists('bundles');
    }
};
