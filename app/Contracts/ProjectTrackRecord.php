<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Market\ProviderRecord;

/**
 * سابقه مجری در بازار پروژه (بخش ۲۱-۶)، برای صفحه عمومی مشاور و آزمایشگاه
 * بی import از ماژول بازار. اگر ماژول بازار نباشد این قرارداد ثبت نمی‌شود.
 */
interface ProjectTrackRecord
{
    /**
     * @param  list<int>  $userIds
     * @return array<int, ProviderRecord> کلید: شناسه کاربر
     */
    public function ofProviders(array $userIds): array;
}
