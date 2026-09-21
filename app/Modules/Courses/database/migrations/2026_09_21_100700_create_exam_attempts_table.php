<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** هر تلاش برای آزمون یک ردیف فقط‌افزودنی است — دانشجو می‌تواند دوباره تلاش کند. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();

            $table->unsignedTinyInteger('score_percentage');
            $table->boolean('passed');

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
