<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پیوند مستقیم به متن مرجع و تاریخ مراجعه برای هر حد.
 *
 * نام و نسخه مرجع می‌گوید عدد از کجاست؛ پیوند می‌گذارد کاربر خودش با یک
 * کلیک ببیند. تاریخ مراجعه برای مرجع برخط لازم است که بی‌شماره ویرایش
 * به‌روز می‌شود: «دیده‌شده در فلان روز» یعنی عدد همان روز همین بود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exposure_limits', function (Blueprint $table): void {
            $table->string('reference_url', 500)->nullable()->after('reference_year');
            $table->date('reference_accessed_on')->nullable()->after('reference_url');
        });
    }

    public function down(): void
    {
        Schema::table('exposure_limits', function (Blueprint $table): void {
            $table->dropColumn(['reference_url', 'reference_accessed_on']);
        });
    }
};
