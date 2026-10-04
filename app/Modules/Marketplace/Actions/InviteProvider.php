<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Contracts\ServiceProviderDirectory;
use App\Models\User;
use App\Modules\Marketplace\Domain\MarketInvite;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Events\ProviderInvited;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * دعوت مستقیم از صفحه مشاور یا آزمایشگاه در دایرکتوری (DEC-89). دعوت‌شده
 * اعلان می‌گیرد و پروژه خصوصی را هم می‌بیند (DEC-90).
 */
final readonly class InviteProvider
{
    public function __construct(
        private Container $container,
        private Dispatcher $events,
    ) {}

    public function handle(User $client, MarketProject $project, int $providerId): MarketInvite
    {
        if ($project->client_user_id !== $client->getKey()) {
            throw new RuntimeException('این پروژه مال شما نیست.');
        }

        if (! $project->acceptsBids()) {
            throw new RuntimeException('فقط به پروژه منتشرشده‌ای که هنوز پیشنهاد می‌پذیرد دعوت می‌شود.');
        }

        if ($providerId === $project->client_user_id || ! array_key_exists($providerId, $this->providers([$providerId]))) {
            throw new RuntimeException('این مشاور یا آزمایشگاه صفحه منتشرشده‌ای ندارد.');
        }

        $invite = MarketInvite::query()->firstOrCreate(['project_id' => $project->id, 'provider_user_id' => $providerId]);
        $invite->setRelation('project', $project);

        if ($invite->wasRecentlyCreated) {
            $this->events->dispatch(new ProviderInvited($invite, (int) $client->getKey()));
        }

        return $invite;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, array{name: string, url: string, laboratory: bool, city: string|null}>
     */
    private function providers(array $ids): array
    {
        return $this->container->bound(ServiceProviderDirectory::class)
            ? $this->container->make(ServiceProviderDirectory::class)->providersOf($ids)
            : [];
    }
}
