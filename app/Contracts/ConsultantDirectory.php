<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * نشانی صفحه عمومی مشاوران (بخش ۱۹-۲)، برای پیوند از نام مشاور در بخش‌های
 * دیگر. مشاوری که صفحه منتشرشده ندارد در خروجی نیست.
 */
interface ConsultantDirectory
{
    /**
     * @param  list<int>  $userIds
     * @return array<int, array{name: string, url: string}> کلید: شناسه کاربر
     */
    public function profilesOf(array $userIds): array;
}
