<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Commerce\Domain\Product;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * محصولی منتشر شد و از این لحظه قابل خرید است.
 */
final readonly class ProductPublished implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public Product $product,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.product_published',
            subjectType: Product::class,
            subjectId: $this->product->uuid,
            actorId: $this->actorId,
            after: [
                'status' => $this->product->status->value,
                'price_toman' => $this->product->price_toman,
            ],
            context: ['slug' => $this->product->slug, 'vendor_user_id' => $this->product->vendor_user_id],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->product->vendor_user_id,
            kind: 'commerce.product_published',
            title: sprintf('محصول «%s» منتشر شد', $this->product->title),
            routeName: 'commerce.show',
            routeParameters: ['product' => $this->product->slug],
        )];
    }
}
