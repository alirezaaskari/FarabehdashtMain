<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Models\User;
use App\Modules\Workspace\Actions\SwitchWorkspaceView;
use App\Modules\Workspace\Services\Dashboard;
use App\Modules\Workspace\Services\WorkspaceViews;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * میزکار — کارت‌های نمای انتخاب‌شده و سوییچر نماها.
 */
final readonly class DashboardController
{
    public function __construct(
        private WorkspaceViews $views,
        private Dashboard $dashboard,
    ) {}

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $view = $this->views->current($user);

        return view('workspace::dashboard', [
            'user' => $user,
            'view' => $view,
            'views' => $this->views->available($user),
            'widgets' => $this->dashboard->widgets($user, $view),
        ]);
    }

    public function switch(Request $request, SwitchWorkspaceView $switch): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $switch->handle($user, (string) $request->input('view'));
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('workspace.dashboard')->withErrors(['view' => $exception->getMessage()]);
        }

        return redirect()->route('workspace.dashboard');
    }
}
