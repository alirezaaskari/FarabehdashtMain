<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use RuntimeException;

/**
 * خطاهای جریان ورود.
 *
 * پیام‌ها فارسی و قابل‌نمایش به کاربرند و قاعده لحن پروژه را رعایت می‌کنند:
 * چه چیزی و چرا، بدون سرزنش، با قدم بعدی.
 */
final class OtpException extends RuntimeException
{
    public static function tooManyRequests(int $secondsRemaining): self
    {
        return new self(sprintf(
            'کد تأیید کمی پیش برای شما ارسال شد. لطفاً %d ثانیه دیگر دوباره تلاش کنید.',
            $secondsRemaining,
        ));
    }

    public static function hourlyLimitReached(): self
    {
        return new self(
            'تعداد درخواست کد تأیید شما در یک ساعت گذشته زیاد بوده است. '
            .'لطفاً یک ساعت دیگر تلاش کنید یا با پشتیبانی تماس بگیرید.',
        );
    }

    public static function noPendingCode(): self
    {
        return new self('کد تأییدی برای این شماره فعال نیست. لطفاً دوباره درخواست کد بدهید.');
    }

    public static function expired(): self
    {
        return new self('این کد منقضی شده است. لطفاً کد تازه درخواست کنید.');
    }

    public static function incorrect(int $attemptsLeft): self
    {
        return new self(sprintf('کد واردشده درست نیست. %d تلاش دیگر باقی مانده است.', $attemptsLeft));
    }

    public static function burned(): self
    {
        return new self('تعداد تلاش‌های نادرست زیاد بود و این کد سوخت. لطفاً کد تازه درخواست کنید.');
    }

    public static function accountSuspended(): self
    {
        return new self('حساب شما معلق شده است. برای پیگیری با پشتیبانی تماس بگیرید.');
    }
}
