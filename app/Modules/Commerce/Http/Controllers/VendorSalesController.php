<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\OrderItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class VendorSalesController
{
    public function index(Request $request): View
    {
        $items = OrderItem::query()
            ->where('vendor_user_id', $request->user()->id)
            ->whereHas('order', fn ($query) => $query->whereIn('status', [
                OrderStatus::Paid->value,
                OrderStatus::PartiallyRefunded->value,
                OrderStatus::Refunded->value,
            ]))
            ->with(['product', 'order'])
            ->latest('id')
            ->limit(100)
            ->get();

        return view('commerce::vendor.sales', ['items' => $items]);
    }
}
