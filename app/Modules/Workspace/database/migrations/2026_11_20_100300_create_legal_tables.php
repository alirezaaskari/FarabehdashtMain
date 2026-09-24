<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // نسخه‌های صفحات حقوقی فقط‌افزودنی‌اند: متنی که کاربر پذیرفته، باید
        // همان‌طور که بود بماند. اصلاح یعنی نسخه تازه.
        Schema::create('legal_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->string('document', 32);
            $table->unsignedInteger('version');
            $table->longText('body');
            $table->string('summary', 500)->nullable();
            $table->string('change', 16);
            $table->timestamp('effective_at');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['document', 'version']);
            $table->index(['document', 'effective_at']);
        });

        Schema::create('legal_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('legal_version_id')->constrained('legal_versions')->restrictOnDelete();
            $table->timestamp('accepted_at');

            $table->unique(['user_id', 'legal_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_acceptances');
        Schema::dropIfExists('legal_versions');
    }
};
