<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // پیش‌نویس و گزارش صادرشده در یک جدول‌اند؛ صدور فقط وضعیت را عوض
        // می‌کند و Snapshot و فایل را می‌نویسد. پس از صدور هیچ ستون محتوایی
        // تغییر نمی‌کند — اصلاح یعنی ردیف تازه با `supersedes_id`.
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 16)->default('draft');

            $table->string('source_key', 32);
            $table->json('source_references');

            $table->string('title')->nullable();
            $table->string('client_name')->nullable();
            $table->string('site')->nullable();
            $table->string('measured_on', 64)->nullable();
            $table->string('author_name')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('include_equipment')->default(true);
            $table->boolean('include_method')->default(true);

            $table->unsignedSmallInteger('revision')->default(1);
            $table->foreignId('supersedes_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->foreignId('superseded_by_id')->nullable()->constrained('reports')->nullOnDelete();

            // شناسه رهگیری فقط هنگام صدور ساخته می‌شود؛ پیش‌نویس شناسه ندارد
            // تا صفحه تأیید هرگز چیزی صادرنشده را «معتبر» نشان ندهد.
            $table->string('tracking_code', 16)->nullable()->unique();
            $table->json('snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->char('pdf_sha256', 64)->nullable();
            $table->timestamp('issued_at')->nullable();

            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
