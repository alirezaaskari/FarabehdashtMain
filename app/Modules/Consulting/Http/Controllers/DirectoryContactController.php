<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Http\Controllers;

use App\Models\User;
use App\Modules\Consulting\Actions\HandleDirectoryContact;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\DirectoryContact;
use App\Modules\Consulting\Domain\Enums\ProviderKind;
use App\Modules\Consulting\Services\DirectoryCatalog;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * درخواست تماس با آزمایشگاه: فرستادن از صفحه آزمایشگاه، فهرست رسیده‌ها با
 * پاسخ برای آزمایشگاه، و فهرست فرستاده‌ها برای درخواست‌دهنده.
 */
final readonly class DirectoryContactController
{
    public function __construct(
        private HandleDirectoryContact $contacts,
        private DirectoryCatalog $catalog,
        private Repository $config,
    ) {}

    public function store(Request $request, string $slug): RedirectResponse
    {
        $lab = ConsultantProfile::query()->listed()->where('kind', ProviderKind::Laboratory)->where('slug', $slug)->first()
            ?? throw new NotFoundHttpException('این آزمایشگاه پیدا نشد.');
        $limits = (array) $this->config->get('consulting.directory', []);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:'.($limits['contact_min'] ?? 20), 'max:'.($limits['contact_max'] ?? 2000)],
            'service' => ['nullable', Rule::in($lab->offerings ?? [])],
            'share_mobile' => ['nullable', 'boolean'],
        ]);

        try {
            $this->contacts->request($this->user($request), $lab, $validated['message'], $validated['service'] ?? null, $lab->city, (bool) ($validated['share_mobile'] ?? false));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['contact' => $exception->getMessage()]);
        }

        return to_route('consulting.contacts.mine')->with('status', 'درخواست شما به آزمایشگاه رسید. پاسخش همین‌جا می‌آید و اعلان می‌گیرید.');
    }

    public function mine(Request $request): View
    {
        return view('consulting::contacts.mine', [
            'contacts' => DirectoryContact::query()
                ->where('user_id', $this->user($request)->getKey())
                ->with('profile')
                ->latest()
                ->paginate(20),
            'catalog' => $this->catalog,
        ]);
    }

    public function incoming(Request $request): View
    {
        $profile = ConsultantProfile::query()->where('user_id', $this->user($request)->getKey())->first();

        return view('consulting::contacts.incoming', [
            'contacts' => $profile === null ? null : DirectoryContact::query()
                ->where('profile_id', $profile->id)
                ->with('user')
                ->orderByRaw('replied_at is null desc')
                ->latest()
                ->paginate(20),
            'catalog' => $this->catalog,
            'replyMax' => (int) $this->config->get('consulting.directory.reply_max', 2000),
        ]);
    }

    public function reply(Request $request, string $uuid): RedirectResponse
    {
        $validated = $request->validate(['reply' => ['required', 'string', 'max:'.(int) $this->config->get('consulting.directory.reply_max', 2000)]]);
        $contact = DirectoryContact::query()->where('uuid', $uuid)->with('profile')->first() ?? throw new NotFoundHttpException;

        try {
            $this->contacts->reply($this->user($request), $contact, $validated['reply']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['reply' => $exception->getMessage()]);
        }

        return to_route('consulting.contacts.incoming')->with('status', 'پاسخ فرستاده شد.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
