<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Http\Controllers;

use App\Modules\Chemicals\Domain\Enums\FactKind;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Services\RelatedTools;
use App\Modules\Core\Seo\Schema;
use App\Modules\Core\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صفحه پایدار یک ماده — قابل استناد در گزارش کارشناسی.
 *
 * فقط منتشرشده نشانی عمومی دارد؛ پیش‌نویس ۴۰۴ می‌گیرد نه ۴۰۳، تا کسی از
 * پاسخ متفاوت نفهمد چنین شناسه‌ای اصلاً وجود دارد یا نه.
 */
final readonly class ChemicalController
{
    public function __construct(private RelatedTools $relatedTools) {}

    public function show(string $slug): View
    {
        $substance = $this->find($slug);

        return view('chemicals::show', [
            'substance' => $substance,
            'limits' => $substance->orderedLimits(),
            'routes' => $substance->factsOf(FactKind::Route),
            'symptoms' => $substance->factsOf(FactKind::Symptom),
            'protections' => $substance->factsOf(FactKind::Protection),
            'tools' => $this->relatedTools->all(),
            'seo' => $this->seo($substance),
        ]);
    }

    private function find(string $slug): Substance
    {
        $substance = Substance::query()
            ->published()
            ->where('slug', $slug)
            ->with(['synonyms', 'limits', 'facts'])
            ->first();

        return $substance ?? throw new NotFoundHttpException('این ماده پیدا نشد.');
    }

    private function seo(Substance $substance): SeoMeta
    {
        $url = route('chemicals.show', $substance->slug);

        $description = sprintf(
            '%s (%s) — شماره CAS %s. حدود مواجهه شغلی، مسیرهای مواجهه و حفاظت فردی.',
            $substance->name_fa,
            $substance->name_en,
            $substance->cas_number,
        );

        $meta = new SeoMeta(title: $substance->name_fa.' — '.$substance->name_en, description: $description, canonical: $url);

        // داده ساختاریافته فقط توضیح می‌دهد، ادعای اعتبار رسمی نمی‌کند —
        // مثل بقیه Schema‌های این سایت.
        return $meta->withSchema(Schema::breadcrumbs([
            ['name' => 'بانک مواد شیمیایی', 'url' => route('chemicals.index')],
            ['name' => $substance->name_fa, 'url' => $url],
        ]));
    }
}
