<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Contracts\Revisable;
use App\Modules\Core\Domain\ContentRevision;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * نگه‌داشتن نسخه‌های پیشین محتوا.
 *
 * شماره نسخه داخل همان تراکنشی گرفته می‌شود که ردیف را می‌نویسد و ایندکس
 * یکتای (نوع، شناسه، نسخه) پشتش است؛ دو ویرایش هم‌زمان نمی‌توانند یک شماره
 * بگیرند.
 */
final readonly class RevisionRecorder
{
    public function __construct(
        private DatabaseManager $db,
        private AuthFactory $auth,
    ) {}

    /**
     * @throws InvalidArgumentException اگر مدل Eloquent نباشد
     */
    public function record(Revisable $model, ?string $reason = null, ?int $authorId = null): ContentRevision
    {
        if (! $model instanceof Model) {
            throw new InvalidArgumentException('نسخه فقط از مدل Eloquent گرفته می‌شود.');
        }

        return $this->db->transaction(fn (): ContentRevision => ContentRevision::query()->create([
            'revisable_type' => $model::class,
            'revisable_id' => $model->getKey(),
            'version' => $this->nextVersion($model),
            'snapshot' => $model->revisionSnapshot(),
            'reason' => $reason,
            'author_id' => $authorId ?? $this->currentAuthorId(),
        ]));
    }

    /** @return Collection<int, ContentRevision> */
    public function historyFor(Model $model): Collection
    {
        return $this->query($model)->orderByDesc('version')->get();
    }

    public function latestFor(Model $model): ?ContentRevision
    {
        return $this->query($model)->orderByDesc('version')->first();
    }

    private function nextVersion(Model $model): int
    {
        return (int) $this->query($model)->lockForUpdate()->max('version') + 1;
    }

    /** @return Builder<ContentRevision> */
    private function query(Model $model): Builder
    {
        return ContentRevision::query()
            ->where('revisable_type', $model::class)
            ->where('revisable_id', $model->getKey());
    }

    private function currentAuthorId(): ?int
    {
        $id = $this->auth->guard()->id();

        return is_numeric($id) ? (int) $id : null;
    }
}
