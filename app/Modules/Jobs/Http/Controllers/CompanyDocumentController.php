<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Models\User;
use App\Modules\Jobs\Actions\ManageCompanyDocuments;
use App\Modules\Jobs\Admin\PendingJobItems;
use App\Modules\Jobs\Domain\CompanyDocument;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * مدرک‌های خصوصی شرکت: فرستادن، برداشتن و دانلود فقط برای خود کارفرما و مدیر کاریابی.
 */
final readonly class CompanyDocumentController
{
    public function __construct(
        private Repository $config,
        private Factory $storage,
    ) {}

    public function store(Request $request, ManageCompanyDocuments $documents): RedirectResponse
    {
        $rules = (array) $this->config->get('jobs.documents', []);

        $request->validate([
            'document' => ['required', 'file', 'mimes:'.implode(',', (array) ($rules['mimes'] ?? ['pdf'])), 'max:'.(int) ($rules['max_kb'] ?? 5120)],
        ]);

        $file = $request->file('document');

        try {
            $documents->add($this->user($request), $file instanceof UploadedFile ? $file : throw new RuntimeException('فایلی انتخاب نشده.'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        return to_route('jobs.company.edit')->with('status', 'مدرک برای بررسی مدیر فرستاده شد. فقط شما و مدیر آن را می‌بینید.');
    }

    public function destroy(Request $request, string $uuid, ManageCompanyDocuments $documents): RedirectResponse
    {
        try {
            $documents->remove($this->user($request), $this->find($uuid));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        return to_route('jobs.company.edit')->with('status', 'مدرک برداشته شد.');
    }

    public function download(Request $request, string $uuid): StreamedResponse
    {
        $user = $this->user($request);
        $document = $this->find($uuid);

        if ($document->company->user_id !== $user->getKey() && ! $user->can(PendingJobItems::ABILITY)) {
            throw new NotFoundHttpException('این مدرک پیدا نشد.');
        }

        $disk = $this->storage->disk('local');

        if (! $disk->exists($document->path)) {
            throw new NotFoundHttpException('فایل روی سرور پیدا نشد.');
        }

        return $disk->download($document->path, $document->original_name);
    }

    private function find(string $uuid): CompanyDocument
    {
        return CompanyDocument::query()->where('uuid', $uuid)->with('company')->first()
            ?? throw new NotFoundHttpException('این مدرک پیدا نشد.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
