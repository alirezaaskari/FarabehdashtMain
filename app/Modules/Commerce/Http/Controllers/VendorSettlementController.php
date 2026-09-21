<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Contracts\LedgerBalanceReader;
use App\Support\Ledger\LedgerAccountRef;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * فقط نمایش موجودی — درخواست تسویه فعلاً از راه پشتیبانی است، نه یک دکمه در
 * همین صفحه؛ فرایند رسمی «درخواست تسویه» منتظر DEC-17 است (حداقل/حداکثر
 * مبلغ و بازه زمانی).
 */
final readonly class VendorSettlementController
{
    public function __construct(private LedgerBalanceReader $balances) {}

    public function index(Request $request): View
    {
        $owed = $this->balances->balanceOf(LedgerAccountRef::vendorPayable((int) $request->user()->id));

        return view('commerce::vendor.settlement', ['owed' => $owed]);
    }
}
