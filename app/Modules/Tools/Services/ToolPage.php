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

    public function __construct(
        private InternalLinker $linker,
        private ToolCatalog $catalog,
        private ResultPresenter $presenter,
    ) {}

    /** @return array{seo: SeoMeta, mentionedIn: list<LinkRef>, alternative: ?ResolvedTool, offline: array<string, mixed>} */
    public function for(ResolvedTool $tool): array
    {
        return [
            'seo' => $this->seo($tool),
            'mentionedIn' => $this->linker->mentionedIn(ToolLinks::key($tool->slug()), self::MENTIONED_IN),
            'alternative' => $this->alternative($tool),
            'offline' => $this->offline($tool),
        ];
    }

    /**
     * آنچه نسخه آفلاین صفحه (`resources/js/offline`) برای محاسبه بدون اینترنت
     * می‌خواهد: شناسه و نسخه همان رابطه‌ای که سرور اجرا می‌کند، تعریف ورودی‌ها
     * با همان بازه‌ها، و برچسب و واحد خروجی‌ها.
     *
     * @return array<string, mixed>
     */
    private function offline(ResolvedTool $tool): array
    {
        $inputs = [];

        foreach ($tool->formula->inputs() as $key => $input) {
            $inputs[$key] = [
                'label' => $input->label,
                'unit_label' => $input->unit->label(),
                'min' => $input->min,
                'max' => $input->max,
                'list' => $input->list,
                'min_items' => $input->minItems,
                'max_items' => $input->maxItems,
            ];
        }

        $outputs = [];

        foreach ($tool->formula->outputs() as $key => $unit) {
            $outputs[$key] = [
                'label' => $this->presenter->label($key),
                'unit' => $unit->dimensionless() ? null : $unit->symbol(),
            ];
        }

        return [
            'formula' => $tool->formula->id().'@'.$tool->version(),
            'slug' => $tool->slug(),
            'inputs' => $inputs,
            'outputs' => $outputs,
        ];
    }

    /**
     * ابزار هم‌خانواده، اگر تعریف شده و باز است.
     *
     * پروتوتایپ یک ابزار WBGT با سوییچ داخلی و بیرونی دارد؛ این‌جا دو ابزار
     * جداست چون ورودی‌هایشان یکی نیست، و این سوییچ همان رفت‌وبرگشت را می‌دهد.
     */
    private function alternative(ResolvedTool $tool): ?ResolvedTool
    {
        $slug = $tool->definition->alternative;

        if ($slug === null || ! $this->catalog->has($slug)) {
            return null;
        }

        $alternative = $this->catalog->resolve($slug);

        return $alternative->usable() ? $alternative : null;
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
