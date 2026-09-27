<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Jobs\Actions\ClosePosting;
use App\Modules\Jobs\Actions\PostingCheckout;
use App\Modules\Jobs\Actions\SubmitPosting;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\EmploymentType;
use App\Modules\Jobs\Domain\Enums\PaymentStatus;
use App\Modules\Jobs\Domain\Enums\PostingState;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\PostingDraft;
use App\Modules\Jobs\Domain\PostingPayment;
use App\Modules\Jobs\Services\JobCatalog;
use App\Modules\Jobs\Services\JobPricing;
use App\Support\Money;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
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
 * آگهی‌های کارفرما در میزکار: ساخت، ویرایش، بستن، و انتشار یا تمدید با
 * پرداخت (DEC-63).
 */
final readonly class EmployerPostingController
{
    public function __construct(
        private JobCatalog $catalog,
        private JobPricing $pricing,
        private PostingCheckout $checkout,
        private PaymentGateway $gateway,
        private Regions $regions,
        private Repository $config,
    ) {}

    public function index(Request $request): View
    {
        $company = $this->company($request);

        return view('jobs::workspace.postings', [
            'company' => $company,
            'postings' => $company === null ? collect() : $company->postings()->withCount('applications')->latest('id')->get(),
            'price' => $company === null ? $this->pricing->price() : $this->pricing->priceFor($company),
            'days' => $this->pricing->days(),
            'catalog' => $this->catalog,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $company = $this->company($request);

        if ($company === null || ! $company->isListed()) {
            return to_route('jobs.company.edit')->withErrors(['company' => 'اول صفحه شرکت را بسازید؛ آگهی پس از تأیید صفحه شرکت ثبت می‌شود.']);
        }

        return $this->form(null, null, $company);
    }

    public function store(Request $request, SubmitPosting $submit): RedirectResponse
    {
        try {
            $submit->create($this->user($request), $this->draft($request));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['posting' => $exception->getMessage()]);
        }

        return to_route('jobs.employer.postings.index')->with('status', 'آگهی برای تأیید مدیر فرستاده شد. پس از تأیید اعلان می‌گیرید.');
    }

    public function edit(Request $request, string $uuid): View
    {
        $posting = $this->posting($request, $uuid);
        $draft = $posting->draft() ?? ($posting->title === null ? null : $posting->publishedDraft($this->catalog->skillIdsOf($posting)));

        return $this->form($posting, $draft, $posting->company);
    }

    public function update(Request $request, string $uuid, SubmitPosting $submit): RedirectResponse
    {
        $posting = $this->posting($request, $uuid);

        try {
            $submit->update($this->user($request), $posting, $this->draft($request));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['posting' => $exception->getMessage()]);
        }

        return to_route('jobs.employer.postings.index')->with('status', $posting->state() === PostingState::Live
            ? 'ویرایش برای تأیید مدیر فرستاده شد. تا تأیید، متن قبلی آگهی نمایش داده می‌شود.'
            : 'آگهی برای تأیید مدیر فرستاده شد.');
    }

    public function close(Request $request, string $uuid, ClosePosting $close): RedirectResponse
    {
        $close->handle($this->user($request), $this->posting($request, $uuid));

        return to_route('jobs.employer.postings.index')->with('status', 'آگهی بسته شد و دیگر در فهرست نمی‌آید.');
    }

    public function checkout(Request $request, string $uuid): View|RedirectResponse
    {
        $posting = $this->posting($request, $uuid);

        if (! $this->payable($posting)) {
            return to_route('jobs.employer.postings.index');
        }

        $price = $this->pricing->priceFor($posting->company);

        return view('jobs::workspace.publish', [
            'posting' => $posting,
            'price' => $price,
            'days' => $this->pricing->days(),
            'renewal' => $posting->published_at !== null,
        ]);
    }

    public function pay(Request $request, string $uuid): View|RedirectResponse
    {
        $user = $this->user($request);
        $posting = $this->posting($request, $uuid);

        if (! $this->payable($posting)) {
            return to_route('jobs.employer.postings.index');
        }

        $source = PaymentSource::requested($request);

        try {
            $payment = $this->checkout->open($posting);

            if ($payment->is_free) {
                return $this->paid($this->checkout->publishFree($payment, (int) $user->getKey()));
            }

            if ($source === PaymentSource::Wallet) {
                return $this->paid($this->checkout->payFromWallet($user, $payment));
            }

            return redirect()->away($this->checkout->startGateway($payment, $user->mobile)->redirectUrl);
        } catch (InsufficientWalletBalance $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return view('jobs::workspace.checkout-failed', ['reason' => PaymentGatewayUnavailable::USER_MESSAGE, 'posting' => $posting]);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
    }

    public function callback(Request $request): View
    {
        $payment = PostingPayment::query()->where('gateway_authority', (string) $request->query('Authority'))->with('posting.company')->firstOrFail();

        if ($payment->status === PaymentStatus::Paid) {
            return $this->paid($payment);
        }

        $fail = function (string $reason) use ($payment): View {
            $payment->forceFill(['status' => PaymentStatus::Failed])->save();

            return view('jobs::workspace.checkout-failed', ['reason' => $reason, 'posting' => $payment->posting]);
        };

        if ((string) $request->query('Status') !== 'OK') {
            return $fail('پرداخت توسط شما لغو شد.');
        }

        $verification = $this->gateway->verify((string) $payment->gateway_authority, $payment->price());

        if (! $verification->successful) {
            return $fail((string) $verification->failureReason);
        }

        return $this->paid($this->checkout->complete($payment, $verification->referenceId ?? ''));
    }

    private function paid(PostingPayment $payment): View
    {
        $payment->refresh();

        return view('jobs::workspace.published', ['payment' => $payment, 'posting' => $payment->posting]);
    }

    private function payable(JobPosting $posting): bool
    {
        return in_array($posting->state(), [PostingState::AwaitingPayment, PostingState::Live, PostingState::Expired], true);
    }

    private function form(?JobPosting $posting, ?PostingDraft $draft, Company $company): View
    {
        return view('jobs::workspace.posting-form', [
            'posting' => $posting,
            'draft' => $draft,
            'company' => $company,
            'types' => EmploymentType::cases(),
            'skills' => $this->catalog->skills(),
            'regions' => $this->catalog->regionsForForm(),
            'limits' => (array) $this->config->get('jobs.limits', []),
        ]);
    }

    private function draft(Request $request): PostingDraft
    {
        $limits = (array) $this->config->get('jobs.limits', []);
        $skillIds = array_map(static fn (TermData $term): int => $term->id, $this->catalog->skills());
        $salaryMax = (int) ($limits['salary_max_toman'] ?? 1_000_000_000);

        // مبلغ با جداکننده فارسی یا لاتین نوشته می‌شود؛ پیش از اعتبارسنجی عدد خالص می‌شود.
        foreach (['salary_min', 'salary_max'] as $key) {
            $value = trim((string) $request->input($key));
            try {
                $request->merge([$key => $value === '' ? null : Money::fromInput($value)->toman]);
            } catch (InvalidArgumentException) {
                // همان متن می‌ماند تا قاعده integer پیام خطای فرم را بدهد.
            }
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:'.($limits['title_max'] ?? 120)],
            'province' => ['required', Rule::in(array_keys($this->regions->provinces()))],
            'city' => ['required', Rule::in(array_keys($this->regions->cities((string) $request->input('province'))))],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'min_experience_years' => ['required', 'integer', 'min:0', 'max:'.($limits['experience_max'] ?? 30)],
            'salary_min' => ['nullable', 'integer', 'min:1', 'max:'.$salaryMax],
            'salary_max' => ['nullable', 'integer', 'min:1', 'max:'.$salaryMax, 'gte:salary_min'],
            'description' => ['required', 'string', 'min:'.($limits['description_min'] ?? 120), 'max:'.($limits['description_max'] ?? 6000)],
            'skills' => $skillIds === [] ? ['prohibited'] : ['required', 'array', 'min:1', 'max:'.($limits['skills_max'] ?? 8)],
            'skills.*' => ['integer', Rule::in($skillIds)],
        ], [
            'city.in' => 'شهر را از استانی که انتخاب کرده‌اید برگزینید.',
            'salary_max.gte' => 'سقف حقوق از کف آن کمتر است.',
            'skills.required' => 'دست‌کم یک مهارت لازم را انتخاب کنید؛ کارجو با همین مهارت‌ها آگهی را پیدا می‌کند.',
            'skills.max' => 'حداکثر '.($limits['skills_max'] ?? 8).' مهارت؛ فقط مهارت‌های اصلی این شغل.',
        ]);

        return PostingDraft::fromArray([
            ...$validated,
            'salary_min_toman' => $validated['salary_min'] ?? null,
            'salary_max_toman' => $validated['salary_max'] ?? null,
            'skill_ids' => $validated['skills'] ?? [],
        ]);
    }

    private function company(Request $request): ?Company
    {
        return Company::query()->where('user_id', $this->user($request)->getKey())->first();
    }

    private function posting(Request $request, string $uuid): JobPosting
    {
        $posting = JobPosting::query()->where('uuid', $uuid)->with('company')->first();

        if ($posting === null || $posting->company->user_id !== $this->user($request)->getKey()) {
            throw new NotFoundHttpException('این آگهی پیدا نشد.');
        }

        return $posting;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
