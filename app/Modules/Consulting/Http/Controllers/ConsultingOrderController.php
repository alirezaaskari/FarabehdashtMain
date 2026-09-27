<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Consulting\Actions\ConsultingCheckout;
use App\Modules\Consulting\Actions\ConsultingOrderFlow;
use App\Modules\Consulting\Actions\PostConsultingMessage;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Modules\Consulting\Domain\Enums\ServiceKind;
use App\Modules\Consulting\Services\ConsultantPresenter;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use App\Support\Regions\Regions;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * خرید خدمت و صفحه درخواست برای خریدار و مشاور.
 */
final readonly class ConsultingOrderController
{
    private const string PAID = 'پرداخت انجام شد و مبلغ تا پایان کار نزد فرابهداشت امانت می‌ماند. مشاور تا ۴۸ ساعت پاسخ می‌دهد.';

    public function __construct(
        private ConsultingCheckout $checkout,
        private ConsultingOrderFlow $flow,
        private PaymentGateway $gateway,
        private ConsultantPresenter $presenter,
        private Regions $regions,
        private Repository $config,
    ) {}

    public function create(string $uuid): View
    {
        $service = $this->onSale($uuid);

        return view('consulting::orders.create', [
            'service' => $service,
            'profile' => $service->profile,
            'open' => $this->checkout->isOpen(),
            'cities' => array_map(fn (string $city): array => [$city, (string) $this->regions->cityName($city)], $service->cities ?? []),
            'limits' => (array) $this->config->get('consulting.orders', []),
        ]);
    }

    public function store(Request $request, string $uuid): RedirectResponse
    {
        $service = $this->onSale($uuid);
        $user = $this->user($request);
        $limits = (array) $this->config->get('consulting.orders', []);

        $validated = $request->validate([
            'need' => ['required', 'string', 'min:'.($limits['need_min'] ?? 30), 'max:'.($limits['need_max'] ?? 3000)],
            'times' => ['required', 'array', 'min:2', 'max:3'],
            'times.*' => ['nullable', 'string', 'max:120'],
            'city' => [$service->kind === ServiceKind::Visit ? 'required' : 'nullable', Rule::in($service->cities ?? [])],
            'share_mobile' => ['nullable', 'boolean'],
        ]);

        $times = array_values(array_filter(array_map(static fn (?string $time): string => trim((string) $time), $validated['times'])));

        if (count($times) < 2) {
            return back()->withInput()->withErrors(['times' => 'دست‌کم دو زمان پیشنهادی بنویسید.']);
        }

        try {
            $order = $this->checkout->place($user, $service, $validated['need'], $times, $validated['city'] ?? null, (bool) ($validated['share_mobile'] ?? false));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['order' => $exception->getMessage()]);
        }

        if (PaymentSource::requested($request) === PaymentSource::Wallet) {
            try {
                $this->checkout->payFromWallet($order);
            } catch (InsufficientWalletBalance $exception) {
                $order->forceFill(['status' => OrderStatus::Failed])->save();

                return back()->withInput()->withErrors(['order' => $exception->getMessage()]);
            }

            return to_route('consulting.orders.show', $order->uuid)->with('status', self::PAID);
        }

        try {
            $result = $this->checkout->startGateway($order, $user->mobile ?? null);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['order' => PaymentGatewayUnavailable::USER_MESSAGE]);
        }

        return redirect()->away($result->redirectUrl);
    }

    /** زرین‌پال بدون نشست کاربر هم ممکن است برگردد؛ مسیر عمداً بیرون از auth است. */
    public function callback(Request $request): RedirectResponse
    {
        $order = ConsultingOrder::query()->where('gateway_authority', (string) $request->query('Authority'))->firstOrFail();
        $back = to_route('consulting.orders.show', $order->uuid);

        if ($order->status !== OrderStatus::AwaitingPayment) {
            return $order->status === OrderStatus::Failed ? $back->withErrors(['order' => 'پرداخت انجام نشد.']) : $back;
        }

        if ((string) $request->query('Status') !== 'OK') {
            $order->forceFill(['status' => OrderStatus::Failed])->save();

            return $back->withErrors(['order' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $order->gateway_authority, $order->price());

        if (! $verification->successful) {
            $order->forceFill(['status' => OrderStatus::Failed])->save();

            return $back->withErrors(['order' => $verification->failureReason ?? 'پرداخت تأیید نشد.']);
        }

        $this->checkout->complete($order, $verification->referenceId ?? '');

        return $back->with('status', self::PAID);
    }

    public function mine(Request $request): View
    {
        return view('consulting::orders.mine', [
            'orders' => ConsultingOrder::query()
                ->where('buyer_id', $this->user($request)->getKey())
                ->where('status', '!=', OrderStatus::AwaitingPayment)
                ->with('service.profile')
                ->latest()
                ->paginate((int) $this->config->get('consulting.orders.per_page', 20)),
        ]);
    }

    public function incoming(Request $request): View
    {
        return view('consulting::orders.incoming', [
            'orders' => ConsultingOrder::query()
                ->where('consultant_id', $this->user($request)->getKey())
                ->whereNotIn('status', [OrderStatus::AwaitingPayment, OrderStatus::Failed])
                ->with('service.profile')
                ->orderByRaw('status = ? desc', [OrderStatus::AwaitingConsultant->value])
                ->latest()
                ->paginate((int) $this->config->get('consulting.orders.per_page', 20)),
        ]);
    }

    public function show(Request $request, string $uuid): View
    {
        $user = $this->user($request);
        $order = $this->party($request, $uuid);
        $order->load(['service.profile', 'messages', 'buyer']);
        $isConsultant = $order->consultant_id === $user->getKey();

        return view('consulting::orders.show', [
            'order' => $order,
            'isConsultant' => $isConsultant,
            // DEC-55: شماره خریدار فقط با اجازه خودش و فقط به مشاور همین درخواست.
            'buyerMobile' => $isConsultant && $order->share_mobile && $order->status->isOpen() ? $order->buyer->mobile : null,
            'city' => $this->regions->cityName($order->city),
            'replyHours' => (int) $this->config->get('consulting.orders.reply_hours', 48),
            'releaseDays' => (int) $this->config->get('consulting.orders.auto_release_days', 7),
            'photo' => $this->presenter->photo($order->service->profile->photo_id),
        ]);
    }

    public function accept(Request $request, string $uuid): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_for' => ['required', 'string', 'max:120'],
            'meeting_link' => ['nullable', 'url:https', 'max:500'],
        ]);

        return $this->act($request, $uuid, fn (ConsultingOrder $order, int $userId) => $this->flow->accept($order, $userId, $validated['scheduled_for'], $validated['meeting_link'] ?? null), 'درخواست پذیرفته شد و خریدار خبردار شد.');
    }

    public function decline(Request $request, string $uuid): RedirectResponse
    {
        return $this->act($request, $uuid, fn (ConsultingOrder $order, int $userId) => $this->flow->decline($order, $userId, (string) $request->input('reason')), 'درخواست رد شد و کل مبلغ به کیف پول خریدار برگشت.');
    }

    public function deliver(Request $request, string $uuid): RedirectResponse
    {
        return $this->act($request, $uuid, fn (ConsultingOrder $order, int $userId) => $this->flow->deliver($order, $userId), 'اعلام شد. پس از تأیید خریدار یا پایان مهلت، سهم شما آزاد می‌شود.');
    }

    public function confirm(Request $request, string $uuid): RedirectResponse
    {
        return $this->act($request, $uuid, fn (ConsultingOrder $order, int $userId) => $this->flow->confirm($order, $userId), 'تأیید شد. ممنون که بازخورد دادید.');
    }

    public function dispute(Request $request, string $uuid): RedirectResponse
    {
        return $this->act($request, $uuid, fn (ConsultingOrder $order, int $userId) => $this->flow->dispute($order, $userId, (string) $request->input('reason')), 'اعتراض ثبت شد. مبلغ تا رأی مدیر در امانت می‌ماند.');
    }

    public function message(Request $request, string $uuid, PostConsultingMessage $post): RedirectResponse
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:'.(int) $this->config->get('consulting.orders.message_max', 2000)]]);

        return $this->act($request, $uuid, static fn (ConsultingOrder $order, int $userId) => $post->handle($order, $userId, $validated['body']), 'پیام فرستاده شد.');
    }

    /** @param  callable(ConsultingOrder, int): mixed  $step */
    private function act(Request $request, string $uuid, callable $step, string $done): RedirectResponse
    {
        $order = $this->party($request, $uuid);

        try {
            $step($order, (int) $this->user($request)->getKey());
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['order' => $exception->getMessage()]);
        }

        return to_route('consulting.orders.show', $order->uuid)->with('status', $done);
    }

    private function party(Request $request, string $uuid): ConsultingOrder
    {
        $order = ConsultingOrder::query()->where('uuid', $uuid)->with('service')->first();

        if ($order === null || ! $order->isParty((int) $this->user($request)->getKey())) {
            throw new NotFoundHttpException('این درخواست پیدا نشد.');
        }

        return $order;
    }

    private function onSale(string $uuid): ConsultingService
    {
        $service = ConsultingService::query()->where('uuid', $uuid)->with('profile')->first();

        if ($service === null || ! $service->isOnSale()) {
            throw new NotFoundHttpException('این خدمت پیدا نشد.');
        }

        return $service;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
