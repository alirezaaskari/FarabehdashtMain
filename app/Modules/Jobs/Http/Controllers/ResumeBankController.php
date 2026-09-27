<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Models\User;
use App\Modules\Jobs\Actions\ManageBankMembership;
use App\Modules\Jobs\Actions\ResumeBankRequests;
use App\Modules\Jobs\Domain\BankRequest;
use App\Modules\Jobs\Domain\Passport;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Jobs\Services\ResumeBank;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * بانک رزومه از نگاه کارجو (۲۰-۵): پیوستن یا بیرون آمدن، کارت ناشناسی که
 * کارفرما می‌بیند، و پاسخ به درخواست‌های تماس.
 */
final readonly class ResumeBankController
{
    public function __construct(
        private ManageBankMembership $membership,
        private ResumeBankRequests $requests,
    ) {}

    public function index(Request $request, ResumeBank $bank, JobPricing $pricing): View
    {
        $userId = (int) $this->user($request)->getKey();
        $passport = Passport::of($userId);

        return view('jobs::bank.member', [
            'passport' => $passport,
            'card' => $bank->card($passport),
            'requests' => BankRequest::query()->where('jobseeker_id', $userId)->with('company')->latest('id')->get(),
            'replyDays' => $pricing->bankReplyDays(),
        ]);
    }

    public function join(Request $request): RedirectResponse
    {
        try {
            $this->membership->join((int) $this->user($request)->getKey());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['bank' => $exception->getMessage()]);
        }

        return to_route('jobs.bank.index')->with('status', 'به بانک رزومه پیوستید. کارفرماها فقط کارت ناشناس شما را می‌بینند.');
    }

    public function leave(Request $request): RedirectResponse
    {
        $this->membership->leave((int) $this->user($request)->getKey());

        return to_route('jobs.bank.index')->with('status', 'از بانک رزومه بیرون آمدید و درخواست‌های بی‌پاسخ رد شدند.');
    }

    public function accept(Request $request, string $uuid): RedirectResponse
    {
        return $this->answer($request, $uuid, true);
    }

    public function decline(Request $request, string $uuid): RedirectResponse
    {
        return $this->answer($request, $uuid, false);
    }

    private function answer(Request $request, string $uuid, bool $accept): RedirectResponse
    {
        $userId = (int) $this->user($request)->getKey();
        $bankRequest = BankRequest::query()->where('uuid', $uuid)->where('jobseeker_id', $userId)->with('company')->first()
            ?? throw new NotFoundHttpException('این درخواست پیدا نشد.');

        try {
            $accept ? $this->requests->accept($userId, $bankRequest) : $this->requests->decline($userId, $bankRequest);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['bank' => $exception->getMessage()]);
        }

        return to_route('jobs.bank.index')->with('status', $accept
            ? '«'.$bankRequest->company->name.'» حالا نام و شماره شما را می‌بیند.'
            : 'درخواست رد شد و «'.$bankRequest->company->name.'» چیزی از شما نمی‌بیند.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
