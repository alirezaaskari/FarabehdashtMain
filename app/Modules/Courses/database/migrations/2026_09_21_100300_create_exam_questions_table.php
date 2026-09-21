<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();

            $table->text('text');
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['exam_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
