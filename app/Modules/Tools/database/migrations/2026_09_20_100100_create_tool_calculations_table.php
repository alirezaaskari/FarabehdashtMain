<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * محاسبه‌های ذخیره‌شده — فقط افزودنی.
 *
 * `updated_at` عمداً نیست: ردیفی که ستون «آخرین ویرایش» دارد، دیر یا زود
 * ویرایش می‌شود.
 *
 * `formula_version` کنار `formula_id` ذخیره می‌شود چون معیار پذیرش بخش ۷
 * بازتولید محاسبه با نسخه لحظه ثبت است، نه با نسخه روز.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_calculations', function (Blueprint $table): void {
            $table->id();

            // شناسه عمومی: نشانی محاسبه و نسخه چاپی‌اش نباید شماره ردیف جدول
            // را لو بدهد و قابل شمارش نباشد.
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tool_slug', 64);
            $table->string('formula_id', 64);
            $table->string('formula_version', 32);
            $table->string('label')->nullable();

            $table->json('inputs');
            $table->json('outputs');
            $table->json('notes');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);

            // پنل مدیریت پیش از سنجاق‌کردن نسخه تازه می‌پرسد «چند محاسبه
            // متأثر می‌شوند»؛ این نمایه همان پرسش است.
            $table->index(['formula_id', 'formula_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_calculations');
    }
};
