<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دفتر رویداد — فقط افزودنی.
 *
 * هیچ UPDATE و DELETE روی این جدول مجاز نیست؛ مدل آن را در سطح کد هم منع
 * می‌کند. بدون ستون updated_at، چون ردیفی که تغییر نمی‌کند زمان تغییر ندارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            $table->string('action', 80);

            $table->string('subject_type')->nullable();
            $table->string('subject_id', 64)->nullable();

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('context')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
