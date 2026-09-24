<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Modules\Workspace\Actions\AcceptLegalVersions;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use App\Modules\Workspace\Domain\LegalVersion;
use App\Modules\Workspace\Http\Middleware\RequireLegalAcceptance;
use App\Modules\Workspace\Services\LegalLibrary;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
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
        $current = $this->library->current($document);

        return view('workspace::legal.show', [
            'document' => $document,
            'version' => $current,
            'history' => $this->library->history($document),
            'isLatest' => true,
            'seo' => $this->seo($document, $current, isLatest: true),
        ]);
    }

    public function version(string $document, int $version): View
    {
        $document = $this->document($document);
        $found = $this->library->find($document, $version);

        abort_if($found === null, 404);

        $current = $this->library->current($document);
        $isLatest = $current?->is($found) ?? false;

        return view('workspace::legal.show', [
            'document' => $document,
            'version' => $found,
            'history' => $this->library->history($document),
            'isLatest' => $isLatest,
            'seo' => $this->seo($document, $found, $isLatest),
        ]);
    }

    /**
     * نسخه قدیمی برای استناد می‌ماند ولی ایندکس نمی‌شود؛ نشانی اصلی همیشه
     * نسخه جاری است. سندی که هنوز منتشر نشده هم صفحه خالی است و ایندکس نمی‌شود.
     */
    private function seo(LegalDocument $document, ?LegalVersion $version, bool $isLatest): SeoMeta
    {
        $url = route('workspace.legal.show', $document->value);
        $meta = new SeoMeta(title: $document->label(), description: $document->label().' فرابهداشت', canonical: $url);

        if (! $isLatest || $version === null) {
            return $meta->noindexed();
        }

        return $meta->withSchema(Schema::webPage($document->label(), $url, $version->effective_at));
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
