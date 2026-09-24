<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تأیید جداگانه جلسه و سؤال.
 *
 * جلسه یا سؤالی که مدرس به دوره منتشرشده اضافه می‌کند تا تأیید مدیر به
 * دانشجو نمی‌رسد. محتوای دوره‌هایی که همین حالا منتشر یا بازنشسته‌اند قبلاً
 * دیده شده، پس تأییدشده حساب می‌شود؛ محتوای پیش‌نویس هنگام انتشار دوره تأیید
 * می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['course_sessions', 'exam_questions'] as $table) {
            Schema::table($table, static function (Blueprint $table): void {
                $table->timestamp('approved_at')->nullable()->after('position');
            });
        }

        $live = DB::table('courses')->whereIn('status', ['published', 'retired'])->select('id');

        DB::table('course_sessions')->whereIn('course_id', $live)->update(['approved_at' => now()]);

        DB::table('exam_questions')
            ->whereIn('exam_id', DB::table('exams')->whereIn('course_id', $live)->select('id'))
            ->update(['approved_at' => now()]);
    }

    public function down(): void
    {
        foreach (['course_sessions', 'exam_questions'] as $table) {
            Schema::table($table, static function (Blueprint $table): void {
                $table->dropColumn('approved_at');
            });
        }
    }
};
