<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Http\Controllers;

use App\Contracts\ServiceProviderDirectory;
use App\Models\User;
use App\Modules\Marketplace\Actions\PostMessage;
use App\Modules\Marketplace\Domain\Enums\BidStatus;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Modules\Marketplace\Services\ContractTerms;
use App\Modules\Marketplace\Services\MarketCatalog;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صفحه یک پیشنهاد و گفت‌وگوی آن، مشترک میان کارفرما و مجری (DEC-80).
 * هر کدام پیام‌های رسیده و پیام‌های خودش را می‌بیند؛ پیام نگه‌داشته طرف
 * مقابل دیده نمی‌شود.
 */
final readonly class ThreadController
{
    public function __construct(
        private MarketCatalog $catalog,
        private Container $container,
        private Repository $config,
        private ContractTerms $terms,
    ) {}

    public function show(Request $request, string $uuid): View
    {
        $user = $this->user($request);
        $bid = $this->bid($user, $uuid);
        $userId = (int) $user->getKey();

        return view('marketplace::bids.thread', [
            'bid' => $bid,
            'project' => $bid->project,
            'catalog' => $this->catalog,
            'provider' => $this->provider($bid->provider_user_id),
            'isProvider' => $bid->provider_user_id === $userId,
            'userId' => $userId,
            'messages' => $bid->messages()->oldest('id')->get()->filter(static fn (MarketMessage $message): bool => $message->isVisibleTo($userId))->values(),
            'messageMax' => (int) $this->config->get('marketplace.messages.max', 2000),
            'contract' => $bid->contract,
            'canAccept' => $bid->project->client_user_id === $userId && $bid->status === BidStatus::Active
                && $bid->project->status === ProjectStatus::Open && $this->terms->isOpen(),
        ]);
    }

    public function message(Request $request, string $uuid, PostMessage $post): RedirectResponse
    {
        $user = $this->user($request);
        $bid = $this->bid($user, $uuid);

        try {
            $message = $post->handle($user, $bid, (string) $request->input('body'));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['body' => $exception->getMessage()]);
        }

        return to_route('market.bids.show', $bid->uuid)->withFragment('messages')->with('status', $message->status->value === 'held'
            ? 'پیام تا بررسی مدیر نگه داشته شد. شماره، ایمیل، لینک و نام پیام‌رسان رد و بدل نمی‌شود.'
            : 'پیام فرستاده شد.');
    }

    /** @return array{name: string, url: string, laboratory: bool, city: string|null}|null */
    private function provider(int $userId): ?array
    {
        if (! $this->container->bound(ServiceProviderDirectory::class)) {
            return null;
        }

        return $this->container->make(ServiceProviderDirectory::class)->providersOf([$userId])[$userId] ?? null;
    }

    private function bid(User $user, string $uuid): MarketBid
    {
        $bid = MarketBid::query()->where('uuid', $uuid)->with('project')->first();

        if ($bid === null || ! $bid->involves((int) $user->getKey())) {
            throw new NotFoundHttpException('این گفت‌وگو پیدا نشد.');
        }

        return $bid;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
