<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جلسه‌های یک دوره — برخلاف نسخه محصول تجارت، این جدول تغییرپذیر است:
 * مدرس محتوای جلسه را ویرایش می‌کند، تاریخچه‌اش لازم نیست.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();

            $table->string('title');
            $table->string('content_type', 16)->default('text');
            $table->text('content')->nullable();
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['course_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};
