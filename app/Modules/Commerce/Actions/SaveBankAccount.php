<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Sheba;
use App\Modules\Commerce\Domain\VendorBankAccount;
use App\Modules\Commerce\Events\BankAccountSaved;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * ثبت یا عوض‌کردن حساب مقصد تسویه. درخواست بازی که پیش‌تر ثبت شده با همان
 * حساب قبلی می‌ماند؛ فقط درخواست‌های بعدی حساب تازه را برمی‌دارند.
 */
final readonly class SaveBankAccount
{
    public function __construct(private Dispatcher $events) {}

    public function handle(int $userId, Sheba $sheba, string $holderName): VendorBankAccount
    {
        $holderName = trim($holderName);

        if ($holderName === '' || mb_strlen($holderName) > 120) {
            throw new InvalidArgumentException('نام صاحب حساب را همان‌طور که در بانک ثبت است بنویسید.');
        }

        $account = VendorBankAccount::query()->updateOrCreate(
            ['user_id' => $userId],
            ['sheba' => $sheba->value, 'holder_name' => $holderName],
        );

        $this->events->dispatch(new BankAccountSaved($account));

        return $account;
    }
}
