<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain;

use App\Models\User;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک خرید خدمت مشاوره.
 *
 * @property int $id
 * @property string $uuid
 * @property int $service_id
 * @property string|null $report_uuid
 * @property int $buyer_id
 * @property int $consultant_id
 * @property int $price_toman
 * @property int $commission_toman
 * @property string $need
 * @property list<string> $proposed_times
 * @property string|null $city
 * @property bool $share_mobile
 * @property OrderStatus $status
 * @property PaymentSource|null $payment_source
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property string|null $escrow_uuid
 * @property Carbon|null $paid_at
 * @property string|null $scheduled_for
 * @property string|null $meeting_link
 * @property Carbon|null $accepted_at
 * @property Carbon|null $due_at
 * @property array{notes?: array<string, string>, summary?: string}|null $review
 * @property string|null $follow_up_question
 * @property string|null $follow_up_answer
 * @property Carbon|null $follow_up_asked_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $disputed_at
 * @property string|null $dispute_reason
 * @property Carbon|null $closed_at
 * @property string|null $close_note
 * @property int|null $closed_by
 * @property int $refunded_toman
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ConsultingService $service
 * @property-read User $buyer
 * @property-read User $consultant
 * @property-read Collection<int, ConsultingMessage> $messages
 */
final class ConsultingOrder extends Model
{
    protected $fillable = [
        'uuid',
        'service_id',
        'report_uuid',
        'buyer_id',
        'consultant_id',
        'price_toman',
        'need',
        'proposed_times',
        'city',
        'share_mobile',
        'status',
    ];

    /** @return BelongsTo<ConsultingService, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ConsultingService::class, 'service_id');
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant_id');
    }

    /** @return HasMany<ConsultingMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ConsultingMessage::class, 'order_id')->orderBy('id');
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isParty(int $userId): bool
    {
        return $userId === $this->buyer_id || $userId === $this->consultant_id;
    }

    public function isReportReview(): bool
    {
        return $this->report_uuid !== null;
    }

    /**
     * یادداشت‌های بررسی‌کننده به ترتیب بخش‌های گزارش.
     *
     * @return array<string, string>
     */
    public function reviewNotes(): array
    {
        return array_filter($this->review['notes'] ?? [], static fn (string $note): bool => trim($note) !== '');
    }

    public function reviewSummary(): ?string
    {
        return $this->review['summary'] ?? null;
    }

    /** بررسی گزارشی که پذیرفته شده و مهلت تحویلش گذشته. */
    public function isOverdue(): bool
    {
        return $this->status === OrderStatus::Accepted && $this->due_at !== null && $this->due_at->isPast();
    }

    /** کلید امانت در دفتر کل؛ هر درخواست فقط یک امانت دارد. */
    public function escrowKey(): string
    {
        return 'consulting.order:'.$this->uuid;
    }

    protected function casts(): array
    {
        return [
            'proposed_times' => 'array',
            'share_mobile' => 'boolean',
            'status' => OrderStatus::class,
            'payment_source' => PaymentSource::class,
            'paid_at' => 'datetime',
            'accepted_at' => 'datetime',
            'due_at' => 'datetime',
            'review' => 'array',
            'follow_up_asked_at' => 'datetime',
            'delivered_at' => 'datetime',
            'disputed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
