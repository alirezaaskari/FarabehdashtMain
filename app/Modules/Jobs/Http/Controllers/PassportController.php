<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Models\User;
use App\Modules\Jobs\Actions\SavePassport;
use App\Modules\Jobs\Domain\Enums\EntryKind;
use App\Modules\Jobs\Domain\Passport;
use App\Modules\Jobs\Services\JobCatalog;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Jobs\Services\SkillPassport;
use App\Support\Regions\Regions;
use App\Support\Seo\SeoMeta;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * گذرنامه مهارتی (۲۰-۳): ویرایش در میزکار و صفحه اشتراکی اختیاری با نشانی
 * ثابت که همیشه noindex است (DEC-69).
 */
final readonly class PassportController
{
    public function __construct(
        private SkillPassport $passport,
        private JobCatalog $catalog,
        private JobPricing $pricing,
        private Regions $regions,
        private Repository $config,
    ) {}

    public function edit(Request $request): View
    {
        $userId = (int) $this->user($request)->getKey();
        $passport = Passport::of($userId);
        $verified = $this->passport->verified($userId);

        return view('jobs::passport.edit', [
            'passport' => $passport->load('entries'),
            'verified' => $verified,
            'verifiedSkills' => $this->passport->verifiedSkills($userId, $verified),
            'declaredSkillIds' => array_map(static fn (TermData $term): int => $term->id, $this->passport->declaredSkills($passport)),
            'skills' => $this->catalog->skills(),
            'regions' => $this->catalog->regionsForForm(),
            'kinds' => EntryKind::cases(),
            'examMin' => $this->pricing->examMinPercent(),
            'limits' => (array) $this->config->get('jobs.passport', []),
            'catalog' => $this->catalog,
        ]);
    }

    public function update(Request $request, SavePassport $save): RedirectResponse
    {
        $skillIds = array_map(static fn (TermData $term): int => $term->id, $this->catalog->skills());

        $validated = $request->validate([
            'headline' => ['nullable', 'string', 'max:'.(int) $this->config->get('jobs.passport.headline_max', 120)],
            'province' => ['nullable', Rule::in(array_keys($this->regions->provinces()))],
            'city' => ['nullable', 'required_with:province', Rule::in(array_keys($this->regions->cities((string) $request->input('province'))))],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'skills' => ['nullable', 'array', 'max:15'],
            'skills.*' => ['integer', Rule::in($skillIds)],
        ], [
            'city.in' => 'شهر را از استانی که انتخاب کرده‌اید برگزینید.',
            'skills.max' => 'حداکثر ۱۵ مهارت؛ مهارت‌هایی که واقعاً با آن‌ها کار کرده‌اید.',
        ]);

        $save->profile(
            (int) $this->user($request)->getKey(),
            $validated['headline'] ?? null,
            $validated['province'] ?? null,
            $validated['city'] ?? null,
            isset($validated['experience_years']) ? (int) $validated['experience_years'] : null,
            array_map(intval(...), $validated['skills'] ?? []),
        );

        return to_route('jobs.passport.edit')->with('status', 'گذرنامه ذخیره شد.');
    }

    public function share(Request $request, SavePassport $save): RedirectResponse
    {
        $shared = $request->boolean('shared');
        $save->share((int) $this->user($request)->getKey(), $shared);

        return to_route('jobs.passport.edit')->with('status', $shared
            ? 'صفحه اشتراکی روشن شد. فقط کسی که نشانی را دارد آن را می‌بیند و موتورهای جست‌وجو آن را نمایه نمی‌کنند.'
            : 'صفحه اشتراکی خاموش شد و نشانی آن دیگر باز نمی‌شود.');
    }

    public function storeEntry(Request $request, SavePassport $save): RedirectResponse
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::enum(EntryKind::class)],
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'start_year' => ['nullable', 'integer', 'between:1300,1500'],
            'end_year' => ['nullable', 'integer', 'between:1300,1500'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'start_year.between' => 'سال را شمسی و چهاررقمی بنویسید، مثل ۱۳۹۸.',
            'end_year.between' => 'سال را شمسی و چهاررقمی بنویسید، مثل ۱۴۰۲.',
        ]);

        try {
            $save->addEntry(
                (int) $this->user($request)->getKey(),
                EntryKind::from((string) $validated['kind']),
                (string) $validated['title'],
                $validated['organization'] ?? null,
                isset($validated['start_year']) ? (int) $validated['start_year'] : null,
                isset($validated['end_year']) ? (int) $validated['end_year'] : null,
                $validated['note'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['entry' => $exception->getMessage()]);
        }

        return to_route('jobs.passport.edit')->with('status', 'سطر افزوده شد.');
    }

    public function destroyEntry(Request $request, int $entry, SavePassport $save): RedirectResponse
    {
        try {
            $save->removeEntry((int) $this->user($request)->getKey(), $entry);
        } catch (RuntimeException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        return to_route('jobs.passport.edit')->with('status', 'سطر برداشته شد.');
    }

    public function show(string $token): View
    {
        $passport = Passport::query()->where('share_token', $token)->where('shared', true)->with('entries', 'user')->first()
            ?? throw new NotFoundHttpException('این گذرنامه پیدا نشد یا صاحبش آن را خصوصی کرده است.');
        $verified = $this->passport->verified($passport->user_id);

        return view('jobs::passport.show', [
            'passport' => $passport,
            'verified' => $verified,
            'verifiedSkills' => $this->passport->verifiedSkills($passport->user_id, $verified),
            'declaredSkills' => $this->passport->declaredSkills($passport),
            'kinds' => EntryKind::cases(),
            'examMin' => $this->pricing->examMinPercent(),
            'catalog' => $this->catalog,
            'seo' => (new SeoMeta(title: 'گذرنامه مهارتی '.($passport->user->name ?: 'کاربر فرابهداشت')))->noindexed(),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
