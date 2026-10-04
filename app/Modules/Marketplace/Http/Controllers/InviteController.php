<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Http\Controllers;

use App\Contracts\ServiceProviderDirectory;
use App\Models\User;
use App\Modules\Marketplace\Actions\InviteProvider;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketProject;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * دعوت مستقیم از صفحه مشاور یا آزمایشگاه (DEC-89): کارفرما یکی از پروژه‌های
 * باز خودش را انتخاب می‌کند.
 */
final readonly class InviteController
{
    public function __construct(private Container $container) {}

    public function create(Request $request, int $provider): View
    {
        $user = $this->user($request);

        return view('marketplace::client.invite', [
            'provider' => $this->provider($provider),
            'providerId' => $provider,
            'projects' => MarketProject::query()
                ->where('client_user_id', $user->getKey())
                ->where('status', ProjectStatus::Open)
                ->latest('id')
                ->get()
                ->filter(static fn (MarketProject $project): bool => $project->acceptsBids())
                ->values(),
        ]);
    }

    public function store(Request $request, int $provider, InviteProvider $invite): RedirectResponse
    {
        $user = $this->user($request);
        $project = MarketProject::query()->where('uuid', (string) $request->input('project'))->first();

        if ($project === null) {
            return back()->withErrors(['invite' => 'یکی از پروژه‌های باز خودتان را انتخاب کنید.']);
        }

        try {
            $invite->handle($user, $project, $provider);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['invite' => $exception->getMessage()]);
        }

        return to_route('market.client.show', $project->uuid)->with('status', 'دعوت فرستاده شد؛ اگر پیشنهاد بدهد همین‌جا می‌بینید.');
    }

    /** @return array{name: string, url: string, laboratory: bool, city: string|null} */
    private function provider(int $id): array
    {
        $providers = $this->container->bound(ServiceProviderDirectory::class)
            ? $this->container->make(ServiceProviderDirectory::class)->providersOf([$id])
            : [];

        return $providers[$id] ?? throw new NotFoundHttpException('این مشاور یا آزمایشگاه پیدا نشد.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
