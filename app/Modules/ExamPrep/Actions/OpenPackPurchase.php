<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\Enums\PurchaseStatus;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Modules\ExamPrep\Services\PackAccess;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ردیف خرید در انتظار پرداخت، با قیمت همین لحظه. هر کاربر برای هر بسته یک
 * ردیف دارد که پس از لغو یا شکست دوباره به کار می‌رود.
 */
final readonly class OpenPackPurchase
{
    public function __construct(private PackAccess $access) {}

    public function handle(ExamPack $pack, int $userId): PackPurchase
    {
        if (! $this->access->isOnSale($pack)) {
            throw new InvalidArgumentException('فروش این بسته در حال حاضر فعال نیست.');
        }

        if ($this->access->owns($userId, $pack)) {
            throw new InvalidArgumentException('این بسته را پیش‌تر خریده‌اید.');
        }

        $snapshot = [
            'status' => PurchaseStatus::Pending,
            'price_toman' => $pack->price_toman,
            'gateway_authority' => null,
        ];

        $existing = PackPurchase::query()->where('exam_pack_id', $pack->id)->where('user_id', $userId)->first();

        if ($existing !== null) {
            $existing->forceFill($snapshot)->save();

            return $existing;
        }

        return PackPurchase::query()->create([
            'uuid' => (string) Str::uuid7(),
            'exam_pack_id' => $pack->id,
            'user_id' => $userId,
            ...$snapshot,
        ]);
    }
}
