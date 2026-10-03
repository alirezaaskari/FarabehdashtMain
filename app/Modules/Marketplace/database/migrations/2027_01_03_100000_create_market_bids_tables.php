<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پیشنهاد مجری، گفت‌وگوی هر پیشنهاد، اخطار پیام و دعوت مستقیم (بخش ۲۱-۳).
 *
 * مرحله‌های پیشنهاد JSON روی همان ردیف‌اند؛ قرارداد ۲۱-۴ آن‌ها را منجمد در
 * جدول خودش کپی می‌کند تا ویرایش بعدی چیزی را عوض نکند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_bids', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('project_id')->constrained('market_projects')->cascadeOnDelete();
            $table->foreignId('provider_user_id')->constrained('users')->restrictOnDelete();
            $table->text('cover');
            $table->json('milestones');
            $table->unsignedBigInteger('total_toman');
            $table->unsignedSmallInteger('total_days');
            $table->string('status', 16)->index();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'provider_user_id']);
            $table->index(['provider_user_id', 'created_at']);
        });

        Schema::create('market_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('bid_id')->constrained('market_bids')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->string('status', 16)->index();
            $table->json('flags')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('market_strikes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('market_messages')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'cleared_at']);
        });

        Schema::create('market_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('market_projects')->cascadeOnDelete();
            $table->foreignId('provider_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'provider_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_invites');
        Schema::dropIfExists('market_strikes');
        Schema::dropIfExists('market_messages');
        Schema::dropIfExists('market_bids');
    }
};
