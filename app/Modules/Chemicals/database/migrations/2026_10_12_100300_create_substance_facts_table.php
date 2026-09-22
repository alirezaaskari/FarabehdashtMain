<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نکته‌های یک ماده: مسیر مواجهه، علامت و اثر، حفاظت فردی.
 *
 * یک جدول با ستون نوع، نه سه جدول: ساختار هر سه یکی است — جمله کوتاه با
 * ترتیب — و سه جدول یعنی سه بار همان کد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substance_facts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('substance_id')->constrained('substances')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->unsignedSmallInteger('position');
            $table->string('text');
            $table->timestamps();

            $table->unique(['substance_id', 'kind', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substance_facts');
    }
};
