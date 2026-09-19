<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نسخه‌های پیشین هر محتوا.
 *
 * دفتر رویداد می‌گوید «چه کسی چه کاری کرد»؛ این جدول می‌گوید «متن قبلی چه بود».
 * دو کار جدا با دو عمر جدا: ردیف رویداد برای همیشه می‌ماند، نسخه محتوا ممکن
 * است بعداً هرس شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_revisions', function (Blueprint $table): void {
            $table->id();

            $table->morphs('revisable');
            $table->unsignedInteger('version');

            $table->json('snapshot');
            $table->string('reason', 255)->nullable();

            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['revisable_type', 'revisable_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
    }
};
