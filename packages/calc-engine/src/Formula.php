<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;

/**
 * یک رابطه محاسباتی نسخه‌دار.
 *
 * هر پیاده‌سازی یک نسخه است، نه یک فرمول قابل ویرایش: نسخه در نام کلاس
 * می‌آید (WbgtIndoorV1) و اصلاح رفتار یعنی کلاس تازه با نسخه تازه، نه ویرایش
 * کلاس موجود. محاسبه‌های ذخیره‌شده با نسخه لحظه ثبتشان بازتولید می‌شوند، پس
 * نسخه قدیمی هیچ‌وقت حذف نمی‌شود.
 *
 * compute() باید خالص باشد: نه ساعت، نه تصادف، نه ورودی و خروجی. تست طلایی
 * روی همین خالص بودن حساب می‌کند.
 */
interface Formula
{
    public function id(): string;

    public function version(): string;

    public function title(): string;

    public function reference(): Reference;

    /**
     * محدودیت‌های این رابطه، به فارسی — چیزهایی که خروجی نمی‌گوید.
     *
     * @return list<string>
     */
    public function limitations(): array;

    /**
     * @return array<string, InputDefinition>
     */
    public function inputs(): array;

    /**
     * کلید هر خروجی و واحدش.
     *
     * @return array<string, Unit>
     */
    public function outputs(): array;

    public function compute(InputSet $inputs): Outcome;
}
