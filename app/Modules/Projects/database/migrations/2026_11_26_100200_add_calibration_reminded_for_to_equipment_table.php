<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تاریخ اعتباری که یادآورش رفته. کالیبراسیون تازه تاریخ را جلو می‌برد
        // و یادآور دوره بعد بدون بازنشانی دستی باز می‌شود.
        Schema::table('equipment', function (Blueprint $table): void {
            $table->date('calibration_reminded_for')->nullable()->after('calibration_valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table): void {
            $table->dropColumn('calibration_reminded_for');
        });
    }
};
