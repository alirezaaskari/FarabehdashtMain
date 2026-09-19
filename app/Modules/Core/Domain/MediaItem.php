<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * یک فایل آپلودشده.
 *
 * حذف نرم است: فایل تا وقتی مدیر پاک‌سازی نکند روی دیسک می‌ماند، چون حذف
 * اشتباهی یک تصویر از یک مقاله منتشرشده، برگشت‌پذیر نیست.
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property string|null $alt
 * @property int|null $width
 * @property int|null $height
 * @property int|null $uploaded_by
 */
final class MediaItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'alt',
        'width',
        'height',
        'attachable_type',
        'attachable_id',
        'uploaded_by',
    ];

    /** @return MorphTo<Model, $this> */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }
}
