<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * منبع پرداخت ثبت‌نام دوره: درگاه، کیف پول (DEC-37) یا رایگان (DEC-38).
 *
 * ردیف‌های پیشین همه از درگاه پرداخت شده‌اند، پس پیش‌فرض «درگاه» است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', static function (Blueprint $table): void {
            $table->string('payment_source', 16)->default('gateway')->after('gateway_ref_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', static function (Blueprint $table): void {
            $table->dropColumn('payment_source');
        });
    }
};
