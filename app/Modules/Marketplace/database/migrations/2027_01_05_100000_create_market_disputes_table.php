<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اعتراض، رأی مدیر و لغو در بازار پروژه (بخش ۲۱-۵).
 *
 * هر اعتراض روی یک مرحله است و رأی مدیر مبلغ برگشتی به کارفرما را ثبت
 * می‌کند؛ صفر یعنی آزادسازی کامل، کل مبلغ یعنی بازگشت کامل.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_disputes', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('milestone_id')->constrained('market_milestones')->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->unsignedBigInteger('to_client_toman')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('market_milestones', function (Blueprint $table): void {
            $table->timestamp('cancel_requested_at')->nullable()->after('released_at');
            $table->unsignedBigInteger('refunded_toman')->nullable()->after('cancel_requested_at');
        });

        Schema::table('market_contracts', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('lapsed_at');
        });
    }

    public function down(): void
    {
        Schema::table('market_contracts', function (Blueprint $table): void {
            $table->dropColumn('cancelled_at');
        });

        Schema::table('market_milestones', function (Blueprint $table): void {
            $table->dropColumn(['cancel_requested_at', 'refunded_toman']);
        });

        Schema::dropIfExists('market_disputes');
    }
};
