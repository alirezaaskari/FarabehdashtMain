<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Services;

use Illuminate\Contracts\Session\Session;

/**
 * سبد خرید — فقط شناسه محصول در Session، بدون جدول اختصاصی.
 *
 * محصول دیجیتال تعداد ندارد (یک بار خرید، دسترسی دائمی)، پس سبد چیزی بیش
 * از یک مجموعه شناسه نیست. اگر روزی سبد باید بین دستگاه‌های کاربر مشترک
 * شود، همین‌جا به جدول دیتابیس تبدیل می‌شود — تا آن روز، این پیچیدگی را
 * ندارد.
 */
final readonly class Cart
{
    private const string SESSION_KEY = 'commerce.cart';

    public function __construct(private Session $session) {}

    /** @return list<int> */
    public function productIds(): array
    {
        /** @var list<int> $ids */
        $ids = $this->session->get(self::SESSION_KEY, []);

        return array_values(array_unique($ids));
    }

    public function add(int $productId): void
    {
        $this->session->put(self::SESSION_KEY, [...$this->productIds(), $productId]);
    }

    public function remove(int $productId): void
    {
        $this->session->put(
            self::SESSION_KEY,
            array_values(array_diff($this->productIds(), [$productId])),
        );
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->productIds() === [];
    }
}
