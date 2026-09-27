<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Jobs\Actions\ResumeBankCheckout;
use App\Modules\Jobs\Actions\ResumeBankRequests;
use App\Modules\Jobs\Domain\BankPackage;
use App\Modules\Jobs\Domain\BankRequest;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\PaymentStatus;
use App\Modules\Jobs\Services\JobCatalog;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Jobs\Services\ResumeBank;
use App\Support\Money;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use App\Support\PersianDigits;
use App\Support\Regions\Regions;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * بانک رزومه از نگاه کارفرما (۲۰-۵): جست‌وجوی کارت‌های ناشناس، درخواست
 * تماس، خرید بسته (DEC-72) و دیدن راه تماس کارجوی پذیرفته.
 */
final readonly class TalentController
{
    public function __construct(
        private ResumeBank $bank,
        private JobPricing $pricing,
        private JobCatalog $catalog,
        private Regions $regions,
        private Repository $config,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $company = $this->company($request);

        if ($company === null) {
            return to_route('jobs.company.edit')->withErrors(['company' => 'بانک رزومه برای کارفرمایی است که صفحه شرکتش تأیید شده است. اول صفحه شرکت را بسازید.']);
        }

        $skills = $this->catalog->skills();
        $filters = $request->validate([
            'skill' => ['nullable', 'integer', Rule::in(array_map(static fn (TermData $term): int => $term->id, $skills))],
            'city' => ['nullable', 'string', 'max:40'],
            'years' => ['nullable', 'integer', 'min:0', 'max:40'],
        ]);
        $city = isset($filters['city']) && $this->regions->cityName((string) $filters['city']) !== null ? (string) $filters['city'] : null;

        $cards = $this->bank->search(
            isset($filters['skill']) ? (int) $filters['skill'] : null,
            null,
            $city,
            isset($filters['years']) ? (int) $filters['years'] : null,
            (int) $this->user($request)->getKey(),
        );

        $perPage = (int) $this->config->get('jobs.bank.per_page', 12);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(array_slice($cards, ($page - 1) * $perPage, $perPage), count($cards), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('jobs::bank.search', [
            'company' => $company,
            'cards' => $paginator,
            'asked' => BankRequest::query()->where('company_id', $company->id)->latest('id')->get()->keyBy('jobseeker_id')->map->status->all(),
            'skills' => $skills,
            'regions' => $this->catalog->regionsForForm(),
            'filters' => ['skill' => $filters['skill'] ?? null, 'city' => $city, 'years' => $filters['years'] ?? null],
            ...$this->billing($company),
        ]);
    }

    public function contact(Request $request, string $token, ResumeBankRequests $requests): RedirectResponse
    {
        $note = $request->validate(['note' => ['nullable', 'string', 'max:'.(int) $this->config->get('jobs.bank.note_max', 500)]])['note'] ?? null;

        try {
            $requests->send($this->user($request), $token, $note === null ? null : (string) $note);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['bank' => $exception->getMessage()]);
        }

        return back()->with('status', 'درخواست تماس فرستاده شد. کارجو تا '.PersianDigits::from($this->pricing->bankReplyDays()).' روز فرصت پاسخ دارد.');
    }

    public function requests(Request $request): View|RedirectResponse
    {
        $company = $this->company($request);

        if ($company === null) {
            return to_route('jobs.company.edit');
        }

        return view('jobs::bank.requests', [
            'requests' => BankRequest::query()->where('company_id', $company->id)->with('jobseeker')->latest('id')->get(),
            'shown' => session('contact_shown'),
            ...$this->billing($company),
        ]);
    }

    public function reveal(Request $request, string $uuid, ResumeBankRequests $requests): RedirectResponse
    {
        $bankRequest = BankRequest::query()->where('uuid', $uuid)->where('employer_id', $this->user($request)->getKey())->first()
            ?? throw new NotFoundHttpException('این درخواست پیدا نشد.');

        try {
            $requests->reveal($this->user($request), $bankRequest);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['bank' => $exception->getMessage()]);
        }

        return to_route('jobs.talent.requests')->with('contact_shown', $uuid);
    }

    public function buy(Request $request, ResumeBankCheckout $checkout): RedirectResponse
    {
        $user = $this->user($request);
        $company = $this->company($request) ?? throw new NotFoundHttpException;

        try {
            $package = $checkout->open($company);

            if (PaymentSource::requested($request) === PaymentSource::Wallet) {
                $checkout->payFromWallet($user, $package);

                return to_route('jobs.talent.index')->with('status', 'بسته خریده شد و '.PersianDigits::from($package->credits).' درخواست تماس به اعتبارتان اضافه شد.');
            }

            return redirect()->away($checkout->startGateway($package, $user->mobile)->redirectUrl);
        } catch (InsufficientWalletBalance $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return back()->withErrors(['payment' => PaymentGatewayUnavailable::USER_MESSAGE]);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
    }

    public function callback(Request $request, ResumeBankCheckout $checkout, PaymentGateway $gateway): RedirectResponse
    {
        $package = BankPackage::query()->where('gateway_authority', (string) $request->query('Authority'))->with('company')->firstOrFail();

        if ($package->status === PaymentStatus::Paid) {
            return to_route('jobs.talent.index')->with('status', 'این بسته پیش‌تر پرداخت شده است.');
        }

        $fail = function (string $reason) use ($package): RedirectResponse {
            $package->forceFill(['status' => PaymentStatus::Failed])->save();

            return to_route('jobs.talent.index')->withErrors(['payment' => $reason]);
        };

        if ((string) $request->query('Status') !== 'OK') {
            return $fail('پرداخت توسط شما لغو شد.');
        }

        $verification = $gateway->verify((string) $package->gateway_authority, $package->price());

        if (! $verification->successful) {
            return $fail((string) $verification->failureReason);
        }

        $checkout->complete($package, $verification->referenceId ?? '');

        return to_route('jobs.talent.index')->with('status', 'بسته خریده شد و '.PersianDigits::from($package->credits).' درخواست تماس به اعتبارتان اضافه شد.');
    }

    /** @return array{charging: bool, credits: int, price: Money, packageSize: int, replyDays: int} */
    private function billing(Company $company): array
    {
        return [
            'charging' => $this->pricing->bankCharging(),
            'credits' => $this->bank->credits($company),
            'price' => $this->pricing->bankPrice(),
            'packageSize' => $this->pricing->bankCredits(),
            'replyDays' => $this->pricing->bankReplyDays(),
        ];
    }

    /** شرکت تأییدشده کاربر؛ بانک رزومه فقط برای کارفرمای تأییدشده است. */
    private function company(Request $request): ?Company
    {
        $company = Company::query()->where('user_id', $this->user($request)->getKey())->first();

        return $company !== null && $company->isListed() ? $company : null;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
