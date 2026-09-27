<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Models\User;
use App\Modules\Jobs\Actions\SubmitCompany;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\CompanyDraft;
use App\Modules\Jobs\Domain\Enums\CompanySize;
use App\Modules\Jobs\Services\JobCatalog;
use App\Support\Regions\Regions;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صفحه عمومی شرکت و ویرایش آن در میزکار کارفرما (DEC-65).
 */
final readonly class CompanyController
{
    public function __construct(
        private JobCatalog $catalog,
        private Regions $regions,
        private Repository $config,
    ) {}

    public function show(string $slug): View
    {
        $company = Company::query()->listed()->where('slug', $slug)->first() ?? throw new NotFoundHttpException('این شرکت پیدا نشد.');
        $url = route('jobs.companies.show', $company->slug);
        $logo = $this->catalog->logo($company->logo_id);

        return view('jobs::companies.show', [
            'company' => $company,
            'logo' => $logo,
            'place' => $this->catalog->place($company->province, $company->city),
            'postings' => $company->postings()->live()->with('company')->latest('published_at')->get(),
            'catalog' => $this->catalog,
            'seo' => (new SeoMeta(
                title: $company->name.' — آگهی‌های استخدام',
                description: Str::limit(trim($company->name.'، '.$company->industry.'. '.$company->about), 155),
                canonical: $url,
                image: $logo?->url,
            ))->withSchema(Schema::graph(
                Schema::webPage((string) $company->name, $url, $company->reviewed_at),
                Schema::breadcrumbs([
                    ['name' => 'کاریابی', 'url' => route('jobs.index')],
                    ['name' => (string) $company->name, 'url' => $url],
                ]),
            )),
        ]);
    }

    public function edit(Request $request): View
    {
        $company = $this->company($request);
        $draft = $company?->draft();

        return view('jobs::workspace.company', [
            'company' => $company,
            'draft' => $draft,
            'logo' => $this->catalog->logo($draft?->logoId),
            'sizes' => CompanySize::cases(),
            'regions' => $this->catalog->regionsForForm(),
            'documents' => $company === null ? collect() : $company->documents,
            'limits' => (array) $this->config->get('jobs.limits', []),
            'documentRules' => (array) $this->config->get('jobs.documents', []),
        ]);
    }

    public function update(Request $request, SubmitCompany $submit): RedirectResponse
    {
        $user = $this->user($request);
        $company = $this->company($request);
        $limits = (array) $this->config->get('jobs.limits', []);
        $slugFixed = $company?->published_at !== null;

        $validated = $request->validate([
            'slug' => $slugFixed ? ['prohibited'] : [
                'required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('companies', 'slug')->ignore($company?->id),
            ],
            'name' => ['required', 'string', 'min:2', 'max:'.($limits['name_max'] ?? 120)],
            'industry' => ['required', 'string', 'max:'.($limits['industry_max'] ?? 80)],
            'size' => ['required', Rule::enum(CompanySize::class)],
            'province' => ['required', Rule::in(array_keys($this->regions->provinces()))],
            'city' => ['required', Rule::in(array_keys($this->regions->cities((string) $request->input('province'))))],
            'about' => ['required', 'string', 'min:'.($limits['about_min'] ?? 60), 'max:'.($limits['about_max'] ?? 3000)],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'slug.regex' => 'نشانی فقط حروف کوچک لاتین، رقم و خط تیره دارد؛ مثل sepahan-steel.',
            'slug.unique' => 'این نشانی را شرکت دیگری گرفته است.',
            'city.in' => 'شهر را از استانی که انتخاب کرده‌اید برگزینید.',
        ]);

        $draft = CompanyDraft::fromArray([
            ...$validated,
            'slug' => $slugFixed ? $company->slug : $validated['slug'],
            'logo_id' => $company?->draft()?->logoId,
        ]);

        try {
            $submit->handle($user, $draft, $request->file('logo'));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['company' => $exception->getMessage()]);
        }

        return to_route('jobs.company.edit')
            ->with('status', 'صفحه شرکت برای تأیید مدیر فرستاده شد. تا تأیید، نسخه قبلی (اگر باشد) نمایش داده می‌شود.');
    }

    private function company(Request $request): ?Company
    {
        return Company::query()->where('user_id', $this->user($request)->getKey())->with('documents')->first();
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
