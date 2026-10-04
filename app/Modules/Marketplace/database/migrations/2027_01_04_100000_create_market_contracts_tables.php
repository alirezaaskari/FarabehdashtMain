<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قرارداد، مرحله‌ها و تحویل‌ها (بخش ۲۱-۴).
 *
 * مرحله‌ها از پیشنهاد پذیرفته‌شده کپی و منجمد می‌شوند. نرخ کمیسیون لحظه
 * پذیرش روی قرارداد ثبت می‌شود تا تغییر نرخ بعدی قرارداد جاری را عوض نکند.
 * هر مرحله امانت جدای خودش را دارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_contracts', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('project_id')->constrained('market_projects')->restrictOnDelete();
            $table->foreignId('bid_id')->unique()->constrained('market_bids')->restrictOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('total_toman');
            $table->unsignedSmallInteger('commission_bp');
            $table->string('status', 24)->index();
            $table->timestamp('pay_by');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('lapsed_at')->nullable();
            $table->timestamps();
            $table->index(['provider_user_id', 'status']);
        });

        Schema::create('market_milestones', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('contract_id')->constrained('market_contracts')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('title', 160);
            $table->unsignedBigInteger('amount_toman');
            $table->unsignedSmallInteger('days');
            $table->string('status', 16)->index();
            $table->unsignedBigInteger('commission_toman')->nullable();
            $table->uuid('escrow_uuid')->nullable();
            $table->string('payment_source', 16)->nullable();
            $table->string('gateway_authority', 64)->nullable()->index();
            $table->string('gateway_ref_id', 64)->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('release_at')->nullable()->index();
            $table->unsignedTinyInteger('revisions')->default(0);
            $table->timestamp('released_at')->nullable();
            $table->boolean('auto_released')->default(false);
            $table->timestamps();
            $table->unique(['contract_id', 'position']);
        });

        Schema::create('market_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('milestone_id')->constrained('market_milestones')->cascadeOnDelete();
            $table->text('note');
            $table->text('revision_note')->nullable();
            $table->timestamp('revision_requested_at')->nullable();
            $table->timestamps();
        });

        Schema::create('market_delivery_files', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('delivery_id')->constrained('market_deliveries')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->unsignedInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_delivery_files');
        Schema::dropIfExists('market_deliveries');
        Schema::dropIfExists('market_milestones');
        Schema::dropIfExists('market_contracts');
    }
};
