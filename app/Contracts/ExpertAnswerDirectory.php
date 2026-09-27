<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Expert\AnswerLink;

/**
 * پاسخ‌های منتشرشده هر مشاور زیر پرسش‌های عمومی (بخش ۱۹-۲).
 *
 * صفحه عمومی مشاور کار او را از همین‌جا نشان می‌دهد. پاسخ پرسش خصوصی هرگز
 * برگردانده نمی‌شود (DEC-41).
 */
interface ExpertAnswerDirectory
{
    /** @return list<AnswerLink> تازه‌ترین اول */
    public function publishedBy(int $userId, int $limit): array;

    public function countPublishedBy(int $userId): int;
}
