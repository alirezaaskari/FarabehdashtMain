<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Sms;

use App\Contracts\SmsSender;
use Psr\Log\LoggerInterface;

/**
 * درایور محلی: پیامک را در لاگ می‌نویسد.
 * برای توسعه و محیط آزمایشی، تا بدون سرویس واقعی بتوان جریان ورود را دید.
 */
final readonly class LogSmsSender implements SmsSender
{
    public function __construct(private LoggerInterface $logger) {}

    public function send(string $mobile, string $message): void
    {
        $this->logger->info('پیامک (درایور log)', [
            'mobile' => $mobile,
            'message' => $message,
        ]);
    }
}
