@php
    use App\Modules\Identity\Domain\Enums\ProfileStatus;
@endphp

<x-layouts.workspace art="character.idcard" title="نقش‌ها و پروفایل‌ها" heading="نقش‌ها و پروفایل‌ها" nav="profiles" help="profiles">
    <p class="max-w-3xl text-lede text-muted">
        هر نقش یک پروفایل روی همین حساب است. فعال‌کردن نقش جدید، حساب تازه‌ای نمی‌سازد و
        غیرفعال‌کردن آن هیچ داده‌ای را حذف نمی‌کند.
    </p>

    @if (session('status'))
        <div class="mt-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    <div class="mt-8 overflow-hidden rounded-xl border border-line divide-y divide-line">
        @foreach ($types as $type)
            @php
                $profile = $user->profileFor($type);
                $status = $profile?->status;
            @endphp

            <div class="flex flex-col gap-4 bg-surface p-5 md:flex-row md:items-center md:px-6">
                <div class="grow">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="text-h4 font-semibold text-ink">{{ $type->label() }}</span>

                        @if ($status)
                            <x-badge :tone="$status->badgeTone()">{{ $status->label() }}</x-badge>
                        @else
                            <x-badge tone="neutral">فعال نشده</x-badge>
                        @endif
                    </div>

                    <p class="mt-1.5 text-label text-muted">{{ $type->description() }}</p>

                    @if ($profile?->rejection_note)
                        <p class="mt-2 text-label text-danger">یادداشت مدیر: {{ $profile->rejection_note }}</p>
                    @endif
                </div>

                <div class="flex shrink-0 gap-2.5">
                    @if ($status === ProfileStatus::Active)
                        <form method="POST"
                              action="{{ route('identity.profiles.deactivate', $type->value) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm">غیرفعال‌کردن</x-button>
                        </form>
                    @elseif ($status === ProfileStatus::Pending)
                        <x-button variant="secondary" size="sm" disabled>در صف بررسی</x-button>
                    @else
                        <form method="POST"
                              action="{{ route('identity.profiles.activate', $type->value) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm">درخواست فعال‌سازی</x-button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-7 grid gap-6 lg:grid-cols-2">
        <x-card title="قواعد ثابت">
            <ol class="flex flex-col gap-2 ps-5 text-label text-body">
                <li>هر پروفایل تجاری پیش از فعال‌شدن نیازمند تأیید مدیر است.</li>
                <li>غیرفعال‌کردن پروفایل، محتوا و سوابق مالی شما را حفظ می‌کند.</li>
                <li>میزکار فقط بخش‌های مربوط به پروفایل‌های فعال را نشان می‌دهد.</li>
                <li>نقش مدیر از این مسیر فعال نمی‌شود.</li>
            </ol>
        </x-card>

        <x-card title="دسترسی‌های فعلی شما"
                subtitle="اتحاد مجوزهای پایه با مجوزهای پروفایل‌های فعال.">
            <ul class="flex flex-wrap gap-2">
                @foreach ($permissions as $permission)
                    <li><x-badge tone="neutral">{{ $permissionLabels[$permission] ?? $permission }}</x-badge></li>
                @endforeach
            </ul>
        </x-card>
    </div>

    <div class="mt-7">
        <x-disclaimer size="sm">
            نشان‌ها و گواهی‌های داخلی فرابهداشت مدرک رسمی یا مجوز حرفه‌ای محسوب نمی‌شوند.
        </x-disclaimer>
    </div>
</x-layouts.workspace>
