<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته آمادگی آزمون (بخش ۱۸-۷): بسته، موضوع، سؤال و گزینه، خرید، و دور
 * تمرین یا آزمون با پاسخ‌هایش.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_packs', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->string('slug', 120)->unique();
            $table->string('title', 160);
            $table->string('exam_name', 160);
            $table->text('description');
            $table->unsignedBigInteger('price_toman');
            $table->unsignedSmallInteger('exam_question_count')->default(50);
            $table->unsignedSmallInteger('exam_minutes')->default(60);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('exam_pack_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_pack_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['exam_pack_id', 'title']);
        });

        Schema::create('prep_questions', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('exam_pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('exam_pack_topics');
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('difficulty', 10)->default('medium');
            $table->text('body');
            $table->text('explanation')->nullable();
            $table->string('reference_label', 160)->nullable();
            $table->string('reference_path', 255)->nullable();
            $table->boolean('is_sample')->default(false);
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['exam_pack_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('prep_choices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->constrained('prep_questions')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('exam_pack_purchases', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('exam_pack_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('price_toman');
            $table->string('payment_source', 20)->default('gateway');
            $table->string('gateway_authority')->nullable()->unique();
            $table->string('gateway_ref_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_pack_id', 'user_id']);
            $table->index(['status', 'paid_at']);
        });

        Schema::create('prep_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('exam_pack_id')->constrained();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 10);
            $table->foreignId('topic_id')->nullable()->constrained('exam_pack_topics')->nullOnDelete();
            $table->json('question_ids');
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('late')->default(false);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('prep_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('prep_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('prep_questions')->cascadeOnDelete();
            $table->foreignId('choice_id')->nullable()->constrained('prep_choices')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prep_answers');
        Schema::dropIfExists('prep_attempts');
        Schema::dropIfExists('exam_pack_purchases');
        Schema::dropIfExists('prep_choices');
        Schema::dropIfExists('prep_questions');
        Schema::dropIfExists('exam_pack_topics');
        Schema::dropIfExists('exam_packs');
    }
};
