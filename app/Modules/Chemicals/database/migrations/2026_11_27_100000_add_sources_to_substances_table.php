<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * منابع غیر از حد مواجهه: مشخصات، مسیر، علائم، حفاظت و روش نمونه‌برداری.
 *
 * هر حد منبع خودش را دارد، ولی بقیه صفحه هم از جایی آمده و کارشناسی که
 * به آن استناد می‌کند باید بداند از کجا. هر خط یک منبع است؛ نشانی داخل خط
 * روی صفحه پیوند می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('substances', function (Blueprint $table): void {
            $table->text('sources')->nullable()->after('method_number');
        });
    }

    public function down(): void
    {
        Schema::table('substances', function (Blueprint $table): void {
            $table->dropColumn('sources');
        });
    }
};
