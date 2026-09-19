<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دسته‌بندی مشترک همه محتواها.
 *
 * `health_domain` (حوزه بهداشت حرفه‌ای: صدا، ارگونومی، مواد شیمیایی، …) روی
 * مقاله، ابزار، دوره، محصول و آگهی یکسان است. یک جدول مشترک یعنی وقتی کاربر
 * روی «صدا» کلیک می‌کند، همه‌چیزِ صدا را می‌بیند، نه فقط مقاله‌ها.
 *
 * برچسب‌ها درختی‌اند (parent_id) تا زیرشاخه ممکن باشد، ولی عمق در نسخه اول
 * دو سطح نگه داشته می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomy_terms', function (Blueprint $table): void {
            $table->id();

            $table->string('taxonomy', 40);
            $table->string('slug', 120);
            $table->string('name', 120);
            $table->string('description', 500)->nullable();

            $table->foreignId('parent_id')->nullable()->constrained('taxonomy_terms')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['taxonomy', 'slug']);
            $table->index(['taxonomy', 'position']);
        });

        Schema::create('taxonomables', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('term_id')->constrained('taxonomy_terms')->cascadeOnDelete();
            $table->morphs('taxonomable');

            $table->unique(['term_id', 'taxonomable_type', 'taxonomable_id'], 'taxonomables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomables');
        Schema::dropIfExists('taxonomy_terms');
    }
};
