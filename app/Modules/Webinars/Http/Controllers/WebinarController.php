<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Http\Controllers;

use App\Contracts\SalesSwitch;
use App\Modules\Webinars\Domain\Enums\WebinarStatus;
use App\Modules\Webinars\Domain\Webinar;
use App\Modules\Webinars\Services\Seats;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class WebinarController
{
    public function __construct(
        private Seats $seats,
        private SalesSwitch $sales,
    ) {}

    public function index(): View
    {
        $webinars = Webinar::query()->published()->orderBy('starts_at')->get();

        return view('webinars::index', [
            'upcoming' => $webinars->reject(static fn (Webinar $w): bool => $w->hasEnded())->values(),
            'past' => $webinars->filter(static fn (Webinar $w): bool => $w->hasEnded())->sortByDesc('starts_at')->take(12)->values(),
        ]);
    }

    public function show(Request $request, Webinar $webinar): View
    {
        abort_if($webinar->status === WebinarStatus::Draft, 404);

        $userId = (int) $request->user()?->getKey();
        $registered = $userId > 0 && $this->seats->isRegistered($webinar, $userId);

        return view('webinars::show', [
            'webinar' => $webinar,
            'registered' => $registered,
            'remaining' => $this->seats->remaining($webinar, $userId > 0 ? $userId : null),
            'paidOpen' => $webinar->isFree() || $this->sales->isOpen(SalesSwitch::EVENT_WEBINAR),
        ]);
    }

    /**
     * پیوند جلسه هرگز در HTML نمی‌آید؛ این مسیر پس از بررسی ثبت‌نام و زمان
     * به سرویس برگزاری هدایت می‌کند، بدون ارجاع‌دهنده و بدون داده کاربر.
     */
    public function join(Request $request, Webinar $webinar): RedirectResponse
    {
        $back = redirect()->route('webinars.show', $webinar->slug);

        if (! $webinar->isPublished() || ! $this->seats->isRegistered($webinar, (int) $request->user()?->getKey())) {
            return $back->withErrors(['join' => 'پیوند ورود فقط برای ثبت‌نام‌شده‌های این رویداد است.']);
        }

        if (! $webinar->isJoinOpen()) {
            return $back->withErrors(['join' => $webinar->hasEnded()
                ? 'این رویداد به پایان رسیده است.'
                : 'پیوند ورود از یک ساعت پیش از شروع باز می‌شود.']);
        }

        return redirect()->away($webinar->join_url)->header('Referrer-Policy', 'no-referrer');
    }
}
