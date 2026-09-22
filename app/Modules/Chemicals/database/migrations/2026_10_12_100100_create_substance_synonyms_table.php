<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مترادف‌های یک ماده.
 *
 * جدول جداست چون جست‌وجو روی آن انجام می‌شود: کاربری که «متیل‌بنزن» را
 * می‌جوید باید تولوئن را پیدا کند. ستونی با مقادیر جداشده با کاما این را
 * ناممکن می‌کرد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substance_synonyms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('substance_id')->constrained('substances')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['substance_id', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substance_synonyms');
    }
};
