<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Http\Controllers;

use App\Models\User;
use App\Modules\Consulting\Actions\ConsultingCheckout;
use App\Modules\Consulting\Actions\ManageConsultingService;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\ServiceKind;
use App\Modules\Consulting\Domain\Enums\ServiceStatus;
use App\Support\Regions\Regions;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * خدمت‌های مشاور در میزکار: فهرست، تعریف، ویرایش و خروج از فروش.
 */
final readonly class ConsultingServiceController
{
    public function __construct(
        private ManageConsultingService $manage,
        private ConsultingCheckout $checkout,
        private Regions $regions,
        private Repository $config,
    ) {}

    public function index(Request $request): View
    {
        $profile = $this->profile($request);

        return view('consulting::services.index', [
            'profile' => $profile,
            'services' => $profile === null ? collect() : $profile->services()->latest()->get(),
            'commissionPercent' => intdiv($this->checkout->rateBp(), 100),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form($request, null);
    }

    public function edit(Request $request, string $uuid): View
    {
        return $this->form($request, $this->owned($request, $uuid));
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $max = (int) $this->config->get('consulting.services.max_per_consultant', 10);

        if ($profile !== null && $profile->services()->where('status', '!=', ServiceStatus::Retired)->count() >= $max) {
            return back()->withInput()->withErrors(['service' => 'بیش از '.$max.' خدمت فعال نمی‌شود داشت؛ یکی را از فروش خارج کنید.']);
        }

        return $this->save($request, null);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        return $this->save($request, $this->owned($request, $uuid));
    }

    public function retire(Request $request, string $uuid): RedirectResponse
    {
        $this->manage->retire($this->user($request), $this->owned($request, $uuid));

        return to_route('consulting.services.index')->with('status', 'خدمت از فروش خارج شد. درخواست‌های باز آن ادامه دارند.');
    }

    private function form(Request $request, ?ConsultingService $service): View
    {
        return view('consulting::services.form', [
            'service' => $service,
            'profile' => $this->profile($request),
            'kinds' => ServiceKind::cases(),
            'regions' => collect($this->regions->provinces())
                ->map(fn (string $name, string $key): array => ['name' => $name, 'cities' => $this->regions->cities($key)])
                ->all(),
            'limits' => (array) $this->config->get('consulting.services', []),
        ]);
    }

    private function save(Request $request, ?ConsultingService $service): RedirectResponse
    {
        $limits = (array) $this->config->get('consulting.services', []);
        $cities = [];

        foreach (array_keys($this->regions->provinces()) as $province) {
            $cities = [...$cities, ...array_keys($this->regions->cities($province))];
        }

        $validated = $request->validate([
            'kind' => ['required', Rule::enum(ServiceKind::class)],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:50', 'max:3000'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'price_toman' => ['required', 'integer', 'min:'.($limits['price_min_toman'] ?? 100_000), 'max:'.($limits['price_max_toman'] ?? 50_000_000)],
            'cities' => ['required_if:kind,visit', 'array'],
            'cities.*' => [Rule::in($cities)],
        ], [
            'cities.required_if' => 'برای بازدید حضوری دست‌کم یک شهر انتخاب کنید.',
        ]);

        try {
            $this->manage->submit($this->user($request), [
                'kind' => ServiceKind::from($validated['kind']),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'duration_minutes' => (int) $validated['duration_minutes'],
                'price_toman' => (int) $validated['price_toman'],
                'cities' => array_values(array_map(strval(...), $validated['cities'] ?? [])),
            ], $service);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['service' => $exception->getMessage()]);
        }

        return to_route('consulting.services.index')->with('status', 'خدمت برای تأیید مدیر فرستاده شد و پس از تأیید روی صفحه شما قابل خرید است.');
    }

    private function owned(Request $request, string $uuid): ConsultingService
    {
        $service = ConsultingService::query()->where('uuid', $uuid)->with('profile')->first();

        if ($service === null || $service->profile->user_id !== $this->user($request)->getKey()) {
            throw new NotFoundHttpException('این خدمت پیدا نشد.');
        }

        return $service;
    }

    private function profile(Request $request): ?ConsultantProfile
    {
        return ConsultantProfile::query()->where('user_id', $this->user($request)->getKey())->first();
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
