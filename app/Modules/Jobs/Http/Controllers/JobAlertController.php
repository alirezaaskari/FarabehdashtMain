<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Models\User;
use App\Modules\Jobs\Actions\ManageJobAlerts;
use App\Modules\Jobs\Domain\Enums\EmploymentType;
use App\Modules\Jobs\Domain\JobAlert;
use App\Modules\Jobs\Services\JobCatalog;
use App\Modules\Jobs\Services\JobMatcher;
use App\Modules\Jobs\Services\SkillPassport;
use App\Support\Regions\Regions;
use App\Support\Taxonomy\TermData;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * «شغل‌های مناسب من» (۲۰-۴): آگهی‌های زنده به ترتیب تطبیق با گذرنامه، و
 * هشدارهای ذخیره‌شده با پیامک اختیاری (DEC-73).
 */
final readonly class JobAlertController
{
    public function __construct(
        private ManageJobAlerts $alerts,
        private JobCatalog $catalog,
        private Regions $regions,
        private Repository $config,
    ) {}

    public function index(Request $request, SkillPassport $passport, JobMatcher $matcher): View
    {
        $userId = (int) $this->user($request)->getKey();
        $skillIds = $passport->skillIds($userId);
        $skills = $this->catalog->skills();

        return view('jobs::alerts.index', [
            'suggestions' => $matcher->suggestions($skillIds, (int) $this->config->get('jobs.alerts.suggestions', 20)),
            'hasSkills' => $skillIds !== [],
            'alerts' => JobAlert::query()->where('user_id', $userId)->latest('id')->get(),
            'skills' => $skills,
            'skillNames' => array_column(array_map(static fn (TermData $term): array => ['id' => $term->id, 'name' => $term->name], $skills), 'name', 'id'),
            'regions' => $this->catalog->regionsForForm(),
            'types' => EmploymentType::cases(),
            'catalog' => $this->catalog,
            'max' => (int) $this->config->get('jobs.alerts.max', 5),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $skillIds = array_map(static fn (TermData $term): int => $term->id, $this->catalog->skills());

        $validated = $request->validate([
            'city' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if ($this->regions->cityName((string) $value) === null) {
                    $fail('شهر را از فهرست انتخاب کنید.');
                }
            }],
            'skill' => ['nullable', 'integer', Rule::in($skillIds)],
            'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
            'match_passport' => ['nullable', 'boolean'],
            'sms' => ['nullable', 'boolean'],
        ]);

        try {
            $this->alerts->create(
                (int) $this->user($request)->getKey(),
                $validated['city'] ?? null,
                isset($validated['skill']) ? (int) $validated['skill'] : null,
                isset($validated['employment_type']) ? EmploymentType::from((string) $validated['employment_type']) : null,
                (bool) ($validated['match_passport'] ?? false),
                (bool) ($validated['sms'] ?? false),
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['alert' => $exception->getMessage()]);
        }

        return to_route('jobs.alerts.index')->with('status', 'هشدار ساخته شد. آگهی تازه‌ای که جور باشد اعلان می‌گیرد.');
    }

    public function sms(Request $request, int $alert): RedirectResponse
    {
        $this->guard(fn () => $this->alerts->setSms((int) $this->user($request)->getKey(), $alert, $request->boolean('sms')));

        return to_route('jobs.alerts.index')->with('status', $request->boolean('sms') ? 'پیامک خلاصه روزانه روشن شد.' : 'پیامک این هشدار خاموش شد.');
    }

    public function destroy(Request $request, int $alert): RedirectResponse
    {
        $this->guard(fn () => $this->alerts->delete((int) $this->user($request)->getKey(), $alert));

        return to_route('jobs.alerts.index')->with('status', 'هشدار برداشته شد.');
    }

    private function guard(callable $action): void
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
