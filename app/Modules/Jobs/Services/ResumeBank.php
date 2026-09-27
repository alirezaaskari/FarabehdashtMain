<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Services;

use App\Modules\Jobs\Domain\BankPackage;
use App\Modules\Jobs\Domain\BankRequest;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
use App\Modules\Jobs\Domain\Passport;
use App\Support\Passport\PassportEvidence;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\Config\Repository;

/**
 * خواندن بانک رزومه (۲۰-۵): کارت‌های ناشناس و اعتبار کارفرما.
 *
 * کارت فقط مهارت، شهر، سال‌های سابقه و بخش «ثبت‌شده در فرابهداشت» را دارد.
 * معرفی، سطرهای اظهاری و نشانی سطرهای ثبت‌شده نمی‌آیند، چون هر کدام ممکن
 * است نام یا کارفرمای فعلی را لو بدهد.
 */
final readonly class ResumeBank
{
    public function __construct(
        private SkillPassport $passports,
        private JobCatalog $catalog,
        private Repository $config,
    ) {}

    /**
     * @return list<array{token: string, user_id: int, place: string, years: int|null, verified: list<array{key: string, label: string, items: list<PassportEvidence>}>, verified_skills: list<TermData>, declared_skills: list<TermData>}>
     */
    public function search(?int $skillId, ?string $province, ?string $city, ?int $minYears, int $viewerId): array
    {
        $passports = Passport::query()
            ->where('in_bank', true)
            ->whereNotNull('bank_token')
            ->where('user_id', '!=', $viewerId)
            ->when($province !== null, static fn ($query) => $query->where('province', $province))
            ->when($city !== null, static fn ($query) => $query->where('city', $city))
            ->when($minYears !== null, static fn ($query) => $query->where('experience_years', '>=', $minYears))
            ->latest('bank_joined_at')
            ->limit((int) $this->config->get('jobs.bank.scan_max', 500))
            ->get();

        $cards = [];

        foreach ($passports as $passport) {
            $card = $this->card($passport);
            $ids = array_map(static fn (TermData $term): int => $term->id, [...$card['verified_skills'], ...$card['declared_skills']]);

            if ($skillId === null || in_array($skillId, $ids, true)) {
                $cards[] = $card;
            }
        }

        // پشتوانه ثبت‌شده بیشتر، بالاتر؛ بعد عضو تازه‌تر (ترتیب کوئری می‌ماند).
        usort($cards, static fn (array $a, array $b): int => count($b['verified_skills']) <=> count($a['verified_skills']));

        return $cards;
    }

    /**
     * کارت ناشناس یک عضو.
     *
     * @return array{token: string, user_id: int, place: string, years: int|null, verified: list<array{key: string, label: string, items: list<PassportEvidence>}>, verified_skills: list<TermData>, declared_skills: list<TermData>}
     */
    public function card(Passport $passport): array
    {
        $verified = array_map(static fn (array $section): array => [
            ...$section,
            'items' => array_map(static fn (PassportEvidence $item): PassportEvidence => new PassportEvidence(
                title: $item->title,
                earnedAt: $item->earnedAt,
                detail: $item->detail,
                count: $item->count,
                tags: $item->tags,
                score: $item->score,
            ), $section['items']),
        ], $this->passports->verified($passport->user_id));

        $verifiedSkills = $this->passports->verifiedSkills($passport->user_id, $verified);
        $verifiedIds = array_map(static fn (TermData $term): int => $term->id, $verifiedSkills);

        return [
            'token' => (string) $passport->bank_token,
            'user_id' => $passport->user_id,
            'place' => $this->catalog->place($passport->province, $passport->city),
            'years' => $passport->experience_years,
            'verified' => $verified,
            'verified_skills' => $verifiedSkills,
            'declared_skills' => array_values(array_filter(
                $this->passports->declaredSkills($passport),
                static fn (TermData $term): bool => ! in_array($term->id, $verifiedIds, true),
            )),
        ];
    }

    /** اعتبار باقی‌مانده: بسته‌های پرداخت‌شده منهای درخواست‌هایی که اعتبار نگه داشته‌اند. */
    public function credits(Company $company): int
    {
        $bought = (int) BankPackage::query()->paid()->where('company_id', $company->id)->sum('credits');
        $held = BankRequest::query()
            ->where('company_id', $company->id)
            ->where('charged', true)
            ->whereIn('status', BankRequestStatus::holding())
            ->count();

        return max(0, $bought - $held);
    }
}
