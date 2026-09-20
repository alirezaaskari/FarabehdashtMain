<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * وضعیت قابل تغییر ابزارها.
 *
 * فقط چیزهایی که مدیر بدون استقرار تازه عوضشان می‌کند. تعریف ابزار (عنوان،
 * گروه، رابطه) در کد می‌ماند تا از مسیر بازبینی کد بیرون نیفتد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table): void {
            $table->string('slug', 64)->primary();
            $table->boolean('is_enabled')->default(true);

            // null یعنی «آخرین نسخه». سنجاق‌کردن یک تصمیم آگاهانه است، نه
            // پیش‌فرض؛ وگرنه ابزارها روی نسخه‌های قدیمی جا می‌مانند.
            $table->string('pinned_version', 32)->nullable();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
