<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Models\User;
use App\Modules\Jobs\Domain\CompanyDocument;
use App\Modules\Jobs\Events\CompanyDocumentChanged;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * مدرک ثبت شرکت یا معرفی‌نامه برای بررسی مدیر (DEC-65): فقط روی دیسک
 * local، نه پوشه عمومی، و هیچ‌وقت روی صفحه عمومی.
 */
final readonly class ManageCompanyDocuments
{
    public function __construct(
        private Factory $storage,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function add(User $user, UploadedFile $file): CompanyDocument
    {
        if (! $user->can(SubmitCompany::ABILITY)) {
            throw new RuntimeException('مدرک شرکت فقط برای کسی است که نقش کارفرمایش تأیید شده.');
        }

        $company = SubmitCompany::companyOf($user);
        $max = (int) $this->config->get('jobs.documents.max', 3);

        if ($company->documents()->count() >= $max) {
            throw new RuntimeException('بیش از '.$max.' مدرک نمی‌شود فرستاد؛ یکی را بردارید.');
        }

        $uuid = (string) Str::uuid7();
        $path = $this->storage->disk('local')->putFileAs(
            (string) $this->config->get('jobs.documents.directory', 'jobs/documents'),
            $file,
            $uuid.'.'.strtolower($file->getClientOriginalExtension() ?: 'bin'),
        );

        if ($path === false) {
            throw new RuntimeException('فایل ذخیره نشد؛ دوباره تلاش کنید.');
        }

        $document = CompanyDocument::query()->create([
            'uuid' => $uuid,
            'company_id' => $company->id,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'size_bytes' => (int) $file->getSize(),
        ]);

        $this->events->dispatch(new CompanyDocumentChanged($document, (int) $user->getKey(), removed: false));

        return $document;
    }

    public function remove(User $user, CompanyDocument $document): void
    {
        if ($document->company->user_id !== $user->getKey()) {
            throw new RuntimeException('این مدرک مال شما نیست.');
        }

        $this->storage->disk('local')->delete($document->path);
        $document->delete();

        $this->events->dispatch(new CompanyDocumentChanged($document, (int) $user->getKey(), removed: true));
    }
}
