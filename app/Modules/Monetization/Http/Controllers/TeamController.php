<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Monetization\Actions\ManageTeamMembers;
use App\Modules\Monetization\Actions\TeamCheckout;
use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\TeamInvitation;
use App\Modules\Monetization\Domain\TeamPeriod;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Modules\Monetization\Services\StreamRegistry;
use App\Modules\Monetization\Services\TeamAccess;
use App\Modules\Monetization\Services\TeamPricing;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * خرید تیم و صفحه تیم در میزکار (بخش ۱۹-۶).
 *
 * صفحه خرید قیمت را با GET همان فرم حساب می‌کند تا بی‌اسکریپت کار کند و
 * کاربر پیش از پرداخت مبلغ دقیق را ببیند.
 */
final readonly class TeamController
{
    public function __construct(
        private TeamPricing $pricing,
        private TeamAccess $access,
        private TeamCheckout $checkout,
        private ManageTeamMembers $members,
        private StreamRegistry $streams,
        private WalletCheckout $wallet,
        private PaymentGateway $gateway,
        private Repository $config,
    ) {}

    public function buy(Request $request): View|RedirectResponse
    {
        if (! $this->streams->isEnabled(RevenueStream::TeamSeat)) {
            return to_route('monetization.plans');
        }

        $team = $this->access->owned($this->user($request));
        $floor = max($this->pricing->minSeats(), $team !== null && $team->isCurrent() ? $team->usedSeats() : 0);
        $seats = min($this->pricing->maxSeats(), max($floor, (int) $request->query('seats', (string) max($floor, $team->seat_count ?? 0))));
        $cycle = BillingCycle::tryFrom((string) $request->query('cycle')) ?? BillingCycle::Monthly;

        return view('monetization::team.buy', [
            'team' => $team,
            'seats' => $seats,
            'floor' => $floor,
            'max' => $this->pricing->maxSeats(),
            'cycle' => $cycle,
            'cycles' => BillingCycle::cases(),
            'name' => (string) ($request->query('name') ?: $team->name ?? ''),
            'unit' => $this->pricing->unitPrice($seats),
            'total' => $this->pricing->total($seats, $cycle),
            'billedMonths' => $this->pricing->billedMonths($cycle),
            'tiers' => $this->pricing->tiers(),
        ]);
    }

    public function store(Request $request): View|RedirectResponse
    {
        if (! $this->streams->isEnabled(RevenueStream::TeamSeat)) {
            throw new NotFoundHttpException;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:'.(int) $this->config->get('monetization.team.name_max', 80)],
            'seats' => ['required', 'integer', 'min:'.$this->pricing->minSeats(), 'max:'.$this->pricing->maxSeats()],
            'cycle' => ['required', Rule::enum(BillingCycle::class)],
        ]);
        $user = $this->user($request);
        $source = PaymentSource::requested($request);
        $cycle = BillingCycle::from($validated['cycle']);
        $seats = (int) $validated['seats'];

        if ($source === PaymentSource::Wallet && ! $this->wallet->canPay((int) $user->getKey(), $this->pricing->total($seats, $cycle))) {
            return back()->withInput()->withErrors(['payment' => 'موجودی کیف پول برای این مبلغ کافی نیست.']);
        }

        try {
            $period = $this->checkout->open($user, trim($validated['name']), $seats, $cycle);

            if ($source === PaymentSource::Wallet) {
                return view('monetization::team.paid', ['period' => $this->checkout->payFromWallet($user, $period)->refresh()]);
            }

            return redirect()->away($this->checkout->startGateway($period, $user->mobile)->redirectUrl);
        } catch (InsufficientWalletBalance $exception) {
            return back()->withInput()->withErrors(['payment' => $exception->getMessage()]);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return view('monetization::checkout-failed', ['reason' => PaymentGatewayUnavailable::USER_MESSAGE]);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['seats' => $exception->getMessage()]);
        }
    }

    public function callback(Request $request): View
    {
        $period = TeamPeriod::query()->where('gateway_authority', (string) $request->query('Authority'))->firstOrFail();

        if ($period->status === PeriodStatus::Paid) {
            return view('monetization::team.paid', ['period' => $period]);
        }

        if ((string) $request->query('Status') !== 'OK') {
            $period->forceFill(['status' => PeriodStatus::Failed])->save();

            return view('monetization::checkout-failed', ['reason' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $period->gateway_authority, $period->price());

        if (! $verification->successful) {
            $period->forceFill(['status' => PeriodStatus::Failed])->save();

            return view('monetization::checkout-failed', ['reason' => $verification->failureReason]);
        }

        return view('monetization::team.paid', ['period' => $this->checkout->complete($period, $verification->referenceId ?? '')->refresh()]);
    }

    public function show(Request $request): View
    {
        $user = $this->user($request);
        $team = $this->access->owned($user);

        return view('monetization::team.show', [
            'team' => $team,
            'members' => $team === null ? collect() : $team->seats()->active()->with('member')->oldest('granted_at')->get(),
            'invitations' => $team === null ? collect() : $team->invitations()->pending()->latest()->get(),
            'seat' => $this->access->seat($user),
            'received' => TeamInvitation::query()->pending()->where('mobile', $user->mobile)->with('team.owner')->latest()->get(),
            'canBuy' => $this->streams->isEnabled(RevenueStream::TeamSeat),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $validated = $request->validate(['mobile' => ['required', 'string', 'max:20']]);
        $user = $this->user($request);

        return $this->attempt(fn () => $this->members->invite($user, $this->access->owned($user) ?? throw new NotFoundHttpException, $validated['mobile']), 'دعوت ثبت شد. صاحب شماره در صفحه «تیم» خود آن را می‌بیند.', 'mobile');
    }

    public function cancel(Request $request, string $uuid): RedirectResponse
    {
        return $this->attempt(fn () => $this->members->cancel($this->user($request), $this->invitation($uuid)), 'دعوت لغو شد و صندلی‌اش آزاد است.');
    }

    public function accept(Request $request, string $uuid): RedirectResponse
    {
        return $this->attempt(fn () => $this->members->accept($this->user($request), $this->invitation($uuid)), 'به تیم پیوستید؛ امکانات حرفه‌ای برای شما باز است.');
    }

    public function decline(Request $request, string $uuid): RedirectResponse
    {
        return $this->attempt(fn () => $this->members->decline($this->user($request), $this->invitation($uuid)), 'دعوت رد شد.');
    }

    public function remove(Request $request, int $seat): RedirectResponse
    {
        $user = $this->user($request);
        $team = $this->access->owned($user) ?? throw new NotFoundHttpException;
        $record = TeamSeat::query()->whereKey($seat)->first() ?? throw new NotFoundHttpException;

        return $this->attempt(fn () => $this->members->remove($user, $team, $record), 'صندلی پس گرفته شد. داده آن عضو مال خودش می‌ماند.');
    }

    public function leave(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $seat = $this->access->seat($user) ?? throw new NotFoundHttpException;

        return $this->attempt(fn () => $this->members->leave($user, $seat), 'از تیم خارج شدید.');
    }

    private function attempt(callable $action, string $status, string $errorKey = 'team'): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors([$errorKey => $exception->getMessage()]);
        }

        return to_route('monetization.team')->with('status', $status);
    }

    private function invitation(string $uuid): TeamInvitation
    {
        return TeamInvitation::query()->where('uuid', $uuid)->with('team')->first() ?? throw new NotFoundHttpException;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
