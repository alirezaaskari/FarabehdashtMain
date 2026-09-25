<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * محاسبه‌ای که کاربر حذف کرده ولی جایی ارجاع دارد، بایگانی می‌شود: از
 * فهرست و سقف پلن رایگان بیرون می‌رود و ردیفش برای پروژه و گزارش می‌ماند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_calculations', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('notes');
            $table->index(['user_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tool_calculations', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
