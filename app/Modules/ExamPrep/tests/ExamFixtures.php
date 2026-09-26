<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Tests;

use App\Models\User;
use App\Modules\ExamPrep\Actions\AddQuestion;
use App\Modules\ExamPrep\Actions\ChangePackStatus;
use App\Modules\ExamPrep\Actions\SavePack;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Modules\ExamPrep\Services\QuestionDraft;

trait ExamFixtures
{
    /** @param  array<string, string>  $overrides */
    private function pack(array $overrides = [], bool $publish = true, int $questions = 4, int $samples = 2): ExamPack
    {
        $admin = User::factory()->create();

        $pack = $this->app->make(SavePack::class)->handle([
            'title' => 'آزمون استخدامی بهداشت حرفه‌ای',
            'slug' => 'moh-employment',
            'exam_name' => 'آزمون استخدامی وزارت بهداشت',
            'description' => 'بانک سؤال مرورشده برای آمادگی آزمون استخدامی.',
            'price' => '190000',
            'exam_question_count' => '5',
            'exam_minutes' => '10',
            'topics' => "سم‌شناسی\nصدا",
            ...$overrides,
        ], $admin->id);

        for ($i = 1; $i <= $questions; $i++) {
            $this->question($pack, $i % 2 === 0 ? 'صدا' : 'سم‌شناسی', 'سؤال شماره '.$i.' درباره موضوع', $i <= $samples);
        }

        if ($publish) {
            $this->app->make(ChangePackStatus::class)->publish($pack, $admin->id);
        }

        return $pack->refresh();
    }

    private function question(ExamPack $pack, string $topic, string $body, bool $sample = false, ?int $authorId = null, bool $publish = true): PrepQuestion
    {
        return $this->app->make(AddQuestion::class)->handle(
            $pack,
            QuestionDraft::make($topic, 'medium', $body, ['درست', 'نادرست الف', 'نادرست ب'], 1, 'توضیح پاسخ', 'مقاله', '/encyclopedia', $sample),
            $authorId,
            $publish,
        );
    }
}
