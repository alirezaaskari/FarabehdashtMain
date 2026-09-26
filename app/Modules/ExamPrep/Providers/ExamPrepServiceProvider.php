<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Providers;

use App\Contracts\SitemapSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\ExamPrep\Actions\AddQuestion;
use App\Modules\ExamPrep\Actions\RecordAnswer;
use App\Modules\ExamPrep\Actions\StartAttempt;
use App\Modules\ExamPrep\Admin\PendingPrepQuestions;
use App\Modules\ExamPrep\Seo\PackSitemapSource;
use App\Modules\ExamPrep\Services\NoPromises;
use App\Modules\ExamPrep\Services\PackAccess;
use App\Modules\ExamPrep\Services\QuestionCsv;
use App\Support\Modules\ModuleProvider;
use Illuminate\Database\ConnectionInterface;

/**
 * آمادگی آزمون (بخش ۱۸-۷).
 *
 * فروش از در `SalesSwitch` می‌پرسد و دفتر کل از `LedgerRecorder`؛ صف تأیید
 * و نقشه سایت با برچسب کانتینر وصل می‌شوند. نویسنده سؤال را توانایی
 * `courses.manage` (مدرس تأییدشده) مشخص می‌کند، نه import از ماژول هویت.
 */
final class ExamPrepServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'ExamPrep';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(NoPromises::class, static fn (): NoPromises => new NoPromises(
            array_values(array_map('strval', (array) config('exam_prep.forbidden_phrases', []))),
        ));

        $this->app->singleton(QuestionCsv::class, static fn (): QuestionCsv => new QuestionCsv(
            (int) config('exam_prep.csv.max_rows', 500),
        ));

        $this->app->bind(AddQuestion::class, static fn ($app): AddQuestion => new AddQuestion(
            $app->make(ConnectionInterface::class),
            (int) config('exam_prep.sample_size', 10),
        ));

        $this->app->bind(StartAttempt::class, static fn ($app): StartAttempt => new StartAttempt(
            $app->make(PackAccess::class),
            (int) config('exam_prep.sample_size', 10),
            (int) config('exam_prep.practice_batch', 20),
        ));

        $this->app->bind(RecordAnswer::class, static fn ($app): RecordAnswer => new RecordAnswer(
            $app->make(ConnectionInterface::class),
            (int) config('exam_prep.grace_seconds', 30),
        ));

        $this->app->tag([PendingPrepQuestions::class], AdminServiceProvider::APPROVAL_SOURCES);
        $this->app->tag([PackSitemapSource::class], SitemapSource::TAG);
    }
}
