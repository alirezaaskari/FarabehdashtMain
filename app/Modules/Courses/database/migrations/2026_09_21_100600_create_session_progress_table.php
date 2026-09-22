<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('course_session_id')->constrained('course_sessions')->cascadeOnDelete();

            $table->timestamp('completed_at');

            $table->unique(['enrollment_id', 'course_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_progress');
    }
};
