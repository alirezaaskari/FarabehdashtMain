<?php

declare(strict_types=1);

namespace App\Support\Notifications;

/**
 * یک اعلان برای یک کاربر، همان‌طور که رویداد منتشرکننده آن را می‌سازد.
 *
 * مقصد به‌صورت نام مسیر ذخیره می‌شود، نه نشانی کامل: اگر ماژول صاحب مسیر
 * بعداً خاموش شود، اعلان خوانا می‌ماند و فقط پیوندش پنهان می‌شود.
 *
 * `kind` کلید پایدار نوع اعلان است (مثل `commerce.product_published`) — همان
 * قرارداد نام‌گذاری دفتر رویداد.
 */
final readonly class UserNotice
{
    /** @param  array<string, string|int>  $routeParameters */
    public function __construct(
        public int $recipientId,
        public string $kind,
        public string $title,
        public ?string $body = null,
        public ?string $routeName = null,
        public array $routeParameters = [],
    ) {}
}
