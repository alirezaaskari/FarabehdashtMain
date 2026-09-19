<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Sms;

use App\Contracts\SmsSender;

/**
 * درایور تست: پیامک‌ها را در حافظه نگه می‌دارد تا تست بتواند محتوایشان را ببیند.
 */
final class FakeSmsSender implements SmsSender
{
    /** @var list<array{mobile: string, message: string}> */
    private array $sent = [];

    public function send(string $mobile, string $message): void
    {
        $this->sent[] = ['mobile' => $mobile, 'message' => $message];
    }

    /** @return list<array{mobile: string, message: string}> */
    public function all(): array
    {
        return $this->sent;
    }

    public function lastMessageTo(string $mobile): ?string
    {
        foreach (array_reverse($this->sent) as $message) {
            if ($message['mobile'] === $mobile) {
                return $message['message'];
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->sent);
    }
}
