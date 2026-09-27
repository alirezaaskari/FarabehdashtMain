<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

/**
 * یک نسخه کامل از صفحه شرکت: همانی که کارفرما می‌فرستد و مدیر تأیید می‌کند.
 */
final readonly class CompanyDraft
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $industry,
        public string $size,
        public string $province,
        public string $city,
        public string $about,
        public ?int $logoId,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            slug: (string) ($data['slug'] ?? ''),
            name: trim((string) ($data['name'] ?? '')),
            industry: trim((string) ($data['industry'] ?? '')),
            size: (string) ($data['size'] ?? ''),
            province: (string) ($data['province'] ?? ''),
            city: (string) ($data['city'] ?? ''),
            about: trim((string) ($data['about'] ?? '')),
            logoId: isset($data['logo_id']) ? (int) $data['logo_id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'industry' => $this->industry,
            'size' => $this->size,
            'province' => $this->province,
            'city' => $this->city,
            'about' => $this->about,
            'logo_id' => $this->logoId,
        ];
    }

    public function withLogo(?int $logoId): self
    {
        return new self($this->slug, $this->name, $this->industry, $this->size, $this->province, $this->city, $this->about, $logoId);
    }
}
