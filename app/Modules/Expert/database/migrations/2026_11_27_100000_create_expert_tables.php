<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // پرسش از متخصص (بخش ۱۸-۳). نام پرسش‌کننده هیچ‌جا نمایش داده نمی‌شود
        // (DEC-41)؛ `user_id` فقط برای «پرسش‌های من» و اعلان است.
        Schema::create('expert_questions', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('topic', 32);
            $table->string('visibility', 16);
            $table->string('title');
            $table->text('body');

            $table->string('status', 16);
            // اولویت مشترک Pro (DEC-40) هنگام پرسیدن ثبت می‌شود تا پایان
            // اشتراک، جای پرسش را در صف عوض نکند.
            $table->boolean('priority')->default(false);
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->unsignedBigInteger('accepted_answer_id')->nullable();
            $table->timestamp('answered_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'visibility', 'published_at']);
            $table->index(['status', 'priority', 'created_at']);
        });

        Schema::create('expert_answers', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('question_id')->constrained('expert_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('body');
            $table->string('status', 16);
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // هر مشاور یک پاسخ به هر پرسش؛ پاسخ ردشده با یادداشت مدیر اصلاح و دوباره فرستاده می‌شود.
            $table->unique(['question_id', 'user_id']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('expert_questions', function (Blueprint $table): void {
            $table->foreign('accepted_answer_id')->references('id')->on('expert_answers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expert_questions', function (Blueprint $table): void {
            $table->dropForeign(['accepted_answer_id']);
        });

        Schema::dropIfExists('expert_answers');
        Schema::dropIfExists('expert_questions');
    }
};
