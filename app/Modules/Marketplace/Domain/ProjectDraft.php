<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use Illuminate\Support\Carbon;

/**
 * آنچه کارفرما در فرم پروژه فرستاده، پس از اعتبارسنجی.
 */
final readonly class ProjectDraft
{
    public function __construct(
        public string $title,
        public string $service,
        public ?string $province,
        public ?string $city,
        public bool $remote,
        public int $budgetMinToman,
        public int $budgetMaxToman,
        public ?Carbon $wantedBy,
        public string $description,
        public ?string $clientName,
        public bool $showClientName,
        public bool $private,
    ) {}

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return [
            'title' => $this->title,
            'service' => $this->service,
            'province' => $this->remote ? null : $this->province,
            'city' => $this->remote ? null : $this->city,
            'remote' => $this->remote,
            'budget_min_toman' => $this->budgetMinToman,
            'budget_max_toman' => $this->budgetMaxToman,
            'wanted_by' => $this->wantedBy,
            'description' => $this->description,
            'client_name' => $this->clientName,
            'show_client_name' => $this->showClientName && $this->clientName !== null,
            'is_private' => $this->private,
        ];
    }
}
