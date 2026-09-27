<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Services;

use App\Contracts\Taxonomy;
use App\Contracts\ToolDirectory;
use App\Modules\Jobs\Domain\JobPosting;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Route;

/**
 * تطبیق مهارت‌های گذرنامه با آگهی (۲۰-۴). نتیجه فقط به خود کارجو نشان داده
 * می‌شود، نه به کارفرما، و جایی ذخیره نمی‌شود.
 */
final readonly class JobMatcher
{
    public function __construct(
        private Taxonomy $taxonomy,
        private JobCatalog $catalog,
        private Container $container,
        private Repository $config,
    ) {}

    /**
     * @param  list<int>  $userSkillIds
     * @return array{have: list<TermData>, missing: list<TermData>}
     */
    public function compare(JobPosting $posting, array $userSkillIds): array
    {
        $have = [];
        $missing = [];

        foreach ($this->catalog->skillsOf($posting) as $term) {
            if (in_array($term->id, $userSkillIds, true)) {
                $have[] = $term;
            } else {
                $missing[] = $term;
            }
        }

        return ['have' => $have, 'missing' => $missing];
    }

    /**
     * آگهی‌های زنده‌ای که دست‌کم یک مهارت مشترک دارند، به ترتیب تعداد مهارت
     * مشترک و بعد تازگی.
     *
     * @param  list<int>  $userSkillIds
     * @return list<array{posting: JobPosting, have: int, total: int}>
     */
    public function suggestions(array $userSkillIds, int $limit = 20): array
    {
        if ($userSkillIds === []) {
            return [];
        }

        $hits = [];

        foreach ($this->catalog->skills() as $term) {
            if (in_array($term->id, $userSkillIds, true)) {
                foreach ($this->taxonomy->taggedIds(JobPosting::class, JobCatalog::TAXONOMY, $term->slug) as $id) {
                    $hits[$id] = ($hits[$id] ?? 0) + 1;
                }
            }
        }

        if ($hits === []) {
            return [];
        }

        $postings = $this->catalog->live()->whereKey(array_keys($hits))->with('company')->get();

        $rows = $postings->map(fn (JobPosting $posting): array => [
            'posting' => $posting,
            'have' => $hits[$posting->id],
            'total' => max($hits[$posting->id], count($this->catalog->skillIdsOf($posting))),
        ])->all();

        usort($rows, static fn (array $a, array $b): int => [$b['have'], $b['posting']->published_at] <=> [$a['have'], $a['posting']->published_at]);

        return array_slice($rows, 0, $limit);
    }

    /**
     * راه ساختن یک مهارت: ابزارهای سایت که به آن نگاشت شده‌اند و جست‌وجوی
     * دوره و مقاله با نام همان مهارت.
     *
     * @return list<array{label: string, url: string}>
     */
    public function learnLinks(TermData $skill): array
    {
        $links = [];

        if ($this->container->bound(ToolDirectory::class) && Route::has('tools.show')) {
            $tools = $this->container->make(ToolDirectory::class);

            foreach ((array) $this->config->get('jobs.passport.tag_skills', []) as $tag => $slug) {
                if ($slug === $skill->slug && str_starts_with((string) $tag, 'formula:')) {
                    $tool = $tools->find(substr((string) $tag, 8));

                    if ($tool !== null) {
                        $links[] = ['label' => 'ابزار '.$tool->title, 'url' => route('tools.show', $tool->slug)];
                    }
                }
            }
        }

        $links = array_slice($links, 0, 2);

        if (Route::has('workspace.search')) {
            $links[] = ['label' => 'دوره و مقاله درباره '.$skill->name, 'url' => route('workspace.search', ['q' => $skill->name])];
        }

        return $links;
    }
}
