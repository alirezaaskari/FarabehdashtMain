<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Commerce\Domain\Product;
use App\Support\Audit\AuditEntry;

/**
 * مدیر یک محصول را رد کرد.
 *
 * یادداشت مدیر بخشی از ردیف رویداد است — فروشنده باید دقیقاً بداند چرا رد شده.
 */
final readonly class ProductRejected implements AuditableEvent
{
    public function __construct(
        public Product $product,
        public int $actorId,
        public string $note,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.product_rejected',
            subjectType: Product::class,
            subjectId: $this->product->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->product->status->value],
            context: [
                'slug' => $this->product->slug,
                'vendor_user_id' => $this->product->vendor_user_id,
                'note' => $this->note,
            ],
        );
    }
}
