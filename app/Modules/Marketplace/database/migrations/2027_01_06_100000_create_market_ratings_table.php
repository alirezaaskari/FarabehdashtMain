<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * امتیاز دوطرفه پس از پایان قرارداد بازار پروژه (بخش ۲۱-۶، DEC-85).
 *
 * هر طرف برای هر قرارداد یک امتیاز می‌دهد. مدیر فقط متن را پنهان می‌کند و
 * عدد سر جایش می‌ماند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained('market_contracts')->cascadeOnDelete();
            $table->foreignId('rater_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ratee_user_id')->constrained('users')->restrictOnDelete();
            $table->string('side', 16);
            $table->unsignedTinyInteger('stars');
            $table->string('comment', 500)->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contract_id', 'rater_user_id']);
            $table->index(['ratee_user_id', 'side']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_ratings');
    }
};
