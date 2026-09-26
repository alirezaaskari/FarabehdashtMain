<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\Enums\PackStatus;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Events\PackStatusChanged;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * انتشار و بایگانی بسته. بسته بدون سؤال منتشرشده منتشر نمی‌شود؛ بایگانی
 * فروش تازه را می‌بندد ولی خریداران همچنان تمرین و آزمون می‌دهند.
 */
final readonly class ChangePackStatus
{
    public function __construct(private Dispatcher $events) {}

    public function publish(ExamPack $pack, int $actorId): ExamPack
    {
        if ($pack->publishedQuestions()->doesntExist()) {
            throw new InvalidArgumentException('بسته هنوز سؤال منتشرشده‌ای ندارد؛ اول سؤال‌ها را وارد یا تأیید کنید.');
        }

        return $this->move($pack, PackStatus::Published, $actorId);
    }

    public function retire(ExamPack $pack, int $actorId): ExamPack
    {
        return $this->move($pack, PackStatus::Retired, $actorId);
    }

    private function move(ExamPack $pack, PackStatus $to, int $actorId): ExamPack
    {
        $before = $pack->status;

        if ($before === $to) {
            return $pack;
        }

        $pack->forceFill([
            'status' => $to,
            'published_at' => $to === PackStatus::Published ? ($pack->published_at ?? now()) : $pack->published_at,
        ])->save();

        $this->events->dispatch(new PackStatusChanged($pack, $before->value, $actorId));

        return $pack;
    }
}
