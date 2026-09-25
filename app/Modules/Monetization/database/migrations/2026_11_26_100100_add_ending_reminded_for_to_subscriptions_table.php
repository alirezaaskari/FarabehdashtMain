<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // پایانی که یادآورش رفته. تمدید `ends_at` را جلو می‌برد و همین
        // اختلاف یادآور دوره تازه را بدون بازنشانی دستی باز می‌کند.
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->timestamp('ending_reminded_for')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn('ending_reminded_for');
        });
    }
};
