<?php

declare(strict_types=1);

namespace App\Modules\Identity\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * کد ورود مدیر. متن ساده، بدون تصویر یا پیوند بیرونی.
 *
 * عمداً صف‌دار نیست: کد دو دقیقه‌ای نباید پشت صفی بماند که روی هاست
 * اشتراکی شاید اجرا نشود.
 */
final class AdminLoginCode extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $validMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'کد ورود به پنل '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(text: 'identity::mail.admin-login-code');
    }
}
