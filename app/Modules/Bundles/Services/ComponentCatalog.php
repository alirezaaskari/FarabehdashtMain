<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Services;

use App\Contracts\BundleComponentSource;
use App\Modules\Bundles\Domain\Bundle;
use App\Support\Bundles\BundleComponent;

/**
 * همه نوع‌های جزء که ماژول‌های فعال ثبت کرده‌اند.
 *
 * جزئی که ماژولش خاموش است یا دیگر فروختنی نیست «در دسترس نیست» و بسته
 * تا وقتی چنین جزئی دارد فروخته نمی‌شود.
 */
final readonly class ComponentCatalog
{
    /** @var array<string, BundleComponentSource> */
    private array $sources;

    /** @param  iterable<BundleComponentSource>  $sources */
    public function __construct(iterable $sources)
    {
        $byKind = [];

        foreach ($sources as $source) {
            $byKind[$source->kind()] = $source;
        }

        $this->sources = $byKind;
    }

    /** @return array<string, BundleComponentSource> */
    public function sources(): array
    {
        return $this->sources;
    }

    public function source(string $kind): ?BundleComponentSource
    {
        return $this->sources[$kind] ?? null;
    }

    public function find(string $kind, string $ref): ?BundleComponent
    {
        return $this->source($kind)?->find($ref);
    }

    /**
     * اجزای بسته به ترتیب؛ جزء ناموجود null است.
     *
     * @return list<array{key: string, component: BundleComponent|null}>
     */
    public function resolve(Bundle $bundle): array
    {
        $rows = [];

        foreach ($bundle->items as $item) {
            $rows[] = ['key' => $item->key(), 'component' => $this->find($item->kind, $item->ref)];
        }

        return $rows;
    }

    /** @return list<BundleComponent>|null همه اجزا، یا null اگر یکی در دسترس نیست */
    public function available(Bundle $bundle): ?array
    {
        $components = [];

        foreach ($this->resolve($bundle) as $row) {
            if ($row['component'] === null) {
                return null;
            }

            $components[] = $row['component'];
        }

        return $components === [] ? null : $components;
    }
}
