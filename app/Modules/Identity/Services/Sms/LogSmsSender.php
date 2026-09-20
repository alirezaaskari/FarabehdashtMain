<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Sms;

use App\Contracts\SmsSender;
use Psr\Log\LoggerInterface;

/**
 * درایور محلی: پیامک را در لاگ می‌نویسد.
 * برای توسعه و محیط آزمایشی، تا بدون سرویس واقعی بتوان جریان ورود را دید.
 *
 * سطح ثبت عمداً `warning` است، نه `info`. دو دلیل:
 *
 * ۱. این وضعیت واقعاً غیرعادی است — یعنی رمز یک‌بارمصرف کاربر به‌جای پیامک،
 *    در فایل لاگ نشسته و هیچ پیامکی ارسال نشده.
 * ۲. تنها کار این درایور دیده‌شدن پیام است. با `info`، هر محیطی که
 *    `LOG_LEVEL=warning` داشته باشد آن را بی‌صدا دور می‌ریزد و کاربر هیچ راهی
 *    برای ورود ندارد — دقیقاً همان اتفاقی که در اولین استقرار افتاد.
 */
final readonly class LogSmsSender implements SmsSender
{
    public function __construct(private LoggerInterface $logger) {}

    public function send(string $mobile, string $message): void
    {
        $this->logger->warning('پیامک در لاگ نوشته شد و ارسال نشد (درایور log)', [
            'mobile' => $mobile,
            'message' => $message,
        ]);
    }
}
