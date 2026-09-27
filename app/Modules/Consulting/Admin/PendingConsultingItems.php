<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Modules\Consulting\Domain\Enums\ServiceStatus;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * خدمت‌های در انتظار تأیید و اعتراض‌های خریدار، برای صف یکپارچه داشبورد پنل.
 * اعتراض کار مدیر بازگشت وجه است، نه مدیر محتوا.
 */
final readonly class PendingConsultingItems implements ApprovalQueueSource
{
    public const REVIEW_ABILITY = 'admin.content.review';

    public const DISPUTE_ABILITY = 'admin.refund.issue';

    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $services = Route::has('filament.fbh.pages.consulting-services') ? route('filament.fbh.pages.consulting-services') : url('/');
        $disputes = Route::has('filament.fbh.pages.consulting-disputes') ? route('filament.fbh.pages.consulting-disputes') : url('/');

        foreach (ConsultingService::query()->where('status', ServiceStatus::Pending)->oldest('submitted_at')->cursor() as $service) {
            yield new PendingItem(
                ability: self::REVIEW_ABILITY,
                kind: 'consulting_service',
                title: 'خدمت مشاوره — '.Str::limit($service->title, 80),
                url: $services,
                waitingSince: $service->submitted_at ?? $service->updated_at,
            );
        }

        foreach (ConsultingOrder::query()->where('status', OrderStatus::Disputed)->with('service')->oldest('disputed_at')->cursor() as $order) {
            yield new PendingItem(
                ability: self::DISPUTE_ABILITY,
                kind: 'consulting_dispute',
                title: 'اعتراض خدمت — '.Str::limit($order->service->title, 80),
                url: $disputes,
                waitingSince: $order->disputed_at ?? $order->updated_at,
            );
        }
    }
}
