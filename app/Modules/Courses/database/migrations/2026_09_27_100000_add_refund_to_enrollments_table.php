<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بازگشت وجه ثبت‌نام دوره (بخش ۱۸-۱۱): چه کسی، کِی و چرا.
 *
 * مبلغ ستون ندارد: بازگشت همیشه کامل است و برابر `price_toman` خود ثبت‌نام.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', static function (Blueprint $table): void {
            $table->timestamp('refunded_at')->nullable()->after('completed_at');
            $table->foreignId('refunded_by')->nullable()->after('refunded_at')->constrained('users')->nullOnDelete();
            $table->string('refund_reason', 500)->nullable()->after('refunded_by');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropColumn(['refunded_at', 'refund_reason']);
        });
    }
};
