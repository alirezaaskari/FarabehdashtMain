<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Http\Controllers;

use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Services\ConsultantPresenter;
use App\Modules\Consulting\Services\DirectoryCatalog;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * دایرکتوری خدمات تخصصی (بخش ۱۹-۵): مشاوران و آزمایشگاه‌ها بر پایه خدمت و شهر.
 *
 * هر ترکیب خدمت و شهر یک نشانی ثابت دارد (`/directory/noise/isfahan`)؛ فرم
 * پالایش به همان نشانی می‌رود تا نسخه پرسش‌دار تکراری ساخته نشود. صفحه‌ای با
 * کمتر از دو ارائه‌دهنده noindex است (DEC-59).
 */
final readonly class DirectoryController
{
    public function __construct(
        private DirectoryCatalog $catalog,
        private ConsultantPresenter $presenter,
        private Repository $config,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $service = $this->catalog->serviceName((string) $request->query('service')) === null ? null : (string) $request->query('service');
        $city = $this->catalog->cityName((string) $request->query('city')) === null ? null : (string) $request->query('city');

        if ($service !== null) {
            return $city === null
                ? to_route('consulting.directory.service', $service)
                : to_route('consulting.directory.city', [$service, $city]);
        }

        $providers = $this->page($this->catalog->providers(null, $city));
        $coverage = $this->catalog->coverage();

        $seo = new SeoMeta(
            title: 'خدمات تخصصی بهداشت حرفه‌ای',
            description: 'مشاوران و آزمایشگاه‌های بهداشت حرفه‌ای برای اندازه‌گیری صدا، روشنایی، عوامل شیمیایی، ارگونومی و دیگر خدمت‌ها، به تفکیک شهر.',
            canonical: route('consulting.directory.index'),
        );

        return view('consulting::directory.index', [
            ...$this->shared(),
            'providers' => $providers,
            'city' => $city,
            'counts' => array_map(static fn (array $cities): int => array_sum($cities), $coverage),
            // پالایش شهر بدون خدمت نشانی ثابت ندارد و تکراری است.
            'seo' => $city === null ? $seo : $seo->noindexed(),
        ]);
    }

    public function service(string $service): View
    {
        $name = $this->catalog->serviceName($service) ?? throw new NotFoundHttpException('این خدمت در فهرست نیست.');
        $providers = $this->page($this->catalog->providers($service));
        $url = route('consulting.directory.service', $service);

        return view('consulting::directory.listing', [
            ...$this->shared(),
            'service' => $service,
            'serviceName' => $name,
            'city' => null,
            'cityName' => null,
            'providers' => $providers,
            'cities' => $this->catalog->coverage()[$service] ?? [],
            'seo' => $this->seo($name, 'ارائه‌دهندگان «'.$name.'» در فرابهداشت: مشاوران و آزمایشگاه‌های بهداشت حرفه‌ای به تفکیک شهر.', $url, $providers->total(), [
                ['name' => $name, 'url' => $url],
            ]),
        ]);
    }

    public function city(string $service, string $city): View
    {
        $name = $this->catalog->serviceName($service) ?? throw new NotFoundHttpException('این خدمت در فهرست نیست.');
        $cityName = $this->catalog->cityName($city) ?? throw new NotFoundHttpException('این شهر در فهرست نیست.');
        $providers = $this->page($this->catalog->providers($service, $city));
        $title = $name.' در '.$cityName;
        $url = route('consulting.directory.city', [$service, $city]);

        return view('consulting::directory.listing', [
            ...$this->shared(),
            'service' => $service,
            'serviceName' => $name,
            'city' => $city,
            'cityName' => $cityName,
            'providers' => $providers,
            'cities' => [],
            'seo' => $this->seo($title, 'مشاوران و آزمایشگاه‌های «'.$name.'» در '.$cityName.'، با معرفی و خدمت‌های هر کدام.', $url, $providers->total(), [
                ['name' => $name, 'url' => route('consulting.directory.service', $service)],
                ['name' => $title, 'url' => $url],
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function shared(): array
    {
        return [
            'services' => $this->catalog->services(),
            'citiesByProvince' => $this->catalog->citiesByProvince(),
            'presenter' => $this->presenter,
            'catalog' => $this->catalog,
        ];
    }

    /**
     * @param  Builder<ConsultantProfile>  $query
     * @return LengthAwarePaginator<int, ConsultantProfile>
     */
    private function page($query): LengthAwarePaginator
    {
        return $query->orderBy('display_name')->paginate((int) $this->config->get('consulting.directory.per_page', 24));
    }

    /** @param  list<array{name: string, url: string}>  $trail */
    private function seo(string $title, string $description, string $url, int $providers, array $trail): SeoMeta
    {
        $seo = (new SeoMeta(title: $title, description: $description, canonical: $url))->withSchema(Schema::graph(
            Schema::webPage($title, $url),
            Schema::breadcrumbs([['name' => 'خدمات تخصصی', 'url' => route('consulting.directory.index')], ...$trail]),
        ));

        return $this->catalog->isIndexable($providers) ? $seo : $seo->noindexed();
    }
}
