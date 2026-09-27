<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Actions\CompleteOnboarding;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * خوش‌آمد اولین ورود: نام (اختیاری) و اینکه کاربر برای چه آمده، تا به همان
 * بخش برود. یک بار دیده می‌شود؛ «بعداً» هم آن را تمام می‌کند.
 */
final readonly class WelcomeController
{
    public function __construct(
        private CompleteOnboarding $complete,
        private Repository $config,
    ) {}

    public function show(Request $request): View
    {
        return view('identity::welcome', [
            'user' => $this->user($request),
            'goals' => $this->goals(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:60'],
            'goal' => ['nullable', 'string', Rule::in(['skip', ...array_keys($this->goals())])],
        ], attributes: ['name' => 'نام']);

        $this->complete->handle($this->user($request), $data['name'] ?? null);

        $goal = $this->goals()[$data['goal'] ?? 'skip'] ?? null;

        return redirect()->route($goal['route'] ?? (Route::has('workspace.dashboard') ? 'workspace.dashboard' : 'identity.profiles'));
    }

    /** @return array<string, array{label: string, text: string, route: string}> فقط گزینه‌هایی که ماژولشان روشن است */
    private function goals(): array
    {
        /** @var array<string, array{label: string, text: string, route: string}> $goals */
        $goals = (array) $this->config->get('identity.onboarding.goals', []);

        return array_filter($goals, static fn (array $goal): bool => Route::has($goal['route']));
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
