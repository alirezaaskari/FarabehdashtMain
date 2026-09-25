<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * بررسی جداگانه هر نسخه فایل.
 *
 * نسخه‌های محصول‌هایی که همین حالا منتشر یا بازنشسته‌اند قبلاً به خریدار
 * رسیده‌اند، پس تأییدشده حساب می‌شوند؛ بقیه هنگام انتشار محصول تأیید می‌شوند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_versions', static function (Blueprint $table): void {
            $table->string('review_status', 16)->default('pending')->after('checksum');
            $table->timestamp('reviewed_at')->nullable()->after('review_status');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });

        DB::table('product_versions')
            ->whereIn('product_id', DB::table('products')->whereIn('status', ['published', 'retired'])->select('id'))
            ->update(['review_status' => 'approved', 'reviewed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('product_versions', static function (Blueprint $table): void {
            $table->dropColumn(['review_status', 'reviewed_at', 'review_note']);
        });
    }
};
