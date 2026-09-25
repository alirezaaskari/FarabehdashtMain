<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Support\Ledger\AccountType;
use App\Support\Ledger\LedgerAccountRef;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * پول یک خرید از کجا آمد.
 *
 * درگاه یعنی پول از بیرون سیستم وارد شد، پس طرف بدهکار خزانه است (DEC-20).
 * کیف پول یعنی پول از قبل در سیستم بود و فقط از کیف پول خریدار کم می‌شود
 * (DEC-37). رایگان یعنی هیچ پولی جابه‌جا نشد و تراکنشی در دفتر کل ثبت نمی‌شود
 * (DEC-38).
 */
enum PaymentSource: string
{
    case Gateway = 'gateway';
    case Wallet = 'wallet';
    case Free = 'free';

    /**
     * روشی که کاربر در فرم پرداخت انتخاب کرد؛ بدون انتخاب، درگاه.
     * «رایگان» انتخاب کاربر نیست و از فرم پذیرفته نمی‌شود.
     */
    public static function requested(Request $request): self
    {
        $data = $request->validate([
            'payment' => ['sometimes', Rule::in([self::Gateway->value, self::Wallet->value])],
        ]);

        return self::from($data['payment'] ?? self::Gateway->value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Gateway => 'درگاه بانکی',
            self::Wallet => 'کیف پول',
            self::Free => 'رایگان',
        };
    }

    /** حسابی که مبلغ خرید از آن بدهکار می‌شود. */
    public function debitAccount(int $payerUserId): LedgerAccountRef
    {
        return match ($this) {
            self::Gateway => new LedgerAccountRef(AccountType::Treasury),
            self::Wallet => LedgerAccountRef::wallet($payerUserId),
            self::Free => throw new LogicException('خرید رایگان در دفتر کل ثبت نمی‌شود.'),
        };
    }
}
