<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** یک دوره حداکثر یک آزمون دارد — کافی برای معیار «آزمون» این بخش. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained('courses')->cascadeOnDelete();

            $table->unsignedTinyInteger('pass_percentage')->default(60);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
