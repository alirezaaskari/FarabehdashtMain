<?php

declare(strict_types=1);

namespace App\Support\Ledger;

use App\Models\User;

/**
 * اشاره به یک حساب دفتر کل، بدون افشای مدل داخلی ماژول Ledger.
 *
 * هر ماژولی که تراکنش مالی ثبت می‌کند باید بگوید هر ردیف به کدام حساب
 * می‌خورد؛ ولی طبق قاعده ۱ حق ندارد `LedgerAccount` (مدل ماژول Ledger) را
 * import کند. این DTO همان مرز است: ماژول Ledger با (type, ownerType,
 * ownerId) حساب را پیدا یا می‌سازد.
 *
 * حساب‌های تک‌نمونه‌ای (خزانه، درآمد پلتفرم…) مالک ندارند؛ `owner*` را null
 * بگذارید.
 */
final readonly class LedgerAccountRef
{
    public function __construct(
        public AccountType $type,
        public ?string $ownerType = null,
        public ?int $ownerId = null,
    ) {}

    /** کیف پول یک کاربر — پرکاربردترین حالت، تا فراخوان مجبور به دانستن نام کلاس مالک نباشد. */
    public static function wallet(int $userId): self
    {
        return new self(AccountType::UserWallet, User::class, $userId);
    }

    /** بدهی پلتفرم به یک فروشنده/مدرس مشخص — هویت مالی مشترک آن‌ها (ADR-0003). */
    public static function vendorPayable(int $vendorUserId): self
    {
        return new self(AccountType::VendorPayable, User::class, $vendorUserId);
    }
}
