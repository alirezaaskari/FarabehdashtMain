<?php

declare(strict_types=1);

namespace App\Modules\Linking\Actions;

use App\Contracts\LinkableContentSource;
use App\Contracts\LinkTargetSource;
use App\Modules\Linking\Domain\InternalLink;
use App\Modules\Linking\Domain\LinkBlock;
use App\Modules\Linking\Domain\LinkTargetRecord;
use App\Modules\Linking\Services\LinkPlanner;
use App\Modules\Linking\Services\PhraseMatcher;
use App\Support\Linking\LinkTarget;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

/**
 * بازسازی همه پیوندهای داخلی از روی متن فعلی (DEC-31).
 *
 * پیوندها هنگام نمایش محاسبه نمی‌شوند: تطبیق همه عبارت‌های سایت روی هر
 * بازدید پرهزینه است، و پیوند ذخیره‌شده را می‌شود گزارش گرفت. هر بار همه‌چیز
 * از نو ساخته می‌شود — داده‌ای جز فهرست مسدود مدیر در این جدول‌ها نیست که
 * از دست برود — و همه در یک تراکنش، تا صفحه هرگز نیمه‌ساخته دیده نشود.
 */
final readonly class RebuildLinks
{
    /**
     * @param  iterable<LinkTargetSource>  $targetSources
     * @param  iterable<LinkableContentSource>  $contentSources
     */
    public function __construct(
        private iterable $targetSources,
        private iterable $contentSources,
        private ConnectionInterface $db,
        private int $perThousandWords,
        private int $minPerDocument,
        private int $minPhraseLength,
    ) {}

    /** @return array{targets: int, documents: int, links: int} */
    public function handle(): array
    {
        $targets = $this->targets();
        $blocks = LinkBlock::query()->orderBy('id')->get()->all();

        $matcher = PhraseMatcher::for(
            array_map(static fn (LinkTarget $target): array => $target->phrases, $targets),
            $this->minPhraseLength,
        );
        $planner = new LinkPlanner($blocks, $this->perThousandWords, $this->minPerDocument);

        $now = Carbon::now();
        $rows = [];
        $documents = 0;

        foreach ($this->contentSources as $source) {
            foreach ($source->linkableDocuments() as $document) {
                $documents++;

                foreach ($planner->plan($document, $matcher) as $position => $match) {
                    $target = $targets[$match->targetKey];

                    $rows[] = [
                        'source_key' => $document->key,
                        'source_title' => $document->title,
                        'source_url' => $document->url,
                        'target_key' => $target->key,
                        'target_title' => $target->title,
                        'target_url' => $target->url,
                        'phrase' => $match->phrase,
                        'position' => $position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        $this->db->transaction(function () use ($targets, $rows, $now): void {
            InternalLink::query()->delete();
            LinkTargetRecord::query()->delete();

            foreach (array_chunk(array_values(array_map(static fn (LinkTarget $target): array => [
                'key' => $target->key,
                'title' => $target->title,
                'url' => $target->url,
                'created_at' => $now,
                'updated_at' => $now,
            ], $targets)), 500) as $chunk) {
                LinkTargetRecord::query()->insert($chunk);
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                InternalLink::query()->insert($chunk);
            }
        });

        return ['targets' => count($targets), 'documents' => $documents, 'links' => count($rows)];
    }

    /**
     * همه مقصدها، کلید ← مقصد. کلید تکراری: اولین منبع برنده است.
     *
     * @return array<string, LinkTarget>
     */
    private function targets(): array
    {
        $targets = [];

        foreach ($this->targetSources as $source) {
            foreach ($source->linkTargets() as $target) {
                $targets[$target->key] ??= $target;
            }
        }

        return $targets;
    }
}
