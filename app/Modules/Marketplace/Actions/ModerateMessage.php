<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Modules\Marketplace\Domain\Enums\MessageStatus;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Modules\Marketplace\Domain\MarketStrike;
use App\Modules\Marketplace\Events\MessageModerated;
use App\Modules\Marketplace\Services\StrikeBook;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * تصمیم مدیر درباره پیام نگه‌داشته. رد یعنی یک اخطار برای فرستنده؛ با رسیدن
 * اخطارها به سقف، پیشنهاد و تعریف پروژه‌اش بسته می‌شود (DEC-80).
 */
final readonly class ModerateMessage
{
    public function __construct(
        private StrikeBook $strikes,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function approve(MarketMessage $message, int $adminId): MarketMessage
    {
        return $this->decide($message, $adminId, approved: true);
    }

    public function reject(MarketMessage $message, int $adminId): MarketMessage
    {
        return $this->decide($message, $adminId, approved: false);
    }

    private function decide(MarketMessage $message, int $adminId, bool $approved): MarketMessage
    {
        $blocked = $this->db->transaction(function () use ($message, $adminId, $approved): bool {
            $locked = MarketMessage::query()->whereKey($message->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== MessageStatus::Held) {
                throw new RuntimeException('درباره این پیام پیش‌تر تصمیم گرفته شده است.');
            }

            $message->forceFill([
                'status' => $approved ? MessageStatus::Delivered : MessageStatus::Rejected,
                'reviewed_by' => $adminId,
                'reviewed_at' => Carbon::now(),
            ])->save();

            if ($approved) {
                return false;
            }

            $wasBlocked = $this->strikes->isBlocked($message->sender_user_id);
            MarketStrike::query()->create(['user_id' => $message->sender_user_id, 'message_id' => $message->id]);

            return ! $wasBlocked && $this->strikes->isBlocked($message->sender_user_id);
        });

        $this->events->dispatch(new MessageModerated($message, $adminId, $approved, $blocked));

        return $message;
    }
}
