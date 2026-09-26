<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Contracts\RevisionHistory;
use App\Modules\Core\Domain\ContentRevision;
use App\Support\Revisions\RevisionRecord;

final readonly class RevisionReader implements RevisionHistory
{
    public function of(string $type, int|string $id): array
    {
        return ContentRevision::query()
            ->where('revisable_type', $type)
            ->where('revisable_id', $id)
            ->orderByDesc('version')
            ->get()
            ->map(static fn (ContentRevision $revision): RevisionRecord => new RevisionRecord(
                version: $revision->version,
                snapshot: $revision->snapshot,
                reason: $revision->reason,
                recordedAt: $revision->created_at,
            ))
            ->values()
            ->all();
    }
}
