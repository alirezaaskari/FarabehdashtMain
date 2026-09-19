<?php

declare(strict_types=1);

namespace App\Modules\Admin\Filament\Pages;

use App\Models\User;
use App\Modules\Admin\Services\AdminAccess;
use App\Modules\Admin\Services\ApprovalQueue;
use App\Support\JalaliDate;
use Filament\Pages\Page;

/**
 * داشبورد تصمیم‌محور.
 *
 * اصل ۱ سند `docs/architecture/admin-panel.md`: صف یکپارچه تأیید در صدر صفحه
 * است و آمار پس از آن می‌آید. مدیر وقتی وارد می‌شود باید ببیند چه چیزی معطل
 * اوست، نه اینکه ماه پیش چند فروش بوده.
 *
 * سرویس‌ها در `mount()` تزریق می‌شوند، نه با helper: Livewire سازنده ندارد،
 * ولی تزریق در mount کار می‌کند و صفحه را قابل تست نگه می‌دارد.
 */
final class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected string $view = 'admin::filament.pages.dashboard';

    /** @var list<array{kind: string, title: string, url: string, waiting: string|null, by: string|null}> */
    public array $pending = [];

    /** @var list<string> */
    public array $roleLabels = [];

    public static function getNavigationLabel(): string
    {
        return 'داشبورد';
    }

    public function getTitle(): string
    {
        return 'چه چیزی معطل شماست؟';
    }

    public function mount(ApprovalQueue $queue, AdminAccess $access): void
    {
        $user = $this->currentUser();

        if (! $user instanceof User) {
            return;
        }

        $this->roleLabels = array_map(
            static fn ($role): string => $role->label(),
            $access->rolesOf($user),
        );

        // به آرایه ساده تبدیل می‌شود چون Livewire ویژگی عمومی را سریال می‌کند
        // و شیء DTO از آن رد نمی‌شود.
        $this->pending = array_map(static fn ($item): array => [
            'kind' => $item->kind,
            'title' => $item->title,
            'url' => $item->url,
            'waiting' => $item->waitingSince === null ? null : JalaliDate::long($item->waitingSince),
            'by' => $item->submittedBy,
        ], $queue->for($user));
    }

    private function currentUser(): ?User
    {
        $user = filament()->auth()->user();

        return $user instanceof User ? $user : null;
    }
}
