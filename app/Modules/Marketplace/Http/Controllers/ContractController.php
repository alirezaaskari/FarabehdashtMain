<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Contracts\ServiceProviderDirectory;
use App\Models\User;
use App\Modules\Marketplace\Actions\AcceptBid;
use App\Modules\Marketplace\Actions\MilestoneCheckout;
use App\Modules\Marketplace\Actions\MilestoneFlow;
use App\Modules\Marketplace\Domain\DeliveryFile;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Modules\Marketplace\Services\MarketCatalog;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صفحه قرارداد، مشترک کارفرما و مجری: پذیرش پیشنهاد، پرداخت مرحله، تحویل،
 * تأیید یا درخواست اصلاح، و فایل‌های تحویل.
 */
final readonly class ContractController
{
    public function __construct(
        private MarketCatalog $catalog,
        private Container $container,
        private Repository $config,
        private Factory $storage,
    ) {}

    public function show(Request $request, string $uuid): View
    {
        $user = $this->user($request);
        $contract = $this->contract($user, $uuid);
        $contract->load(['project.files', 'milestones.deliveries.files']);
        $providers = $this->container->bound(ServiceProviderDirectory::class)
            ? $this->container->make(ServiceProviderDirectory::class)->providersOf([$contract->provider_user_id])
            : [];

        return view('marketplace::contracts.show', [
            'contract' => $contract,
            'project' => $contract->project,
            'catalog' => $this->catalog,
            'provider' => $providers[$contract->provider_user_id] ?? null,
            'isClient' => $contract->client_user_id === $user->getKey(),
            'payable' => $contract->payable(),
            'limits' => (array) $this->config->get('marketplace.contracts', []),
        ]);
    }

    public function accept(Request $request, string $uuid, AcceptBid $accept): RedirectResponse
    {
        $bid = MarketBid::query()->where('uuid', $uuid)->first() ?? throw new NotFoundHttpException;

        try {
            $contract = $accept->handle($this->user($request), $bid);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['bid' => $exception->getMessage()]);
        }

        return to_route('market.contracts.show', $contract->uuid)->with('status', 'قرارداد ساخته شد. برای شروع کار، پول مرحله اول را بپردازید تا در امانت فرابهداشت بماند.');
    }

    public function pay(Request $request, string $uuid, MilestoneCheckout $checkout): RedirectResponse
    {
        $user = $this->user($request);
        $milestone = $this->milestone($user, $uuid);
        $back = to_route('market.contracts.show', $milestone->contract->uuid);

        try {
            if (PaymentSource::requested($request) === PaymentSource::Wallet) {
                $checkout->payFromWallet($milestone, (int) $user->getKey());

                return $back->with('status', 'پول مرحله در امانت است و مجری خبر گرفت.');
            }

            $result = $checkout->startGateway($milestone, (int) $user->getKey(), $user->mobile ?? null);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return $back->withErrors(['contract' => PaymentGatewayUnavailable::USER_MESSAGE]);
        } catch (RuntimeException $exception) {
            return $back->withErrors(['contract' => $exception->getMessage()]);
        }

        return redirect()->away($result->redirectUrl);
    }

    /** زرین‌پال بدون نشست کاربر هم ممکن است برگردد؛ مسیر عمداً بیرون از auth است. */
    public function callback(Request $request, PaymentGateway $gateway, MilestoneCheckout $checkout): RedirectResponse
    {
        $milestone = MarketMilestone::query()->where('gateway_authority', (string) $request->query('Authority'))->firstOrFail();
        $back = to_route('market.contracts.show', $milestone->contract->uuid);

        if ($milestone->status !== MilestoneStatus::Unpaid) {
            return $back;
        }

        if ((string) $request->query('Status') !== 'OK') {
            return $back->withErrors(['contract' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $gateway->verify((string) $milestone->gateway_authority, $milestone->amount());

        if (! $verification->successful) {
            return $back->withErrors(['contract' => $verification->failureReason ?? 'پرداخت تأیید نشد.']);
        }

        try {
            $checkout->complete($milestone, $verification->referenceId ?? '');
        } catch (RuntimeException $exception) {
            report($exception);

            return $back->withErrors(['contract' => 'پرداخت انجام شد ولی ثبت نشد؛ با پشتیبانی تماس بگیرید تا مبلغ به کیف پولتان برگردد.']);
        }

        return $back->with('status', 'پول مرحله در امانت است و مجری خبر گرفت.');
    }

    public function deliver(Request $request, string $uuid, MilestoneFlow $flow): RedirectResponse
    {
        $limits = (array) $this->config->get('marketplace.contracts', []);
        $validated = $request->validate([
            'note' => ['required', 'string', 'min:'.($limits['note_min'] ?? 10), 'max:'.($limits['note_max'] ?? 3000)],
            'files' => ['nullable', 'array', 'max:'.($limits['files_max'] ?? 5)],
            'files.*' => ['file', 'max:'.($limits['file_max_kb'] ?? 20_480), 'mimes:'.implode(',', (array) $this->config->get('marketplace.projects.file_types', ['pdf']))],
        ]);
        $files = $request->file('files', []);
        $uploads = array_values(is_array($files) ? $files : [$files]);

        return $this->act($request, $uuid, static fn (MarketMilestone $milestone, int $userId) => $flow->deliver($milestone, $userId, $validated['note'], $uploads), 'تحویل ثبت شد و کارفرما خبر گرفت.');
    }

    public function approve(Request $request, string $uuid, MilestoneFlow $flow): RedirectResponse
    {
        return $this->act($request, $uuid, $flow->approve(...), 'مرحله تأیید شد و پولش به مجری رسید.');
    }

    public function revise(Request $request, string $uuid, MilestoneFlow $flow): RedirectResponse
    {
        $limits = (array) $this->config->get('marketplace.contracts', []);
        $validated = $request->validate([
            'revision_note' => ['required', 'string', 'min:'.($limits['note_min'] ?? 10), 'max:'.($limits['note_max'] ?? 3000)],
        ]);

        return $this->act($request, $uuid, static fn (MarketMilestone $milestone, int $userId) => $flow->requestRevision($milestone, $userId, $validated['revision_note']), 'درخواست اصلاح برای مجری فرستاده شد.');
    }

    public function file(Request $request, string $uuid): StreamedResponse
    {
        $file = DeliveryFile::query()->where('uuid', $uuid)->with('delivery.milestone.contract')->first();
        $user = $request->user();

        if ($file === null || ! $user instanceof User || ! $file->delivery->milestone->contract->involves((int) $user->getKey())) {
            throw new NotFoundHttpException('این فایل پیدا نشد.');
        }

        return $this->storage->disk('local')->download($file->path, $file->original_name);
    }

    /** @param  callable(MarketMilestone, int): MarketMilestone  $step */
    private function act(Request $request, string $uuid, callable $step, string $done): RedirectResponse
    {
        $user = $this->user($request);
        $milestone = $this->milestone($user, $uuid);
        $back = to_route('market.contracts.show', $milestone->contract->uuid);

        try {
            $step($milestone, (int) $user->getKey());
        } catch (RuntimeException $exception) {
            return $back->withInput()->withErrors(['contract' => $exception->getMessage()]);
        }

        return $back->with('status', $done);
    }

    private function contract(User $user, string $uuid): MarketContract
    {
        $contract = MarketContract::query()->where('uuid', $uuid)->first();

        if ($contract === null || ! $contract->involves((int) $user->getKey())) {
            throw new NotFoundHttpException('این قرارداد پیدا نشد.');
        }

        return $contract;
    }

    private function milestone(User $user, string $uuid): MarketMilestone
    {
        $milestone = MarketMilestone::query()->where('uuid', $uuid)->with('contract')->first();

        if ($milestone === null || ! $milestone->contract->involves((int) $user->getKey())) {
            throw new NotFoundHttpException('این مرحله پیدا نشد.');
        }

        return $milestone;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
