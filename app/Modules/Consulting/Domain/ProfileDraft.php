<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain;

/**
 * یک نسخه کامل از صفحه مشاور: همانی که مشاور می‌فرستد و مدیر تأیید می‌کند.
 */
final readonly class ProfileDraft
{
    /**
     * @param  list<int>  $domainIds  برچسب‌های دسته‌بندی «حوزه بهداشت حرفه‌ای»
     */
    public function __construct(
        public string $slug,
        public string $displayName,
        public string $headline,
        public string $bio,
        public string $province,
        public string $city,
        public ?string $experience,
        public ?string $education,
        public array $domainIds,
        public ?int $photoId,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            slug: (string) ($data['slug'] ?? ''),
            displayName: (string) ($data['display_name'] ?? ''),
            headline: (string) ($data['headline'] ?? ''),
            bio: (string) ($data['bio'] ?? ''),
            province: (string) ($data['province'] ?? ''),
            city: (string) ($data['city'] ?? ''),
            experience: self::text($data['experience'] ?? null),
            education: self::text($data['education'] ?? null),
            domainIds: array_values(array_unique(array_map(intval(...), (array) ($data['domain_ids'] ?? [])))),
            photoId: isset($data['photo_id']) ? (int) $data['photo_id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'display_name' => $this->displayName,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'province' => $this->province,
            'city' => $this->city,
            'experience' => $this->experience,
            'education' => $this->education,
            'domain_ids' => $this->domainIds,
            'photo_id' => $this->photoId,
        ];
    }

    private static function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
