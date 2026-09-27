<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Http\Controllers;

use App\Models\User;
use App\Modules\Monetization\Actions\ManageTeamFiles;
use App\Modules\Monetization\Domain\TeamFile;
use App\Modules\Monetization\Services\TeamAccess;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** کتابخانه مشترک تیم؛ فقط برای صاحب و اعضای فعال همان تیم. */
final readonly class TeamLibraryController
{
    public function __construct(
        private TeamAccess $access,
        private ManageTeamFiles $files,
        private Filesystem $storage,
        private Repository $config,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);
        $team = $this->access->libraryTeam($user);

        return view('monetization::team.library', [
            'team' => $team,
            'files' => $team === null ? null : TeamFile::query()->where('team_id', $team->id)->with('uploader')->latest()->paginate(30),
            'user' => $user,
            'mimes' => (array) $this->config->get('monetization.team.file_mimes', []),
            'maxKb' => (int) $this->config->get('monetization.team.file_max_kb', 20480),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $team = $this->access->libraryTeam($user) ?? throw new NotFoundHttpException;
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'file' => ['required', 'file', 'max:'.(int) $this->config->get('monetization.team.file_max_kb', 20480), 'mimes:'.implode(',', (array) $this->config->get('monetization.team.file_mimes', []))],
        ]);

        try {
            $this->files->add($user, $team, $validated['file'], $validated['title']);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['file' => $exception->getMessage()]);
        }

        return to_route('monetization.team.library')->with('status', 'فایل در کتابخانه تیم گذاشته شد.');
    }

    public function download(Request $request, string $uuid): StreamedResponse
    {
        $file = $this->file($request, $uuid);

        return $this->storage->disk('local')->download($file->path, $file->original_name);
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        try {
            $this->files->remove($this->user($request), $this->file($request, $uuid));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return to_route('monetization.team.library')->with('status', 'فایل برداشته شد.');
    }

    private function file(Request $request, string $uuid): TeamFile
    {
        $team = $this->access->libraryTeam($this->user($request)) ?? throw new NotFoundHttpException;

        return TeamFile::query()->where('uuid', $uuid)->where('team_id', $team->id)->with('team')->first()
            ?? throw new NotFoundHttpException;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
