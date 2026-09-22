<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Domain;

use App\Support\PersianNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * یک منبع نسخه‌دار.
 *
 * @property int $id
 * @property int $article_id
 * @property int $position
 * @property string $title
 * @property string|null $publisher
 * @property string|null $edition
 * @property int|null $year
 * @property string|null $url
 */
final class ArticleReference extends Model
{
    protected $fillable = ['article_id', 'position', 'title', 'publisher', 'edition', 'year', 'url'];

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * بخش نسخه‌دار استناد: «ویرایش ۲۰۰۹» یا «۲۰۰۹» یا هیچ.
     *
     * بدون ویرایش، استناد به یک استاندارد بی‌معناست؛ همین رشته است که منبع را
     * از یک اسم به یک سند مشخص تبدیل می‌کند.
     *
     * ارقام از خط خود منبع پیروی می‌کنند: «ISO 9612 (2009)» با ارقام لاتین
     * درست است و «حدود مجاز مواجهه شغلی ایران (۱۴۰۰)» با ارقام فارسی. آمیختن
     * ارقام فارسی با عنوان لاتین، استناد را ناخوانا می‌کند.
     */
    public function version(): ?string
    {
        $edition = $this->edition;
        $year = $this->year === null ? null : (string) $this->year;

        if (! $this->usesLatinScript()) {
            $edition = $edition === null ? null : PersianNumber::digitsOnly($edition);
            $year = $year === null ? null : PersianNumber::digitsOnly($year);
        }

        $parts = array_filter([
            $edition === null ? null : ($this->usesLatinScript() ? 'ed. ' : 'ویرایش ').$edition,
            $year,
        ]);

        return $parts === [] ? null : implode($this->usesLatinScript() ? ', ' : '، ', $parts);
    }

    /**
     * آیا عنوان منبع لاتین است.
     *
     * نبودِ حرف عربی/فارسی در عنوان، ساده‌ترین نشانه قابل اعتماد است: عنوان
     * استاندارد بین‌المللی هرگز حرف فارسی ندارد و عنوان سند ایرانی همیشه دارد.
     */
    public function usesLatinScript(): bool
    {
        return preg_match('/\p{Arabic}/u', $this->title) !== 1;
    }

    /** آیا این منبع نسخه‌دار است — معیار نمره سلامت محتوا. */
    public function isVersioned(): bool
    {
        return $this->edition !== null || $this->year !== null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer', 'year' => 'integer'];
    }
}
