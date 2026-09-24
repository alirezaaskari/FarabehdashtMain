<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Modules\Workspace\Actions\AcceptLegalVersions;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use App\Modules\Workspace\Http\Middleware\RequireLegalAcceptance;
use App\Modules\Workspace\Services\LegalLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * صفحات حقوقی و پذیرش دوباره.
 *
 * هر نسخه نشانی ثابت خودش را دارد (`/legal/terms/v3`) تا بشود دقیقاً گفت
 * کاربر در تاریخ پذیرشش چه متنی را دیده است.
 */
final readonly class LegalController
{
    public function __construct(private LegalLibrary $library) {}

    public function show(string $document): View
    {
        $document = $this->document($document);

        return view('workspace::legal.show', [
            'document' => $document,
            'version' => $this->library->current($document),
            'history' => $this->library->history($document),
            'isLatest' => true,
        ]);
    }

    public function version(string $document, int $version): View
    {
        $document = $this->document($document);
        $found = $this->library->find($document, $version);

        abort_if($found === null, 404);

        $current = $this->library->current($document);

        return view('workspace::legal.show', [
            'document' => $document,
            'version' => $found,
            'history' => $this->library->history($document),
            'isLatest' => $current?->is($found) ?? false,
        ]);
    }

    public function accept(Request $request): View|RedirectResponse
    {
        $pending = $this->library->pendingFor((int) $request->user()?->getKey());

        if ($pending === []) {
            return redirect()->intended(route('workspace.dashboard'));
        }

        return view('workspace::legal.accept', [
            'pending' => $pending,
            'impersonating' => $request->session()->has(RequireLegalAcceptance::IMPERSONATION_SESSION_KEY),
        ]);
    }

    public function store(Request $request, AcceptLegalVersions $accept): RedirectResponse
    {
        // مدیری که میزکار را از چشم کاربر می‌بیند، به‌جای او قرارداد نمی‌پذیرد.
        abort_if($request->session()->has(RequireLegalAcceptance::IMPERSONATION_SESSION_KEY), 403);

        $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'برای ادامه، پذیرش قوانین را تأیید کنید.']);

        $accept->handle((int) $request->user()?->getKey());

        return redirect()->intended(route('workspace.dashboard'));
    }

    private function document(string $slug): LegalDocument
    {
        return LegalDocument::tryFrom($slug) ?? abort(404);
    }
}
