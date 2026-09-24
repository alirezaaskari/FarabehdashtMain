<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Contracts\SearchSource;
use App\Modules\Tools\Actions\UpdateToolSettings;
use App\Modules\Tools\Domain\Advisor\Answers;
use App\Modules\Tools\Domain\Advisor\Situation;
use App\Modules\Tools\Domain\Advisor\Suggestion;
use App\Modules\Tools\Domain\Enums\Hazard;
use App\Modules\Tools\Domain\Enums\WorkStage;
use App\Modules\Tools\Services\ToolAdvisor;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دستیار انتخاب ابزار — سه سؤال و پنل نتیجه پیشنهادی.
 */
final class ToolAdvisorTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_situation_points_at_a_tool_that_exists(): void
    {
        // موقعیتی که به ابزار ناموجود اشاره کند، بی‌صدا از فهرست می‌افتد و
        // کسی نمی‌فهمد. این تست همان را می‌گیرد.
        /** @var list<array<string, string>> $situations */
        $situations = (array) config('tools.advisor', []);
        $catalog = $this->app->make(ToolCatalog::class);

        $this->assertNotEmpty($situations);

        foreach ($situations as $entry) {
            $this->assertTrue(
                $catalog->has($entry['tool']),
                sprintf('موقعیت «%s» به ابزار ناموجود «%s» اشاره می‌کند.', $entry['situation'], $entry['tool']),
            );
            $this->assertNotSame('', trim($entry['reason']));
        }
    }

    public function test_every_hazard_with_tools_has_at_least_one_situation(): void
    {
        // خطری که ابزار دارد ولی موقعیتی نه، سؤال سوم را خالی رد می‌کند و
        // کاربر هرگز به ابزارش نمی‌رسد.
        $advisor = $this->app->make(ToolAdvisor::class);

        foreach (Hazard::cases() as $hazard) {
            if ($hazard->category() !== null) {
                $this->assertNotEmpty($advisor->situations($hazard), $hazard->label());
            }
        }
    }

    public function test_a_disabled_tool_is_never_suggested(): void
    {
        // پیشنهاد ابزاری که باز نمی‌شود، بدتر از نبودِ پیشنهاد است.
        $this->app->make(UpdateToolSettings::class)->setAvailability('noise-dose', false, null);

        $slugs = array_map(
            static fn (Situation $s): string => $s->key(),
            $this->app->make(ToolAdvisor::class)->situations(Hazard::Noise),
        );

        $this->assertNotContains('noise-dose', $slugs);

        $this->get(route('tools.advisor', ['hazard' => 'noise', 'stage' => 'measure', 'situation' => 'noise-dose']))
            ->assertOk()
            ->assertSee('سؤال ۳ از ۳');
    }

    public function test_the_questions_follow_each_other(): void
    {
        $this->get(route('tools.advisor'))
            ->assertOk()
            ->assertSee('سؤال ۱ از ۳')
            ->assertSee('چه چیزی می‌خواهید اندازه بگیرید؟')
            ->assertSee(Hazard::ExposureStatistics->label());

        $this->get(route('tools.advisor', ['hazard' => 'noise']))
            ->assertOk()
            ->assertSee('سؤال ۲ از ۳')
            ->assertSee(WorkStage::Control->label())
            ->assertSee('name="hazard" value="noise"', escape: false);

        $this->get(route('tools.advisor', ['hazard' => 'noise', 'stage' => 'measure']))
            ->assertOk()
            ->assertSee('سؤال ۳ از ۳')
            ->assertSee('چند دستگاه هم‌زمان کار می‌کنند')
            ->assertDontSee('روشنایی سالن را در چند نقطه');

        $this->get(route('tools.advisor', ['hazard' => 'noise', 'stage' => 'measure', 'situation' => 'noise-dose']))
            ->assertOk()
            ->assertSee('پیشنهاد آماده است')
            ->assertSee(route('tools.show', 'noise-dose'), escape: false);
    }

    public function test_a_hazard_without_tools_ends_after_the_second_question(): void
    {
        $this->get(route('tools.advisor', ['hazard' => 'vibration']))
            ->assertOk()
            ->assertSee('سؤال ۲ از ۲');

        $this->get(route('tools.advisor', ['hazard' => 'vibration', 'stage' => 'control']))
            ->assertOk()
            ->assertSee('پیشنهاد آماده است')
            ->assertSee('هنوز منتشر نشده است');
    }

    public function test_nonsense_answers_start_from_the_first_question(): void
    {
        $this->get(route('tools.advisor', ['hazard' => 'lava', 'stage' => 'measure']))
            ->assertOk()
            ->assertSee('سؤال ۱ از ۳');

        $answers = Answers::from(['stage' => 'measure', 'situation' => 'noise-dose']);

        $this->assertNull($answers->stage);
        $this->assertNull($answers->situation);
    }

    public function test_articles_files_and_courses_come_from_other_modules_through_search(): void
    {
        $this->fakeSources();

        $suggestions = $this->app->make(ToolAdvisor::class)->suggestions(new Answers(Hazard::Noise, WorkStage::Measure));
        $kinds = array_map(static fn (Suggestion $s): string => $s->kind, $suggestions);

        $this->assertSame('ابزار', $kinds[0]);
        $this->assertContains('مقاله', $kinds);
        $this->assertContains('فایل', $kinds);

        // مقاله‌ای که با دو واژه خطر پیدا شود یک بار می‌آید.
        $titles = array_map(static fn (Suggestion $s): string => $s->title, $suggestions);
        $this->assertCount(1, array_keys($titles, 'اندازه‌گیری صدا در محیط کار', true));
    }

    public function test_the_work_stage_reorders_the_suggestions(): void
    {
        $this->fakeSources();

        $suggestions = $this->app->make(ToolAdvisor::class)->suggestions(new Answers(Hazard::Noise, WorkStage::Control));

        $this->assertSame('مقاله', $suggestions[0]->kind);
    }

    public function test_the_page_shows_the_suggestions(): void
    {
        $this->fakeSources();

        $this->get(route('tools.advisor', ['hazard' => 'noise']))
            ->assertOk()
            ->assertSee('با انتخاب «صدا»')
            ->assertSee('اندازه‌گیری صدا در محیط کار')
            ->assertSee('قالب گزارش صدا و ارتعاش');
    }

    public function test_the_hub_links_to_the_advisor(): void
    {
        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee(route('tools.advisor'), escape: false);
    }

    /** جست‌وجوی ساختگی به‌جای ماژول‌های دیگر؛ دستیار فقط قرارداد را می‌شناسد. */
    private function fakeSources(): void
    {
        $source = new class implements SearchSource
        {
            public function search(SearchQuery $query, int $limit): ?SearchGroup
            {
                return new SearchGroup('encyclopedia', 'دانشنامه', [
                    new SearchHit('اندازه‌گیری صدا در محیط کار', 'https://example.test/articles/noise'),
                ], 1, 10);
            }

            public function directUrl(SearchQuery $query): ?string
            {
                return null;
            }
        };

        $shop = new class implements SearchSource
        {
            public function search(SearchQuery $query, int $limit): ?SearchGroup
            {
                return new SearchGroup('commerce', 'فروشگاه', [
                    new SearchHit('قالب گزارش صدا و ارتعاش', 'https://example.test/shop/noise-report'),
                ], 1, 40);
            }

            public function directUrl(SearchQuery $query): ?string
            {
                return null;
            }
        };

        $this->app->forgetInstance(ToolAdvisor::class);
        $this->app->singleton(ToolAdvisor::class, fn (): ToolAdvisor => new ToolAdvisor(
            $this->app->make(ToolCatalog::class),
            (array) config('tools.advisor', []),
            [$source, $shop],
        ));
    }
}
