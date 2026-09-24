<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Modules\Workspace\Domain\Enums\LegalChange;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use App\Modules\Workspace\Domain\LegalVersion;
use App\Modules\Workspace\Events\LegalVersionPublished;
use App\Modules\Workspace\Services\LegalLibrary;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * انتشار نسخه تازه یک صفحه حقوقی.
 *
 * شماره نسخه را سیستم می‌دهد، نه مدیر. نخستین نسخه سندی که پذیرش لازم دارد
 * همیشه اساسی است: پیش از آن کاربری چیزی نپذیرفته که «اصلاح جزئی» معنا داشته
 * باشد.
 */
final readonly class PublishLegalVersion
{
    public function __construct(
        private LegalLibrary $library,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    /** @throws InvalidArgumentException */
    public function handle(
        LegalDocument $document,
        string $body,
        LegalChange $change,
        ?string $summary,
        ?Carbon $effectiveAt,
        ?int $actorId,
    ): LegalVersion {
        if (trim($body) === '') {
            throw new InvalidArgumentException('متن سند خالی است.');
        }

        if ($change === LegalChange::Material && ($summary === null || trim($summary) === '')) {
            throw new InvalidArgumentException('برای تغییر اساسی، خلاصه تغییرات اجباری است؛ کاربر پیش از پذیرش همین را می‌خواند.');
        }

        $version = $this->db->transaction(function () use ($document, $body, $change, $summary, $effectiveAt, $actorId): LegalVersion {
            $number = $this->library->nextVersionNumber($document);

            if ($number === 1 && $document->requiresAcceptance()) {
                $change = LegalChange::Material;
            }

            return LegalVersion::query()->create([
                'uuid' => (string) Str::uuid7(),
                'document' => $document,
                'version' => $number,
                'body' => trim($body),
                'summary' => $summary !== null && trim($summary) !== '' ? trim($summary) : null,
                'change' => $change,
                'effective_at' => $effectiveAt ?? Carbon::now(),
                'published_by' => $actorId,
            ]);
        });

        $this->events->dispatch(new LegalVersionPublished($version, $actorId));

        return $version;
    }
}
