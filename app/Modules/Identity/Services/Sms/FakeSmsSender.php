<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Sms;

use App\Contracts\SmsSender;
use App\Support\Sms\SmsMessage;

/**
 * درایور تست: پیامک‌ها را در حافظه نگه می‌دارد تا تست بتواند محتوایشان را ببیند.
 */
final class FakeSmsSender implements SmsSender
{
    /** @var list<array{mobile: string, message: SmsMessage}> */
    private array $sent = [];

    public function send(string $mobile, SmsMessage $message): void
    {
        $this->sent[] = ['mobile' => $mobile, 'message' => $message];
    }

    /** @return list<array{mobile: string, message: SmsMessage}> */
    public function all(): array
    {
        return $this->sent;
    }

    public function lastMessageTo(string $mobile): ?string
    {
        return $this->lastTo($mobile)?->text;
    }

    public function lastTo(string $mobile): ?SmsMessage
    {
        foreach (array_reverse($this->sent) as $entry) {
            if ($entry['mobile'] === $mobile) {
                return $entry['message'];
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->sent);
    }
}
