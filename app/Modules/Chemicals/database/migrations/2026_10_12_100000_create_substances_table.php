<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ماده شیمیایی.
 *
 * `cas_number` یکتاست و همین یکتایی است که ورود CSV را ممکن می‌کند: ردیف
 * تازه با CAS موجود یعنی «به‌روزرسانی»، نه «ماده دوم». نام فارسی یکتا نیست،
 * چون یک ماده چند نام رایج دارد و مترادف‌ها جدول خودشان را دارند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substances', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->string('slug')->unique();
            $table->string('cas_number', 32)->unique();

            $table->string('name_fa');
            $table->string('name_en');
            $table->string('formula', 64)->nullable();

            // جرم مولکولی g/mol — ابزارهای تبدیل واحد از همین می‌خوانند.
            $table->decimal('molar_mass', 10, 4)->nullable();

            $table->string('physical_state', 64)->nullable();
            $table->text('description')->nullable();

            // روش نمونه‌برداری و تحلیل
            $table->string('sampling_media')->nullable();
            $table->string('sampling_flow')->nullable();
            $table->string('analysis_method')->nullable();
            $table->string('method_number', 64)->nullable();

            $table->string('status', 32)->default('draft');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'name_fa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substances');
    }
};
