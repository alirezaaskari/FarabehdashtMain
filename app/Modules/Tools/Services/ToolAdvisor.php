<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Contracts\SearchSource;
use App\Modules\Tools\Domain\Advisor\Answers;
use App\Modules\Tools\Domain\Advisor\Situation;
use App\Modules\Tools\Domain\Advisor\Suggestion;
use App\Modules\Tools\Domain\Enums\Hazard;
use App\Modules\Tools\Domain\Enums\WorkStage;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchQuery;
use Illuminate\Support\Facades\Route;

/**
 * دستیار انتخاب ابزار: سه سؤال، و پیشنهاد ابزار، مقاله، فایل و دوره.
 *
 * نه هوش مصنوعی و نه درخت تصمیم پیچیده. سؤال اول خطر است، دوم مرحله کار و
 * سوم موقعیت دقیق میدانی (از پیکربندی `tools.advisor`). ابزار از همین ماژول
 * می‌آید؛ مقاله، فایل و دوره از جست‌وجوی ماژول‌های دیگر با قرارداد
 * {@see SearchSource}، پس این‌جا مدل هیچ ماژول دیگری شناخته نمی‌شود (قاعده ۱)
 * و ماژول خاموش فقط ردیف‌هایش را از پنل حذف می‌کند.
 *
 * موقعیتی که ابزارش غیرفعال یا تعریف‌نشده باشد، نمایش داده نمی‌شود؛ پیشنهاد
 * ابزاری که باز نمی‌شود بدتر از نبودِ پیشنهاد است.
 */
final readonly class ToolAdvisor
{
    /** سهم هر نوع محتوا در پنل؛ پنل راهنماست، نه صفحه جست‌وجو. */
    private const PER_KIND = 2;

    /** بیشترین ابزاری که پیش از انتخاب موقعیت پیشنهاد می‌شود. */
    private const TOOLS_BEFORE_SITUATION = 3;

    /**
     * گروه‌های جست‌وجو که دستیار از آن‌ها محتوا برمی‌دارد، با برچسب نوعشان.
     * ابزارها از خود ماژول می‌آیند و نام مواد با واژه خطر پیدا نمی‌شود.
     */
    private const KINDS = [
        'encyclopedia' => 'مقاله',
        'commerce' => 'فایل',
        'courses' => 'دوره',
    ];

    /**
     * @param  list<array<string, string>>  $situations
     * @param  iterable<SearchSource>  $sources
     */
    public function __construct(
        private ToolCatalog $catalog,
        private array $situations,
        private iterable $sources,
    ) {}

    /**
     * موقعیت‌های میدانی، به ترتیب پیکربندی؛ با خطر، فقط موقعیت‌های همان خطر.
     *
     * @return list<Situation>
     */
    public function situations(?Hazard $hazard = null): array
    {
        $situations = [];

        foreach ($this->situations as $entry) {
            $slug = $entry['tool'] ?? '';

            if (! $this->catalog->has($slug)) {
                continue;
            }

            $tool = $this->catalog->resolve($slug);

            if (! $tool->usable()) {
                continue;
            }

            if ($hazard !== null && $tool->definition->category !== $hazard->category()) {
                continue;
            }

            $situations[] = new Situation($entry['situation'] ?? '', $entry['reason'] ?? '', $tool);
        }

        return $situations;
    }

    /** موقعیت انتخاب‌شده، اگر هنوز به خطر انتخاب‌شده تعلق دارد و ابزارش باز است. */
    public function situation(Answers $answers): ?Situation
    {
        if ($answers->hazard === null || $answers->situation === null) {
            return null;
        }

        foreach ($this->situations($answers->hazard) as $situation) {
            if ($situation->key() === $answers->situation) {
                return $situation;
            }
        }

        return null;
    }

    /**
     * سؤال جاری: ۱ تا ۳، یا null وقتی همه پاسخ داده شده.
     *
     * خطری که هنوز ابزار ندارد سؤال سوم ندارد؛ دستیار بعد از سؤال دوم تمام است.
     */
    public function step(Answers $answers): ?int
    {
        return match (true) {
            $answers->hazard === null => 1,
            $answers->stage === null => 2,
            $this->situation($answers) === null && $this->situations($answers->hazard) !== [] => 3,
            default => null,
        };
    }

    /** شمار سؤال‌ها برای همین خطر. */
    public function steps(Answers $answers): int
    {
        return $answers->hazard !== null && $this->situations($answers->hazard) === [] ? 2 : 3;
    }

    /**
     * ردیف‌های پنل «نتیجه پیشنهادی»، به ترتیبی که مرحله کار می‌خواهد.
     *
     * @return list<Suggestion>
     */
    public function suggestions(Answers $answers): array
    {
        if ($answers->hazard === null) {
            return [];
        }

        $groups = ['tools' => $this->toolSuggestions($answers), ...$this->contentSuggestions($answers->hazard)];
        $ordered = [];

        foreach (($answers->stage ?? WorkStage::Measure)->priority() as $key) {
            array_push($ordered, ...($groups[$key] ?? []));
        }

        return [...$ordered, ...$this->actions($answers)];
    }

    /** @return list<Suggestion> */
    private function toolSuggestions(Answers $answers): array
    {
        if (! Route::has('tools.show')) {
            return [];
        }

        $chosen = $this->situation($answers);
        $situations = $chosen !== null
            ? [$chosen]
            : array_slice($this->situations($answers->hazard), 0, self::TOOLS_BEFORE_SITUATION);

        return array_map(
            static fn (Situation $situation): Suggestion => new Suggestion(
                kind: 'ابزار',
                title: $situation->tool->definition->title,
                url: route('tools.show', $situation->tool->slug()),
                note: $chosen !== null ? $situation->reason : '',
            ),
            $situations,
        );
    }

    /**
     * محتوای ماژول‌های دیگر که با واژه‌های خطر پیدا می‌شود.
     *
     * @return array<string, list<Suggestion>>
     */
    private function contentSuggestions(Hazard $hazard): array
    {
        $found = [];

        foreach ($this->sources as $source) {
            foreach ($hazard->keywords() as $keyword) {
                $group = $source->search(SearchQuery::from($keyword), self::PER_KIND);

                if (! $group instanceof SearchGroup || ! isset(self::KINDS[$group->key])) {
                    continue;
                }

                foreach ($group->hits as $hit) {
                    $found[$group->key][$hit->url] ??= new Suggestion(self::KINDS[$group->key], $hit->title, $hit->url);
                }
            }
        }

        return array_map(
            static fn (array $suggestions): array => array_slice(array_values($suggestions), 0, self::PER_KIND),
            $found,
        );
    }

    /**
     * گام بعدی که به مرحله کار بستگی دارد، نه به جست‌وجو.
     *
     * @return list<Suggestion>
     */
    private function actions(Answers $answers): array
    {
        $actions = [];

        if ($answers->hazard === Hazard::Chemical && Route::has('chemicals.index')) {
            $actions[] = new Suggestion('بانک مواد', 'حد مجاز و مشخصات ماده را پیدا کنید', route('chemicals.index'));
        }

        if ($answers->stage === WorkStage::Report && Route::has('reports.create')) {
            $actions[] = new Suggestion('گزارش', 'گزارش‌ساز میزکار', route('reports.create'));
        }

        return $actions;
    }
}
