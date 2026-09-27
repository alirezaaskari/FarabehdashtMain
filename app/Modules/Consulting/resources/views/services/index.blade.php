@php use App\Modules\Consulting\Domain\Enums\ServiceStatus; @endphp

<x-layouts.workspace art="consulting-services" title="خدمت‌های من"
                     heading="خدمت‌های من"
                     lede="جلسه آنلاین یا بازدید حضوری که روی صفحه عمومی شما خریدنی است. هر خدمت پیش از فروش از تأیید مدیر می‌گذرد."
                     nav="consulting-services" help="consulting-services">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($profile === null || ! $profile->isListed())
        <x-alert tone="info" title="اول صفحه عمومی">
            خدمت روی صفحه عمومی شما فروخته می‌شود؛ پیش از تعریف خدمت، صفحه‌تان باید تأیید و منتشر شده باشد.
        </x-alert>
        <div class="mt-4">
            <x-button :href="route('consulting.profile.edit')" variant="primary">ساختن صفحه عمومی</x-button>
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-copy text-muted">کمیسیون فرابهداشت روی هر خدمت @fa($commissionPercent) درصد است؛ بقیه پس از پایان کار به کیف پول درآمد شما می‌رود.</p>
            <x-button :href="route('consulting.services.create')" variant="primary" icon="plus">خدمت تازه</x-button>
        </div>

        @if ($services->isEmpty())
            <x-empty-state art="empty-consulting-services" icon="list"
                           title="هنوز خدمتی تعریف نکرده‌اید"
                           description="مثلاً «جلسه آنلاین یک‌ساعته بررسی برنامه حفاظت شنوایی» یا «بازدید حضوری و اندازه‌گیری صدا در کارگاه»." />
        @else
            <ul class="flex list-none flex-col gap-3 ps-0">
                @foreach ($services as $service)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-h4 text-ink">{{ $service->title }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-note text-muted">
                                <x-badge :tone="$service->status === ServiceStatus::Published ? 'primary' : ($service->status === ServiceStatus::Rejected ? 'caution' : 'neutral')">{{ $service->status->label() }}</x-badge>
                                <span>{{ $service->kind->label() }}</span>
                                <span aria-hidden="true">·</span>
                                <span>{{ $service->price()->format() }}</span>
                            </p>
                            @if ($service->status === ServiceStatus::Rejected && $service->review_note)
                                <p class="mt-2 text-note text-caution">یادداشت مدیر: {{ $service->review_note }}</p>
                            @endif
                        </div>
                        @if ($service->status->isEditable())
                            <div class="flex flex-wrap gap-2">
                                <x-button :href="route('consulting.services.edit', $service->uuid)" variant="secondary" size="sm">ویرایش</x-button>
                                <form method="POST" action="{{ route('consulting.services.retire', $service->uuid) }}">
                                    @csrf
                                    <x-button type="submit" variant="ghost" size="sm">خروج از فروش</x-button>
                                </form>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

</x-layouts.workspace>
