<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Passport\PassportEvidence;

/**
 * یک منبع بخش «ثبت‌شده در فرابهداشت» گذرنامه مهارتی (بخش ۲۰-۳).
 *
 * گذرنامه مدل هیچ ماژولی را نمی‌شناسد (قاعده ۱). هر ماژولی که کاری از کاربر
 * را خودش ثبت کرده (دوره تمام‌شده، کارنامه آزمون، گزارش صادرشده، پاسخ
 * منتشرشده) این قرارداد را با برچسب {@see TAG} ثبت می‌کند. با حذف یک
 * ماژول فقط بخش همان منبع از گذرنامه می‌رود.
 *
 * منبع فقط داده خود همان کاربر را برمی‌گرداند و چیزی را که کاربر خودش
 * نوشته باشد «ثبت‌شده» حساب نمی‌کند.
 */
interface PassportEvidenceSource
{
    public const TAG = 'passport.evidence_sources';

    /** کلید پایدار منبع، مثل «courses». */
    public function key(): string;

    /** عنوان بخش در گذرنامه، مثل «دوره‌های تمام‌شده». */
    public function label(): string;

    /** @return list<PassportEvidence> تازه‌ترین اول */
    public function evidence(int $userId): array;
}
