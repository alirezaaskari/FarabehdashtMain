<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Models\User;
use App\Modules\Consulting\Domain\ConsultantDocument;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProfileReviewStatus;
use App\Modules\Consulting\Events\ConsultantDocumentChanged;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * مدرک‌های خصوصی مشاور برای بررسی مدیر (DEC-50): فقط روی دیسک local، نه
 * پوشه عمومی، و هیچ‌وقت روی صفحه عمومی.
 */
final readonly class ManageConsultantDocuments
{
    public function __construct(
        private Factory $storage,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function add(User $user, UploadedFile $file): ConsultantDocument
    {
        if (! $user->can('consulting.services.manage')) {
            throw new RuntimeException('فرستادن مدرک فقط برای مشاور است.');
        }

        $profile = ConsultantProfile::query()->firstOrCreate(
            ['user_id' => $user->getKey()],
            ['uuid' => (string) Str::uuid7(), 'status' => ProfileReviewStatus::Draft],
        );

        $max = (int) $this->config->get('consulting.documents.max', 5);

        if ($profile->documents()->count() >= $max) {
            throw new RuntimeException('بیش از '.$max.' مدرک نمی‌شود فرستاد؛ یکی را بردارید.');
        }

        $uuid = (string) Str::uuid7();
        $path = $this->storage->disk('local')->putFileAs(
            (string) $this->config->get('consulting.documents.directory', 'consulting/documents'),
            $file,
            $uuid.'.'.strtolower($file->getClientOriginalExtension() ?: 'bin'),
        );

        if ($path === false) {
            throw new RuntimeException('فایل ذخیره نشد؛ دوباره تلاش کنید.');
        }

        $document = ConsultantDocument::query()->create([
            'uuid' => $uuid,
            'profile_id' => $profile->id,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'size_bytes' => (int) $file->getSize(),
        ]);

        $this->events->dispatch(new ConsultantDocumentChanged($document, (int) $user->getKey(), removed: false));

        return $document;
    }

    public function remove(User $user, ConsultantDocument $document): void
    {
        if ($document->profile->user_id !== $user->getKey()) {
            throw new RuntimeException('این مدرک مال شما نیست.');
        }

        $this->storage->disk('local')->delete($document->path);
        $document->delete();

        $this->events->dispatch(new ConsultantDocumentChanged($document, (int) $user->getKey(), removed: true));
    }
}
