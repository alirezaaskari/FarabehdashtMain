<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پروژه‌های بازار (بخش ۲۱-۲) و پیوست‌های خصوصی آن‌ها.
 *
 * `service` کلید فهرست ثابت خدمت‌های دایرکتوری است (DEC-87) و شهر کلید
 * config/regions.php؛ `city` تهی با `remote` یعنی «از راه دور».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_projects', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('client_user_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 160);
            $table->string('service', 32)->index();
            $table->string('province', 32)->nullable();
            $table->string('city', 32)->nullable()->index();
            $table->boolean('remote')->default(false);
            $table->unsignedBigInteger('budget_min_toman');
            $table->unsignedBigInteger('budget_max_toman');
            $table->date('wanted_by')->nullable();
            $table->text('description');
            $table->string('client_name', 100)->nullable();
            $table->boolean('show_client_name')->default(false);
            $table->boolean('is_private')->default(false);
            $table->string('status', 16)->index();
            $table->string('review_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('bids_close_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('market_project_files', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('project_id')->constrained('market_projects')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 190);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_project_files');
        Schema::dropIfExists('market_projects');
    }
};
