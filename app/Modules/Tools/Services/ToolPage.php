<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Contracts\InternalLinker;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Linking\ToolLinks;
use App\Support\Linking\LinkRef;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;

/**
 * داده مشترک صفحه ابزار: سئو و «مقاله‌هایی که به این اشاره دارند».
 *
 * صفحه ابزار از سه جا رندر می‌شود (نمایش، نتیجه محاسبه، خطای ذخیره) و هر سه
 * باید یک عنوان و یک Canonical بدهند: نتیجه محاسبه نشانی تازه نیست.
 */
final readonly class ToolPage
{
    private const MENTIONED_IN = 6;

    public function __construct(private InternalLinker $linker) {}

    /** @return array{seo: SeoMeta, mentionedIn: list<LinkRef>} */
    public function for(ResolvedTool $tool): array
    {
        return [
            'seo' => $this->seo($tool),
            'mentionedIn' => $this->linker->mentionedIn(ToolLinks::key($tool->slug()), self::MENTIONED_IN),
        ];
    }

    private function seo(ResolvedTool $tool): SeoMeta
    {
        $definition = $tool->definition;
        $url = route('tools.show', $definition->slug);

        $meta = new SeoMeta(title: $definition->title, description: $definition->summary, canonical: $url);

        return $meta->withSchema(Schema::graph(
            Schema::webApplication($definition->title, $url, $definition->summary),
            Schema::breadcrumbs([
                ['name' => 'ابزارها', 'url' => route('tools.index')],
                ['name' => $definition->title, 'url' => $url],
            ]),
        ));
    }
}
