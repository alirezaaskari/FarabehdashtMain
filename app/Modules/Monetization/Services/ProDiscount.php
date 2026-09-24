<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Contracts\EntitlementGate;
use App\Contracts\SubscriberDiscount;
use App\Models\User;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * تخفیف پایدار فروشگاه برای مشترک.
 *
 * فقط دلیل `Subscribed` تخفیف می‌دهد، نه هر پاسخ مثبت دروازه: وقتی مدیر کلید
 * اشتراک را خاموش کند دروازه به همه اجازه می‌دهد، ولی تخفیف باید **حذف**
 * شود (جدول «اگر اشتراک Pro خاموش شود»). همین‌جا روشن می‌شود چرا پاسخ
 * دروازه دلیل دارد و بولین نیست.
 */
final readonly class ProDiscount implements SubscriberDiscount
{
    public function __construct(
        private EntitlementGate $gate,
        private Config $config,
    ) {}

    public function percentFor(User $user): float
    {
        if ($this->gate->decide($user, Feature::ShopDiscount)->reason !== EntitlementReason::Subscribed) {
            return 0.0;
        }

        return (float) $this->config->get('monetization.pro_discount_percent', 0);
    }
}
