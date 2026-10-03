<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Http\Controllers;

use App\Contracts\ServiceProviderDirectory;
use App\Models\User;
use App\Modules\Marketplace\Actions\CloseProject;
use App\Modules\Marketplace\Actions\SubmitProject;
use App\Modules\Marketplace\Domain\Enums\BidStatus;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Domain\ProjectDraft;
use App\Modules\Marketplace\Services\MarketCatalog;
use App\Support\Money;
use App\Support\Regions\Regions;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * پروژه‌های کارفرما در میزکار: تعریف، اصلاح پیش از انتشار، بستن، و دیدن
 * پیشنهادها کنار هم (قیمت، زمان، مرحله‌ها و صفحه عمومی مجری).
 */
final readonly class ClientProjectController
{
    public function __construct(
        private MarketCatalog $catalog,
        private Regions $regions,
        private Repository $config,
        private Container $container,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);

        return view('marketplace::client.index', [
            'projects' => MarketProject::query()->where('client_user_id', $user->getKey())
                ->withCount(['bids' => static fn ($query) => $query->whereIn('status', [BidStatus::Active, BidStatus::Accepted])])
                ->latest('id')->get(),
            'catalog' => $this->catalog,
            'verified' => $user->mobile_verified_at !== null,
        ]);
    }

    public function show(Request $request, string $uuid): View
    {
        $project = $this->project($request, $uuid);
        $bids = $project->bids()->whereIn('status', [BidStatus::Active, BidStatus::Accepted])->orderBy('total_toman')->get();
        $ids = [...$bids->pluck('provider_user_id')->all(), ...$project->invites()->pluck('provider_user_id')->all()];
        $providers = $this->container->bound(ServiceProviderDirectory::class)
            ? $this->container->make(ServiceProviderDirectory::class)->providersOf(array_values(array_unique(array_map(intval(...), $ids))))
            : [];

        return view('marketplace::client.show', [
            'project' => $project,
            'bids' => $bids,
            'invites' => $project->invites()->latest('id')->get(),
            'providers' => $providers,
            'catalog' => $this->catalog,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form($request, null);
    }

    public function store(Request $request, SubmitProject $submit): RedirectResponse
    {
        $draft = $this->draft($request);

        try {
            $submit->create($this->user($request), $draft, $this->uploads($request));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['project' => $exception->getMessage()]);
        }

        return to_route('market.client.index')->with('status', 'پروژه برای تأیید مدیر فرستاده شد. پس از تأیید اعلان می‌گیرید و پروژه پیشنهاد می‌پذیرد.');
    }

    public function edit(Request $request, string $uuid): View|RedirectResponse
    {
        $project = $this->project($request, $uuid);

        if (! $project->status->isEditable()) {
            return to_route('market.client.index');
        }

        return $this->form($request, $project->load('files'));
    }

    public function update(Request $request, string $uuid, SubmitProject $submit): RedirectResponse
    {
        $project = $this->project($request, $uuid);
        $draft = $this->draft($request);

        try {
            $submit->update($this->user($request), $project, $draft, $this->uploads($request));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['project' => $exception->getMessage()]);
        }

        return to_route('market.client.index')->with('status', 'اصلاح پروژه برای تأیید مدیر فرستاده شد.');
    }

    public function close(Request $request, string $uuid, CloseProject $close): RedirectResponse
    {
        try {
            $close->handle($this->user($request), $this->project($request, $uuid));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['project' => $exception->getMessage()]);
        }

        return to_route('market.client.index')->with('status', 'پروژه بسته شد و دیگر پیشنهاد نمی‌پذیرد.');
    }

    private function form(Request $request, ?MarketProject $project): View
    {
        return view('marketplace::client.form', [
            'project' => $project,
            'services' => $this->catalog->services(),
            'regions' => $this->catalog->regionsForForm(),
            'limits' => (array) $this->config->get('marketplace.projects', []),
            'budgetMin' => Money::toman((int) $this->config->get('marketplace.projects.budget_min_toman', 1_000_000))->format(),
            'verified' => $this->user($request)->mobile_verified_at !== null,
        ]);
    }

    private function draft(Request $request): ProjectDraft
    {
        $limits = (array) $this->config->get('marketplace.projects', []);
        $budgetMin = (int) ($limits['budget_min_toman'] ?? 1_000_000);
        $budgetMax = (int) ($limits['budget_max_toman'] ?? 10_000_000_000);

        // مبلغ با جداکننده فارسی یا لاتین نوشته می‌شود؛ پیش از اعتبارسنجی عدد خالص می‌شود.
        foreach (['budget_min', 'budget_max'] as $key) {
            $value = trim((string) $request->input($key));
            try {
                $request->merge([$key => $value === '' ? null : Money::fromInput($value)->toman]);
            } catch (InvalidArgumentException) {
                // همان متن می‌ماند تا قاعده integer پیام خطای فرم را بدهد.
            }
        }

        $remote = $request->boolean('remote');
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:'.($limits['title_min'] ?? 10), 'max:'.($limits['title_max'] ?? 120)],
            'service' => ['required', Rule::in(array_keys($this->catalog->services()))],
            'province' => $remote ? ['nullable'] : ['required', Rule::in(array_keys($this->regions->provinces()))],
            'city' => $remote ? ['nullable'] : ['required', Rule::in(array_keys($this->regions->cities((string) $request->input('province'))))],
            'budget_min' => ['required', 'integer', 'min:'.$budgetMin, 'max:'.$budgetMax],
            'budget_max' => ['required', 'integer', 'min:'.$budgetMin, 'max:'.$budgetMax, 'gte:budget_min'],
            'wanted_by' => ['nullable', 'date', 'after:today', 'before:'.Carbon::today()->addDays((int) ($limits['wanted_days_max'] ?? 365))->toDateString()],
            'description' => ['required', 'string', 'min:'.($limits['description_min'] ?? 80), 'max:'.($limits['description_max'] ?? 6000)],
            'client_name' => ['nullable', 'string', 'max:'.($limits['client_name_max'] ?? 80)],
            'files' => ['nullable', 'array', 'max:'.($limits['files_max'] ?? 5)],
            'files.*' => ['file', 'max:'.($limits['file_max_kb'] ?? 10_240), 'extensions:'.implode(',', (array) ($limits['file_types'] ?? ['pdf']))],
        ], [
            'city.in' => 'شهر را از استانی که انتخاب کرده‌اید برگزینید.',
            'budget_min.min' => 'کمینه بودجه پروژه '.Money::toman($budgetMin)->format().' است.',
            'budget_max.min' => 'کمینه بودجه پروژه '.Money::toman($budgetMin)->format().' است.',
            'budget_max.gte' => 'سقف بودجه از کف آن کمتر است.',
            'wanted_by.after' => 'مهلت دلخواه باید روزی پس از امروز باشد.',
            'files.*.extensions' => 'پیوست باید PDF، تصویر، نقشه DWG، اکسل، ورد یا ZIP باشد.',
        ]);

        return new ProjectDraft(
            title: trim((string) $validated['title']),
            service: (string) $validated['service'],
            province: $remote ? null : (string) $validated['province'],
            city: $remote ? null : (string) $validated['city'],
            remote: $remote,
            budgetMinToman: (int) $validated['budget_min'],
            budgetMaxToman: (int) $validated['budget_max'],
            wantedBy: empty($validated['wanted_by']) ? null : Carbon::parse((string) $validated['wanted_by']),
            description: trim((string) $validated['description']),
            clientName: trim((string) ($validated['client_name'] ?? '')) === '' ? null : trim((string) $validated['client_name']),
            showClientName: $request->boolean('show_client_name'),
            private: $request->boolean('private'),
        );
    }

    /** @return list<UploadedFile> */
    private function uploads(Request $request): array
    {
        $files = $request->file('files', []);

        return array_values(is_array($files) ? $files : [$files]);
    }

    private function project(Request $request, string $uuid): MarketProject
    {
        $project = MarketProject::query()->where('uuid', $uuid)->first();

        if ($project === null || $project->client_user_id !== $this->user($request)->getKey()) {
            throw new NotFoundHttpException('این پروژه پیدا نشد.');
        }

        return $project;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
