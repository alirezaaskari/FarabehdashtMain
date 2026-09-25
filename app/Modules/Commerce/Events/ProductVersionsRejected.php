<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Commerce\Domain\Product;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** نسخه‌های تازه یک محصول منتشرشده رد شد؛ خریداران همان نسخه قبلی را می‌گیرند. */
final readonly class ProductVersionsRejected implements AuditableEvent, UserNotifiableEvent
{
    /** @param  list<string>  $versions */
    public function __construct(
        public Product $product,
        public int $actorId,
        public string $note,
        public array $versions,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.product_versions_rejected',
            subjectType: Product::class,
            subjectId: $this->product->uuid,
            actorId: $this->actorId,
            after: ['versions' => $this->versions],
            context: [
                'slug' => $this->product->slug,
                'vendor_user_id' => $this->product->vendor_user_id,
                'note' => $this->note,
            ],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->product->vendor_user_id,
            kind: 'commerce.product_versions_rejected',
            title: sprintf('نسخه تازه «%s» تأیید نشد', $this->product->title),
            body: $this->note,
            routeName: 'commerce.vendor.products.edit',
            routeParameters: ['product' => $this->product->getKey()],
        )];
    }
}
