<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Actions\CancelPayout;
use App\Modules\Commerce\Actions\RequestPayout;
use App\Modules\Commerce\Actions\SaveBankAccount;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Modules\Commerce\Domain\Sheba;
use App\Modules\Commerce\Domain\VendorBankAccount;
use App\Modules\Commerce\Services\Payouts;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * تسویه در میزکار فروشنده و مدرس (بخش ۱۸-۶): مانده، حساب مقصد، درخواست
 * تسویه و سابقه درخواست‌ها. واریز واقعی کار دستی مدیر مالی است.
 */
final readonly class VendorSettlementController
{
    public function __construct(private Payouts $payouts) {}

    public function index(Request $request): View
    {
        $userId = (int) $request->user()->id;
        $account = VendorBankAccount::query()->where('user_id', $userId)->first();

        return view('commerce::vendor.settlement', [
            'owed' => $this->payouts->owed($userId),
            'minimum' => $this->payouts->minimum(),
            'account' => $account,
            'sheba' => $account?->sheba(),
            'open' => $this->payouts->openRequest($userId),
            'canRequest' => $account !== null && $this->payouts->canRequest($userId),
            'history' => PayoutRequest::query()->where('user_id', $userId)->latest('id')->limit(20)->get(),
        ]);
    }

    public function saveAccount(Request $request, SaveBankAccount $save): RedirectResponse
    {
        $data = $request->validate([
            'sheba' => ['required', 'string', 'max:40'],
            'holder_name' => ['required', 'string', 'max:120'],
        ]);

        try {
            $save->handle((int) $request->user()->id, Sheba::fromInput($data['sheba']), $data['holder_name']);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['sheba' => $exception->getMessage()]);
        }

        return redirect()->route('commerce.vendor.settlement')->with('status', 'حساب مقصد تسویه ثبت شد.');
    }

    public function request(Request $request, RequestPayout $requestPayout): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'string', 'max:20']]);

        try {
            $requestPayout->handle((int) $request->user()->id, Money::fromInput($data['amount']));
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('commerce.vendor.settlement')
            ->with('status', 'درخواست تسویه ثبت شد. واریز هفته‌ای یک بار انجام می‌شود و خبرش را در اعلان‌ها می‌بینید.');
    }

    public function cancel(Request $request, string $uuid, CancelPayout $cancel): RedirectResponse
    {
        $payout = PayoutRequest::query()
            ->where('uuid', $uuid)
            ->where('user_id', (int) $request->user()->id)
            ->firstOrFail();

        try {
            $cancel->handle($payout);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['payout' => $exception->getMessage()]);
        }

        return redirect()->route('commerce.vendor.settlement')->with('status', 'درخواست تسویه لغو شد.');
    }
}
