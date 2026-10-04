<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * پرسش و پاسخ تحریریه فرابهداشت صاحب حساب ندارند: `user_id` خالی یعنی
     * «تحریریه». حساب ساختگی نمی‌سازیم، چون ورود با موبایل است و هر شماره
     * ساختگی ممکن است مال کسی باشد.
     */
    public function up(): void
    {
        Schema::table('expert_questions', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('expert_answers', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('expert_answers')->whereNull('user_id')->delete();
        DB::table('expert_questions')->whereNull('user_id')->delete();

        Schema::table('expert_questions', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('expert_answers', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
