<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Modules\Jobs\Domain\Enums\EmploymentType;

/**
 * یک نسخه کامل از آگهی: همانی که کارفرما می‌فرستد و مدیر تأیید می‌کند.
 */
final readonly class PostingDraft
{
    /** @param  list<int>  $skillIds  برچسب‌های دسته‌بندی «مهارت شغلی» (DEC-71) */
    public function __construct(
        public string $title,
        public string $province,
        public string $city,
        public EmploymentType $employmentType,
        public int $minExperienceYears,
        public ?int $salaryMinToman,
        public ?int $salaryMaxToman,
        public string $description,
        public array $skillIds,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            title: trim((string) ($data['title'] ?? '')),
            province: (string) ($data['province'] ?? ''),
            city: (string) ($data['city'] ?? ''),
            employmentType: EmploymentType::tryFrom((string) ($data['employment_type'] ?? '')) ?? EmploymentType::FullTime,
            minExperienceYears: max(0, (int) ($data['min_experience_years'] ?? 0)),
            salaryMinToman: self::amount($data['salary_min_toman'] ?? null),
            salaryMaxToman: self::amount($data['salary_max_toman'] ?? null),
            description: trim((string) ($data['description'] ?? '')),
            skillIds: array_values(array_unique(array_map(intval(...), (array) ($data['skill_ids'] ?? [])))),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'province' => $this->province,
            'city' => $this->city,
            'employment_type' => $this->employmentType->value,
            'min_experience_years' => $this->minExperienceYears,
            'salary_min_toman' => $this->salaryMinToman,
            'salary_max_toman' => $this->salaryMaxToman,
            'description' => $this->description,
            'skill_ids' => $this->skillIds,
        ];
    }

    private static function amount(mixed $value): ?int
    {
        return $value === null || $value === '' || (int) $value <= 0 ? null : (int) $value;
    }
}
