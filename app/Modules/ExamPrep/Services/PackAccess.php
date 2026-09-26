<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Services;

use App\Contracts\SalesSwitch;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PackPurchase;

/**
 * چه کسی به چه چیزی از یک بسته دسترسی دارد.
 *
 * نمونه رایگان برای هر کاربر واردشده؛ تمرین و آزمون فقط برای خریدار. کلید
 * «بسته‌های آزمون» فقط خرید تازه را می‌بندد، نه دسترسی خریداران قبلی.
 */
final readonly class PackAccess
{
    public function __construct(private SalesSwitch $switch) {}

    public function isOnSale(ExamPack $pack): bool
    {
        return $pack->isPublished() && $this->switch->isOpen(SalesSwitch::EXAM_PACK);
    }

    public function owns(int $userId, ExamPack $pack): bool
    {
        return PackPurchase::query()
            ->paid()
            ->where('exam_pack_id', $pack->id)
            ->where('user_id', $userId)
            ->exists();
    }
}
