<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Http\Controllers;

use App\Contracts\ExpertAnswerDirectory;
use App\Contracts\Taxonomy;
use App\Modules\Consulting\Actions\ReviewConsultantProfile;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Services\ConsultantPresenter;
use App\Support\Regions\Regions;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * فهرست عمومی مشاوران و صفحه هر مشاور.
 */
final readonly class ConsultantController
{
    public function __construct(
        private ConsultantPresenter $presenter,
        private Taxonomy $taxonomy,
        private Regions $regions,
        private Repository $config,
        private Container $container,
    ) {}

    public function index(Request $request): View
    {
        $terms = $this->presenter->domainTerms();
        $domain = collect($terms)->firstWhere('slug', (string) $request->query('domain'));
        $province = array_key_exists((string) $request->query('province'), $this->regions->provinces()) ? (string) $request->query('province') : null;

        $profiles = ConsultantProfile::query()
            ->listed()
            ->when($domain instanceof TermData, fn ($query) => $query->whereKey(
                $this->taxonomy->taggedIds(ConsultantProfile::class, ReviewConsultantProfile::TAXONOMY, $domain->slug),
            ))
            ->when($province !== null, static fn ($query) => $query->where('province', $province))
            ->orderBy('display_name')
            ->paginate((int) $this->config->get('consulting.per_page', 24))
            ->withQueryString();

        $seo = new SeoMeta(
            title: 'مشاوران بهداشت حرفه‌ای',
            description: 'مشاوران بهداشت حرفه‌ای و ایمنی فرابهداشت: حوزه تخصص، شهر، معرفی و پاسخ‌های منتشرشده هر مشاور.',
            canonical: route('consulting.index'),
        );

        return view('consulting::index', [
            'profiles' => $profiles,
            'photos' => $this->presenter->photos($profiles->items()),
            'presenter' => $this->presenter,
            'terms' => $terms,
            'domain' => $domain,
            'provinces' => $this->regions->provinces(),
            'province' => $province,
            // فهرست پالایش‌شده صفحه تکراری است؛ فقط فهرست اصلی ایندکس می‌شود.
            'seo' => $domain !== null || $province !== null ? $seo->noindexed() : $seo,
        ]);
    }

    public function show(string $slug): View
    {
        $profile = ConsultantProfile::query()->listed()->where('slug', $slug)->with('user')->first();

        if ($profile === null || ! $profile->user->can('consulting.services.manage')) {
            throw new NotFoundHttpException('این مشاور پیدا نشد.');
        }

        $domains = $this->presenter->domainsOf($profile);
        $photo = $this->presenter->photo($profile->photo_id);
        $place = $this->presenter->place($profile->province, $profile->city);
        $url = route('consulting.show', $profile->slug);
        $answers = $this->container->bound(ExpertAnswerDirectory::class)
            ? $this->container->make(ExpertAnswerDirectory::class)
            : null;

        return view('consulting::show', [
            'profile' => $profile,
            'domains' => $domains,
            'photo' => $photo,
            'place' => $place,
            'answers' => $answers?->publishedBy($profile->user_id, (int) $this->config->get('consulting.answers_on_profile', 6)) ?? [],
            'answerCount' => $answers?->countPublishedBy($profile->user_id) ?? 0,
            'seo' => (new SeoMeta(
                title: $profile->display_name.' — مشاور بهداشت حرفه‌ای',
                description: Str::limit(trim($profile->headline.'. '.$profile->bio), 155),
                canonical: $url,
                image: $photo?->url,
            ))->withSchema(Schema::person(
                name: (string) $profile->display_name,
                url: $url,
                description: Str::limit((string) $profile->bio, 300),
                image: $photo?->url,
                jobTitle: $profile->headline,
                locality: $place === '' ? null : $place,
                knowsAbout: array_map(static fn (TermData $term): string => $term->name, $domains),
            )),
        ]);
    }
}
