<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Events\QuestionsImported;
use App\Modules\ExamPrep\Services\QuestionCsv;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * ورود دسته‌ای سؤال از CSV به دست مدیر. همه یا هیچ: اگر حتی یک ردیف
 * ایراد داشته باشد، هیچ سؤالی نوشته نمی‌شود و فهرست ایرادها برمی‌گردد.
 */
final readonly class ImportQuestions
{
    public function __construct(
        private QuestionCsv $csv,
        private AddQuestion $add,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    /**
     * @return array{imported: int, errors: list<array{line: int, error: string}>}
     */
    public function handle(ExamPack $pack, string $contents, int $actorId, bool $dryRun = false): array
    {
        $rows = $this->csv->read($contents);
        $errors = [];

        foreach ($rows as $row) {
            if ($row['error'] !== null) {
                $errors[] = ['line' => $row['line'], 'error' => $row['error']];
            }
        }

        $imported = 0;

        try {
            $this->db->transaction(function () use ($rows, $pack, $actorId, &$errors, &$imported, $dryRun): void {
                foreach ($rows as $row) {
                    if ($row['draft'] === null) {
                        continue;
                    }

                    try {
                        $this->add->handle($pack, $row['draft'], $actorId, publish: true);
                        $imported++;
                    } catch (InvalidArgumentException $exception) {
                        $errors[] = ['line' => $row['line'], 'error' => $exception->getMessage()];
                    }
                }

                if ($errors !== [] || $dryRun) {
                    throw new RollbackImport;
                }
            });
        } catch (RollbackImport) {
            usort($errors, static fn (array $a, array $b): int => $a['line'] <=> $b['line']);

            return ['imported' => $errors === [] ? $imported : 0, 'errors' => $errors];
        }

        $this->events->dispatch(new QuestionsImported($pack, $imported, $actorId));

        return ['imported' => $imported, 'errors' => []];
    }
}
