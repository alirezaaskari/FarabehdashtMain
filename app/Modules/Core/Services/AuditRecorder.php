<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Contracts\AuditTrail;
use App\Modules\Core\Domain\AuditLog;
use App\Support\Audit\AuditEntry;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;

/**
 * نوشتن ردیف دفتر رویداد.
 *
 * بازیگر و نشانی IP اگر در ورودی نیامده باشند از درخواست جاری برداشته می‌شوند،
 * تا فراخوان مجبور نباشد هر بار آن‌ها را دست به دست بدهد. در صف و دستور کنسول
 * درخواستی وجود ندارد و مقدارها null می‌مانند — یعنی «سیستم».
 */
final readonly class AuditRecorder implements AuditTrail
{
    public function __construct(
        private AuthFactory $auth,
        private Request $request,
    ) {}

    public function record(AuditEntry $entry): void
    {
        AuditLog::query()->create([
            'action' => $entry->action,
            'subject_type' => $entry->subjectType,
            'subject_id' => $entry->subjectId === null ? null : (string) $entry->subjectId,
            'actor_id' => $entry->actorId ?? $this->currentActorId(),
            'before' => $entry->before === [] ? null : $entry->before,
            'after' => $entry->after === [] ? null : $entry->after,
            'context' => $entry->context === [] ? null : $entry->context,
            'ip' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 255) ?: null,
        ]);
    }

    private function currentActorId(): ?int
    {
        $id = $this->auth->guard()->id();

        return is_numeric($id) ? (int) $id : null;
    }
}
