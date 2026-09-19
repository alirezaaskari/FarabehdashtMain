<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Actions\DeactivateProfile;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Services\PermissionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final readonly class ProfileController
{
    public function __construct(
        private RequestProfileActivation $request,
        private DeactivateProfile $deactivate,
        private PermissionResolver $permissions,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);

        $permissions = $this->permissions->for($user);

        return view('identity::profiles', [
            'user' => $user,
            'types' => ProfileType::cases(),
            'permissions' => $permissions,
            'permissionLabels' => array_combine(
                $permissions,
                array_map($this->permissions->label(...), $permissions),
            ),
        ]);
    }

    public function activate(Request $request, string $type): RedirectResponse
    {
        $profileType = ProfileType::tryFrom($type);

        if (! $profileType instanceof ProfileType) {
            abort(404);
        }

        $this->request->handle($this->user($request), $profileType);

        return back()->with(
            'status',
            sprintf('درخواست فعال‌سازی نقش «%s» ثبت شد و در صف بررسی مدیر است.', $profileType->label()),
        );
    }

    public function deactivate(Request $request, string $type): RedirectResponse
    {
        $profileType = ProfileType::tryFrom($type);

        if (! $profileType instanceof ProfileType) {
            abort(404);
        }

        $this->deactivate->handle($this->user($request), $profileType);

        return back()->with(
            'status',
            sprintf('نقش «%s» غیرفعال شد. داده‌های شما دست‌نخورده باقی مانده است.', $profileType->label()),
        );
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
