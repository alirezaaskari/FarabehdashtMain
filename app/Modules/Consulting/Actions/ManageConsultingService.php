<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Models\User;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\ServiceKind;
use App\Modules\Consulting\Domain\Enums\ServiceStatus;
use App\Modules\Consulting\Events\ConsultingServiceChanged;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * مشاور خدمت‌هایش را می‌سازد و برای تأیید مدیر می‌فرستد.
 *
 * هر ویرایش خدمت منتشرشده آن را دوباره به صف تأیید می‌برد و تا تأیید از
 * فروش بیرون است؛ قیمت یا شرحی که مدیر ندیده، فروخته نمی‌شود. درخواست‌های
 * قبلی با قیمت همان لحظه خریدشان ادامه می‌دهند.
 */
final readonly class ManageConsultingService
{
    public function __construct(private Dispatcher $events) {}

    /**
     * @param  array{kind: ServiceKind, title: string, description: string, duration_minutes: int|null, price_toman: int, cities: list<string>}  $data
     */
    public function submit(User $user, array $data, ?ConsultingService $service = null): ConsultingService
    {
        $profile = ConsultantProfile::query()->where('user_id', $user->getKey())->first();

        if ($profile === null || ! $profile->isListed() || ! $user->can('consulting.services.manage')) {
            throw new RuntimeException('پیش از تعریف خدمت، صفحه عمومی شما باید منتشر شده باشد.');
        }

        if ($service !== null && $service->profile_id !== $profile->id) {
            throw new RuntimeException('این خدمت مال شما نیست.');
        }

        if ($service !== null && ! $service->status->isEditable()) {
            throw new RuntimeException('این خدمت در انتظار تأیید یا بیرون از فروش است و ویرایش نمی‌شود.');
        }

        // یک خدمت بررسی گزارش برای هر مشاور؛ خریدار از فهرست بررسی‌کننده‌ها انتخاب می‌کند و دو قیمت از یک نفر گیجش می‌کند.
        if ($data['kind'] === ServiceKind::ReportReview && $profile->services()
            ->where('kind', ServiceKind::ReportReview)
            ->where('status', '!=', ServiceStatus::Retired)
            ->when($service !== null, static fn ($query) => $query->whereKeyNot($service?->getKey()))
            ->exists()) {
            throw new RuntimeException('شما یک خدمت بررسی گزارش دارید؛ همان را ویرایش کنید.');
        }

        $service ??= new ConsultingService(['uuid' => (string) Str::uuid7(), 'profile_id' => $profile->id]);

        $service->forceFill([
            'kind' => $data['kind'],
            'title' => $data['title'],
            'description' => $data['description'],
            'duration_minutes' => $data['kind'] === ServiceKind::ReportReview ? null : $data['duration_minutes'],
            'price_toman' => $data['price_toman'],
            'cities' => $data['kind'] === ServiceKind::Visit ? $data['cities'] : null,
            'status' => ServiceStatus::Pending,
            'submitted_at' => Carbon::now(),
            'review_note' => null,
            'published_at' => null,
        ])->save();

        $this->events->dispatch(new ConsultingServiceChanged($service, (int) $user->getKey()));

        return $service;
    }

    public function retire(User $user, ConsultingService $service): ConsultingService
    {
        if ($service->profile->user_id !== $user->getKey()) {
            throw new RuntimeException('این خدمت مال شما نیست.');
        }

        $service->forceFill(['status' => ServiceStatus::Retired])->save();
        $this->events->dispatch(new ConsultingServiceChanged($service, (int) $user->getKey()));

        return $service;
    }

    public function approve(ConsultingService $service, int $adminId): ConsultingService
    {
        $this->assertPending($service);

        $service->forceFill([
            'status' => ServiceStatus::Published,
            'published_at' => Carbon::now(),
            'reviewed_by' => $adminId,
            'review_note' => null,
        ])->save();

        $this->events->dispatch(new ConsultingServiceChanged($service, $adminId));

        return $service;
    }

    public function reject(ConsultingService $service, int $adminId, string $note): ConsultingService
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('یادداشتی بنویسید که مشاور بداند چه چیزی را اصلاح کند.');
        }

        $this->assertPending($service);

        $service->forceFill([
            'status' => ServiceStatus::Rejected,
            'reviewed_by' => $adminId,
            'review_note' => $note,
        ])->save();

        $this->events->dispatch(new ConsultingServiceChanged($service, $adminId));

        return $service;
    }

    private function assertPending(ConsultingService $service): void
    {
        if ($service->status !== ServiceStatus::Pending) {
            throw new RuntimeException('این خدمت پیش‌تر بررسی شده است.');
        }
    }
}
