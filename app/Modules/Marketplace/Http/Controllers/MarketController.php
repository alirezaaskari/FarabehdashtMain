<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Http\Controllers;

use App\Models\User;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Services\MarketCatalog;
use App\Modules\Marketplace\Services\ProjectAccess;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صفحه‌های عمومی بازار پروژه: فهرست با پالایش، صفحه ثابت خدمت و شهر، و صفحه هر پروژه.
 *
 * پالایش تنها خدمت یا تنها شهر به نشانی ثابت همان صفحه می‌رود؛ ترکیب
 * noindex است. پروژه خصوصی (DEC-90) فقط برای کارفرما و مدیر باز می‌شود.
 */
final readonly class MarketController
{
    public function __construct(
        private MarketCatalog $catalog,
        private ProjectAccess $access,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $service = $this->catalog->serviceName((string) $request->query('service')) === null ? null : (string) $request->query('service');
        $city = $this->catalog->cityName((string) $request->query('city')) === null ? null : (string) $request->query('city');

        if (($service === null) !== ($city === null)) {
            return $service !== null ? to_route('market.service', $service) : to_route('market.city', (string) $city);
        }

        $seo = new SeoMeta(
            title: 'بازار پروژه بهداشت حرفه‌ای و HSE',
            description: 'پروژه‌های اندازه‌گیری، ارزیابی و مستندسازی HSE کارفرماها؛ مشاوران و آزمایشگاه‌های تأییدشده پیشنهاد می‌دهند و پول هر مرحله تا تحویل در امانت فرابهداشت می‌ماند.',
            canonical: route('market.index'),
        );

        return view('marketplace::market.index', [
            ...$this->shared(),
            'projects' => $this->page($this->catalog->listed($service, $city)),
            'service' => $service,
            'city' => $city,
            'serviceCounts' => $this->catalog->serviceCounts(),
            'cityCounts' => array_slice($this->catalog->cityCounts(), 0, 12, true),
            'seo' => $service === null ? $seo : $seo->noindexed(),
        ]);
    }

    public function service(string $service): View
    {
        $name = $this->catalog->serviceName($service) ?? throw new NotFoundHttpException('این نوع کار در فهرست نیست.');
        $projects = $this->page($this->catalog->listed($service));
        $title = 'پروژه‌های '.$name;

        return view('marketplace::market.listing', [
            ...$this->shared(),
            'projects' => $projects,
            'title' => $title,
            'lede' => 'پروژه‌های باز «'.$name.'» که کارفرماها در فرابهداشت تعریف کرده‌اند؛ تازه‌ترین بالاتر.',
            'service' => $service,
            'city' => null,
            'seo' => $this->seo($title, 'پروژه‌های باز '.$name.' برای مشاوران و آزمایشگاه‌های بهداشت حرفه‌ای در فرابهداشت.', route('market.service', $service), $projects->total()),
        ]);
    }

    public function city(string $city): View
    {
        $name = $this->catalog->cityName($city) ?? throw new NotFoundHttpException('این شهر در فهرست نیست.');
        $projects = $this->page($this->catalog->listed(null, $city));
        $title = 'پروژه‌های HSE در '.$name;

        return view('marketplace::market.listing', [
            ...$this->shared(),
            'projects' => $projects,
            'title' => $title,
            'lede' => 'پروژه‌های باز اندازه‌گیری، ارزیابی و مستندسازی در '.$name.'؛ تازه‌ترین بالاتر.',
            'service' => null,
            'city' => $city,
            'seo' => $this->seo($title, 'پروژه‌های باز بهداشت حرفه‌ای و ایمنی در '.$name.' در فرابهداشت.', route('market.city', $city), $projects->total()),
        ]);
    }

    public function show(Request $request, int $project): View
    {
        $record = MarketProject::query()->with('files')->find($project);
        $viewer = $request->user();

        if ($record === null || ! $this->access->canView($viewer instanceof User ? $viewer : null, $record)) {
            throw new NotFoundHttpException('این پروژه پیدا نشد.');
        }

        $url = route('market.show', $record->id);
        $serviceName = (string) $this->catalog->serviceName($record->service);
        $seo = new SeoMeta(
            title: $record->title.' — بازار پروژه',
            description: Str::limit(trim($serviceName.'، '.$this->catalog->place($record).'. '.$record->description), 155),
            canonical: $url,
        );
        $listed = ! $record->is_private && $record->acceptsBids();

        return view('marketplace::market.show', [
            'project' => $record,
            'catalog' => $this->catalog,
            'serviceName' => $serviceName,
            'open' => $record->acceptsBids(),
            'owner' => $viewer !== null && $viewer->getKey() === $record->client_user_id,
            'myBid' => $viewer === null ? null : MarketBid::query()->where('project_id', $record->id)->where('provider_user_id', $viewer->getKey())->first(),
            // فقط پروژه باز عمومی ایندکس می‌شود؛ بسته، در حال انجام و خصوصی نه.
            'seo' => $listed ? $seo->withSchema(Schema::graph(
                Schema::webPage($record->title, $url),
                Schema::breadcrumbs([['name' => 'بازار پروژه', 'url' => route('market.index')], ['name' => $record->title, 'url' => $url]]),
            )) : $seo->noindexed(),
        ]);
    }

    /** @return array<string, mixed> */
    private function shared(): array
    {
        return [
            'catalog' => $this->catalog,
            'services' => $this->catalog->services(),
            'citiesByProvince' => $this->catalog->citiesByProvince(),
        ];
    }

    /**
     * @param  Builder<MarketProject>  $query
     * @return LengthAwarePaginator<int, MarketProject>
     */
    private function page(Builder $query): LengthAwarePaginator
    {
        return $query->latest('published_at')->paginate($this->catalog->perPage())->withQueryString();
    }

    private function seo(string $title, string $description, string $url, int $projects): SeoMeta
    {
        $seo = (new SeoMeta(title: $title, description: $description, canonical: $url))->withSchema(Schema::graph(
            Schema::webPage($title, $url),
            Schema::breadcrumbs([['name' => 'بازار پروژه', 'url' => route('market.index')], ['name' => $title, 'url' => $url]]),
        ));

        return $this->catalog->isIndexable($projects) ? $seo : $seo->noindexed();
    }
}
