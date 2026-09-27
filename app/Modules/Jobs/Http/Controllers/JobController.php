<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Modules\Jobs\Domain\Enums\EmploymentType;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Services\JobCatalog;
use App\Modules\Jobs\Services\JobMatcher;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Jobs\Services\SkillPassport;
use App\Support\Regions\Regions;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صفحه‌های عمومی کاریابی: فهرست با پالایش، صفحه ثابت شهر و مهارت، و صفحه
 * هر آگهی با داده ساختاریافته `JobPosting`.
 *
 * دیدن آگهی رایگان و بی‌ورود است. پالایش تنها شهر یا تنها مهارت به نشانی
 * ثابت همان صفحه می‌رود؛ ترکیب پالایش‌ها noindex است. آگهی منقضی با برچسب
 * و noindex می‌ماند و پس از مهلت DEC-66 ۴۱۰ می‌شود.
 */
final readonly class JobController
{
    public function __construct(
        private JobCatalog $catalog,
        private JobPricing $pricing,
        private Regions $regions,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $city = $this->catalog->cityName((string) $request->query('city')) === null ? null : (string) $request->query('city');
        $skill = $this->catalog->skill((string) $request->query('skill'));
        $type = EmploymentType::tryFrom((string) $request->query('type'));

        if ($type === null && ($city === null) !== ($skill === null)) {
            return $city !== null ? to_route('jobs.city', $city) : to_route('jobs.skill', $skill?->slug);
        }

        $postings = $this->page($this->catalog->live($city, $skill?->slug, $type));
        $seo = new SeoMeta(
            title: 'آگهی‌های استخدام بهداشت حرفه‌ای و HSE',
            description: 'آگهی‌های شغلی کارشناس بهداشت حرفه‌ای، ایمنی و HSE به تفکیک شهر و مهارت؛ دیدن آگهی و ارسال درخواست برای کارجو رایگان است.',
            canonical: route('jobs.index'),
        );

        return view('jobs::postings.index', [
            ...$this->shared(),
            'postings' => $postings,
            'city' => $city,
            'skill' => $skill,
            'type' => $type,
            'cityCounts' => array_slice($this->catalog->cityCounts(), 0, 12, true),
            'skillCounts' => array_slice($this->catalog->skillCounts(), 0, 12, true),
            'seo' => $city === null && $skill === null && $type === null ? $seo : $seo->noindexed(),
        ]);
    }

    public function city(string $city): View
    {
        $name = $this->catalog->cityName($city) ?? throw new NotFoundHttpException('این شهر در فهرست نیست.');
        $postings = $this->page($this->catalog->live($city));
        $title = 'آگهی‌های استخدام HSE در '.$name;
        $url = route('jobs.city', $city);

        return view('jobs::postings.listing', [
            ...$this->shared(),
            'postings' => $postings,
            'title' => $title,
            'lede' => 'آگهی‌های کارشناس بهداشت حرفه‌ای، ایمنی و HSE در '.$name.'؛ تازه‌ترین بالاتر.',
            'city' => $city,
            'skill' => null,
            'seo' => $this->seo($title, 'آگهی‌های شغلی بهداشت حرفه‌ای و ایمنی در '.$name.' در فرابهداشت.', $url, $postings->total()),
        ]);
    }

    public function skill(string $skill): View
    {
        $term = $this->catalog->skill($skill) ?? throw new NotFoundHttpException('این مهارت در فهرست نیست.');
        $postings = $this->page($this->catalog->live(null, $term->slug));
        $title = 'آگهی‌های استخدام با مهارت '.$term->name;
        $url = route('jobs.skill', $term->slug);

        return view('jobs::postings.listing', [
            ...$this->shared(),
            'postings' => $postings,
            'title' => $title,
            'lede' => 'آگهی‌هایی که «'.$term->name.'» را از مهارت‌های لازم نام برده‌اند.',
            'city' => null,
            'skill' => $term,
            'seo' => $this->seo($title, 'آگهی‌های شغلی که مهارت «'.$term->name.'» می‌خواهند، در فرابهداشت.', $url, $postings->total()),
        ]);
    }

    public function show(Request $request, int $posting, SkillPassport $passport, JobMatcher $matcher): View
    {
        $record = JobPosting::query()->with('company')->find($posting);

        if ($record === null || $record->published_at === null || ! $record->company->isListed()) {
            throw new NotFoundHttpException('این آگهی پیدا نشد.');
        }

        $ended = $record->endedAt();
        $live = $record->isLive();

        if (! $live && $ended !== null && $ended->copy()->addDays($this->pricing->goneAfterDays())->isPast()) {
            throw new GoneHttpException('این آگهی مدتی است بسته شده و دیگر در دسترس نیست.');
        }

        $url = route('jobs.show', $record->id);
        $company = $record->company;
        $skills = $this->catalog->skillsOf($record);
        $seo = new SeoMeta(
            title: $record->title.' — '.$company->name,
            description: Str::limit(trim($record->title.'، '.$company->name.'، '.$this->catalog->place($record->province, $record->city).'. '.$record->description), 155),
            canonical: $url,
        );

        // ۲۰-۴: تطبیق فقط برای خود کاربر حساب و نشان داده می‌شود و جایی نمی‌رود.
        $viewer = $request->user();
        $match = $live && $viewer !== null && $skills !== []
            ? $matcher->compare($record, $passport->skillIds((int) $viewer->getKey()))
            : null;

        return view('jobs::postings.show', [
            'posting' => $record,
            'match' => $match,
            'matcher' => $matcher,
            'company' => $company,
            'skills' => $skills,
            'place' => $this->catalog->place($record->province, $record->city),
            'logo' => $this->catalog->logo($company->logo_id),
            'catalog' => $this->catalog,
            'live' => $live,
            'more' => $company->postings()->live()->whereKeyNot($record->id)->latest('published_at')->limit(4)->get(),
            // DEC-66: فقط آگهی زنده ایندکس و در جست‌وجوی شغل گوگل دیده می‌شود.
            'seo' => ! $live ? $seo->noindexed() : $seo->withSchema(Schema::jobPosting(
                title: (string) $record->title,
                url: $url,
                description: implode("\n\n", $record->descriptionParagraphs()),
                postedAt: $record->published_at,
                validThrough: $record->expires_at ?? Carbon::now(),
                employmentType: ($record->employment_type ?? EmploymentType::FullTime)->schemaValue(),
                company: (string) $company->name,
                companyUrl: route('jobs.companies.show', $company->slug),
                place: ['locality' => $this->regions->cityName($record->city), 'region' => $this->regions->provinceName($record->province)],
                salaryMin: $record->salaryMin(),
                salaryMax: $record->salaryMax(),
                experienceYears: (int) $record->min_experience_years,
            )),
        ]);
    }

    /** @return array<string, mixed> */
    private function shared(): array
    {
        return [
            'catalog' => $this->catalog,
            'skills' => $this->catalog->skills(),
            'types' => EmploymentType::cases(),
            'citiesByProvince' => $this->catalog->citiesByProvince(),
        ];
    }

    /**
     * @param  Builder<JobPosting>  $query
     * @return LengthAwarePaginator<int, JobPosting>
     */
    private function page(Builder $query): LengthAwarePaginator
    {
        return $query->latest('published_at')->paginate($this->catalog->perPage())->withQueryString();
    }

    private function seo(string $title, string $description, string $url, int $postings): SeoMeta
    {
        $seo = (new SeoMeta(title: $title, description: $description, canonical: $url))->withSchema(Schema::graph(
            Schema::webPage($title, $url),
            Schema::breadcrumbs([['name' => 'کاریابی', 'url' => route('jobs.index')], ['name' => $title, 'url' => $url]]),
        ));

        return $this->catalog->isIndexable($postings) ? $seo : $seo->noindexed();
    }
}
