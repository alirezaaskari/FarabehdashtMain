<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * درخواست کارجو، گفت‌وگوی همان درخواست و دفتر دسترسی به رزومه (۲۰-۲).
 *
 * `resume_access_logs` هر بار که کارفرما شماره یا رزومه کسی را می‌بیند یک
 * ردیف می‌گیرد (DEC-68) و بانک رزومه ۲۰-۵ هم در همین جدول می‌نویسد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('cover_note');
            $table->string('resume_path')->nullable();
            $table->string('resume_name', 190)->nullable();
            $table->unsignedInteger('resume_size_bytes')->default(0);
            $table->boolean('share_contact')->default(false);
            $table->string('status', 20)->default('received');
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();

            $table->unique(['posting_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['posting_id', 'status']);
        });

        Schema::create('job_application_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('resume_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jobseeker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('job_applications')->cascadeOnDelete();
            $table->string('source', 20);
            $table->string('kind', 20);
            $table->timestamp('created_at')->nullable();

            $table->index(['jobseeker_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_access_logs');
        Schema::dropIfExists('job_application_messages');
        Schema::dropIfExists('job_applications');
    }
};
