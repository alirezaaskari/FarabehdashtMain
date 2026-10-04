<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * فهرست ثابت خدمت‌های تخصصی و ارائه‌دهنده‌های منتشرشده آن‌ها (مشاور و
 * آزمایشگاه، بخش ۱۹-۵)، برای بازار پروژه (بخش ۲۱) بی import از ماژول مشاوره.
 *
 * ارائه‌دهنده‌ای که صفحه منتشرشده ندارد یا صفحه‌اش پنهان است در هیچ خروجی نیست.
 */
interface ServiceProviderDirectory
{
    /** @return array<string, string> کلید خدمت => نام فارسی (DEC-87) */
    public function services(): array;

    /**
     * @param  list<int>  $userIds
     * @return array<int, array{name: string, url: string, laboratory: bool, city: string|null}> کلید: شناسه کاربر؛ city کلید شهر است، نه نام
     */
    public function providersOf(array $userIds): array;

    /**
     * شناسه کاربری ارائه‌دهنده‌هایی که این خدمت را دارند یا در این شهرند.
     *
     * @return list<int>
     */
    public function matching(string $service, ?string $city): array;
}
