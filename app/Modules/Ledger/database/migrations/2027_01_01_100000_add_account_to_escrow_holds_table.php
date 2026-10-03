<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حساب امانت هر ردیف (بخش ۲۱-۱): خدمت یا پروژه. ردیف‌های قبلی همه خدمت‌اند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escrow_holds', function (Blueprint $table): void {
            $table->string('account', 32)->default('service_escrow')->after('refunded_toman')->index();
        });
    }

    public function down(): void
    {
        Schema::table('escrow_holds', function (Blueprint $table): void {
            $table->dropIndex(['account']);
            $table->dropColumn('account');
        });
    }
};
