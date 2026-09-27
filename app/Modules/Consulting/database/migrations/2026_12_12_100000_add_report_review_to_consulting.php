<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بررسی گزارش توسط متخصص (بخش ۱۹-۴) نوعی خدمت مشاوره است با همان امانت،
 * مهلت پاسخ و اعتراض؛ فقط به‌جای زمان جلسه، گزارش و یادداشت بررسی دارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulting_services', function (Blueprint $table): void {
            // بررسی گزارش مدت جلسه ندارد.
            $table->unsignedSmallInteger('duration_minutes')->nullable()->change();
        });

        Schema::table('consulting_orders', function (Blueprint $table): void {
            // شناسه گزارش صادرشده؛ متن از Snapshot خود گزارش خوانده می‌شود، نه این‌جا.
            $table->uuid('report_uuid')->nullable()->after('service_id')->index();
            $table->timestamp('due_at')->nullable()->after('accepted_at');
            // {notes: {کلید بخش: یادداشت}, summary: جمع‌بندی}
            $table->json('review')->nullable()->after('meeting_link');
            $table->text('follow_up_question')->nullable()->after('review');
            $table->text('follow_up_answer')->nullable()->after('follow_up_question');
            $table->timestamp('follow_up_asked_at')->nullable()->after('follow_up_answer');
        });
    }

    public function down(): void
    {
        Schema::table('consulting_orders', function (Blueprint $table): void {
            $table->dropIndex(['report_uuid']);
            $table->dropColumn(['report_uuid', 'due_at', 'review', 'follow_up_question', 'follow_up_answer', 'follow_up_asked_at']);
        });
    }
};
