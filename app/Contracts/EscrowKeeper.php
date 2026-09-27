<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Escrow\EscrowHold;
use App\Support\Escrow\EscrowHoldRequest;
use App\Support\Money;
use DomainException;

/**
 * نگه‌داری پول یک خدمت تا پایان کار (بخش ۱۹-۱).
 *
 * خریدار پول می‌دهد، پول در حساب «امانت وجه خدمت» می‌ماند و فقط یکی از سه راه
 * بسته می‌شود: آزادسازی به ارائه‌دهنده (منهای کمیسیون)، بازگشت کامل به کیف پول
 * خریدار، یا تقسیم با رأی مدیر. همه جابه‌جایی‌ها از دفتر کل می‌گذرند و هر کدام
 * idempotent است.
 *
 * ماژول دفتر کل این قرارداد را می‌بندد؛ اگر خاموش باشد، بسته نیست و مصرف‌کننده
 * پیش از فراخوانی با `app()->bound()` بررسی می‌کند.
 */
interface EscrowKeeper
{
    /** @throws DomainException اگر کمیسیون از مبلغ بیشتر یا مبلغ صفر باشد */
    public function hold(EscrowHoldRequest $request): EscrowHold;

    public function find(string $uuid): ?EscrowHold;

    /** همه پول به ارائه‌دهنده، منهای کمیسیون. */
    public function release(string $uuid, ?int $actorId = null): EscrowHold;

    /** همه پول به کیف پول خریدار. */
    public function refund(string $uuid, ?int $actorId = null, ?string $reason = null): EscrowHold;

    /**
     * `$toPayer` به کیف پول خریدار، باقی به ارائه‌دهنده. کمیسیون فقط به نسبت
     * بخش آزادشده برداشته می‌شود.
     */
    public function split(string $uuid, Money $toPayer, int $actorId, string $reason): EscrowHold;
}
