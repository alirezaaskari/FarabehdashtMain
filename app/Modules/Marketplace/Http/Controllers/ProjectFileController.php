<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Http\Controllers;

use App\Models\User;
use App\Modules\Marketplace\Actions\ManageProjectFiles;
use App\Modules\Marketplace\Domain\ProjectFile;
use App\Modules\Marketplace\Services\ProjectAccess;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * پیوست خصوصی پروژه. برای هر کسی جز کارفرما، مدیر بازار و (از ۲۱-۴) مجری
 * قرارداد ۴۰۴ است تا وجود فایل هم لو نرود.
 */
final readonly class ProjectFileController
{
    public function __construct(
        private ProjectAccess $access,
        private Factory $storage,
    ) {}

    public function download(Request $request, string $uuid): StreamedResponse
    {
        $file = ProjectFile::query()->where('uuid', $uuid)->with('project')->first();
        $user = $request->user();

        if ($file === null || ! $this->access->canDownloadFiles($user instanceof User ? $user : null, $file->project)) {
            throw new NotFoundHttpException('این فایل پیدا نشد.');
        }

        return $this->storage->disk('local')->download($file->path, $file->original_name);
    }

    public function destroy(Request $request, string $uuid, ManageProjectFiles $files): RedirectResponse
    {
        $file = ProjectFile::query()->where('uuid', $uuid)->with('project')->first();
        $user = $request->user();

        if ($file === null || ! $user instanceof User) {
            throw new NotFoundHttpException('این فایل پیدا نشد.');
        }

        try {
            $files->remove($user, $file);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['project' => $exception->getMessage()]);
        }

        return back()->with('status', 'پیوست برداشته شد.');
    }
}
