<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DEC-64: نقش کارجو بی‌صف فعال می‌شود. درخواست‌هایی که پیش از این قاعده در
 * صف مانده‌اند همین‌جا فعال می‌شوند تا کسی بی‌دلیل منتظر نماند.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_profiles')
            ->where('type', 'jobseeker')
            ->where('status', 'pending')
            ->update(['status' => 'active', 'approved_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void {}
};
