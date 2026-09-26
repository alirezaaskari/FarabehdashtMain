<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Taxonomy\TermData;
use InvalidArgumentException;

/**
 * دسته‌بندی مشترک محتواها از بیرون Core (بخش ۱۸-۱۱).
 *
 * ماژول صاحب محتوا برچسب‌هایش را از این قرارداد می‌خواند و می‌نویسد؛ خود
 * برچسب‌ها را مدیر در پنل «دسته‌بندی‌ها» می‌سازد. فهرست دسته‌بندی‌ها بسته
 * است (`config/core.php`) و نام ثبت‌نشده خطا است.
 */
interface Taxonomy
{
    /**
     * @return list<TermData>
     *
     * @throws InvalidArgumentException
     */
    public function terms(string $taxonomy): array;

    /**
     * @param  class-string  $type
     * @return list<TermData>
     */
    public function termsOf(string $type, int $id, string $taxonomy): array;

    /**
     * برچسب‌های یک محتوا در یک دسته‌بندی را با همین فهرست جایگزین می‌کند؛
     * شناسه‌ای که از این دسته‌بندی نیست نادیده گرفته می‌شود.
     *
     * @param  class-string  $type
     * @param  list<int>  $termIds
     */
    public function sync(string $type, int $id, string $taxonomy, array $termIds): void;

    /**
     * شناسه محتواهایی از این نوع که این برچسب را دارند.
     *
     * @param  class-string  $type
     * @return list<int>
     */
    public function taggedIds(string $type, string $taxonomy, string $slug): array;
}
