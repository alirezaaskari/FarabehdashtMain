<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Http\Controllers;

use App\Models\User;
use App\Modules\Consulting\Actions\SubmitConsultantProfile;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\ProfileDraft;
use App\Modules\Consulting\Services\ConsultantPresenter;
use App\Support\Regions\Regions;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ویرایش صفحه عمومی در میزکار مشاور.
 */
final readonly class ConsultantProfileController
{
    public function __construct(
        private ConsultantPresenter $presenter,
        private Regions $regions,
        private Repository $config,
    ) {}

    public function edit(Request $request): View
    {
        $profile = $this->profile($request);
        // ویرایش در انتظار اگر هست، وگرنه نسخه منتشرشده با حوزه‌هایش.
        $draft = match (true) {
            $profile?->pending !== null => $profile->draft(),
            $profile?->published_at !== null => $profile->publishedDraft(
                array_map(static fn (TermData $term): int => $term->id, $this->presenter->domainsOf($profile)),
            ),
            default => null,
        };

        return view('consulting::edit', [
            'profile' => $profile,
            'draft' => $draft,
            'photo' => $this->presenter->photo($draft?->photoId),
            'terms' => $this->presenter->domainTerms(),
            'regions' => collect($this->regions->provinces())
                ->map(fn (string $name, string $key): array => ['name' => $name, 'cities' => $this->regions->cities($key)])
                ->all(),
            'documents' => $profile === null ? collect() : $profile->documents,
            'limits' => (array) $this->config->get('consulting.limits', []),
            'documentRules' => (array) $this->config->get('consulting.documents', []),
        ]);
    }

    public function update(Request $request, SubmitConsultantProfile $submit): RedirectResponse
    {
        $user = $this->user($request);
        $profile = $this->profile($request);
        $limits = (array) $this->config->get('consulting.limits', []);
        $termIds = array_map(static fn (TermData $term): int => $term->id, $this->presenter->domainTerms());
        $slugFixed = $profile?->published_at !== null;

        $validated = $request->validate([
            'slug' => $slugFixed ? ['prohibited'] : [
                'required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('consultant_profiles', 'slug')->ignore($profile?->id),
            ],
            'display_name' => ['required', 'string', 'max:'.($limits['name_max'] ?? 80)],
            'headline' => ['required', 'string', 'max:'.($limits['headline_max'] ?? 120)],
            'bio' => ['required', 'string', 'min:'.($limits['bio_min'] ?? 80), 'max:'.($limits['bio_max'] ?? 3000)],
            'province' => ['required', Rule::in(array_keys($this->regions->provinces()))],
            'city' => ['required', Rule::in(array_keys($this->regions->cities((string) $request->input('province'))))],
            'experience' => ['nullable', 'string', 'max:'.($limits['history_max'] ?? 2000)],
            'education' => ['nullable', 'string', 'max:'.($limits['history_max'] ?? 2000)],
            'domains' => $termIds === [] ? ['prohibited'] : ['required', 'array', 'min:1'],
            'domains.*' => ['integer', Rule::in($termIds)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'slug.regex' => 'نشانی فقط حروف کوچک لاتین، رقم و خط تیره دارد؛ مثل ali-rezaei.',
            'slug.unique' => 'این نشانی را مشاور دیگری گرفته است.',
            'city.in' => 'شهر را از استانی که انتخاب کرده‌اید برگزینید.',
            'domains.required' => 'دست‌کم یک حوزه تخصص انتخاب کنید.',
        ]);

        $draft = ProfileDraft::fromArray([
            ...$validated,
            'slug' => $slugFixed ? $profile->slug : $validated['slug'],
            'domain_ids' => $validated['domains'] ?? [],
            'photo_id' => $profile?->draft()?->photoId,
        ]);

        try {
            $submit->handle($user, $draft, $request->file('photo'));
        } catch (RuntimeException|InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['profile' => $exception->getMessage()]);
        }

        return to_route('consulting.profile.edit')
            ->with('status', 'صفحه شما برای تأیید مدیر فرستاده شد. تا تأیید، نسخه قبلی (اگر باشد) نمایش داده می‌شود.');
    }

    private function profile(Request $request): ?ConsultantProfile
    {
        return ConsultantProfile::query()->where('user_id', $this->user($request)->getKey())->with('documents')->first();
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
