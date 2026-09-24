<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain;

use App\Modules\Workspace\Domain\Enums\LegalChange;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک نسخه از یک صفحه حقوقی — فقط‌افزودنی.
 *
 * متنی که کاربری پذیرفته باید تا ابد همان بماند؛ برای همین `update` و `delete`
 * خطا می‌دهند و اصلاح یعنی نسخه تازه.
 *
 * @property int $id
 * @property string $uuid
 * @property LegalDocument $document
 * @property int $version
 * @property string $body
 * @property string|null $summary
 * @property LegalChange $change
 * @property Carbon $effective_at
 * @property int|null $published_by
 * @property Carbon $created_at
 */
final class LegalVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'document',
        'version',
        'body',
        'summary',
        'change',
        'effective_at',
        'published_by',
    ];

    /** @param  Builder<$this>  $query */
    public function scopeOf(Builder $query, LegalDocument $document): void
    {
        $query->where('document', $document->value);
    }

    /**
     * نسخه‌هایی که تاریخ اثرشان رسیده.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeInEffect(Builder $query, ?Carbon $at = null): void
    {
        $query->where('effective_at', '<=', $at ?? Carbon::now());
    }

    /**
     * متن به‌صورت بلوک‌های ساده: بند، و سرتیترهایی که با «## » شروع می‌شوند.
     *
     * عمداً HTML نیست و Markdown کامل هم نیست: متن حقوقی به جدول و تصویر نیاز
     * ندارد، و متنی که فقط به‌صورت متن ساده چاپ می‌شود هیچ راهی برای تزریق
     * اسکریپت باز نمی‌کند.
     *
     * @return list<array{heading: bool, text: string}>
     */
    public function blocks(): array
    {
        $blocks = [];

        foreach (preg_split('/\R{2,}/u', trim($this->body)) ?: [] as $chunk) {
            $chunk = trim($chunk);

            if ($chunk === '') {
                continue;
            }

            $heading = str_starts_with($chunk, '## ');
            $blocks[] = ['heading' => $heading, 'text' => $heading ? trim(substr($chunk, 3)) : $chunk];
        }

        return $blocks;
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('نسخه منتشرشده حقوقی تغییر نمی‌کند؛ نسخه تازه منتشر کنید.');
    }

    public function delete(): bool
    {
        throw new RuntimeException('نسخه منتشرشده حقوقی حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'document' => LegalDocument::class,
            'change' => LegalChange::class,
            'version' => 'integer',
            'effective_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
