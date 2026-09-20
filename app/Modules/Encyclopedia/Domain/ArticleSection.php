<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * یک بخش از محتوا.
 *
 * @property int $id
 * @property int $article_id
 * @property int $position
 * @property string $heading
 * @property string $body
 * @property string|null $note
 * @property string|null $tool_slug
 */
final class ArticleSection extends Model
{
    protected $fillable = ['article_id', 'position', 'heading', 'body', 'note', 'tool_slug'];

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * لنگر این بخش برای فهرست «در این مقاله».
     *
     * از شماره ساخته می‌شود و نه از عنوان: عنوان فارسی در نشانی خوانا نیست و
     * با هر ویرایش عوض می‌شود، یعنی پیوند ذخیره‌شده کاربر می‌شکند.
     */
    public function anchor(): string
    {
        return 's'.$this->position;
    }

    /**
     * بدنه به پاراگراف‌های جدا.
     *
     * متن ساده ذخیره می‌شود و هرگز به‌صورت HTML خام چاپ نمی‌شود؛ شکستن به
     * پاراگراف کار نمایش است و همین‌جا انجام می‌شود تا قالب منطق نداشته باشد.
     *
     * @return list<string>
     */
    public function paragraphs(): array
    {
        $parts = preg_split('/\R{2,}/u', trim($this->body)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
