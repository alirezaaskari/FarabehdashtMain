@php use App\Support\PersianDigits; @endphp

<x-layouts.workspace :title="'بازبینی — '.($report->title ?? 'گزارش')"
                     heading="بازبینی و صدور"
                     lede="پیش‌نمایش را ببینید؛ پس از صدور، گزارش منجمد می‌شود و برای اصلاح باید نسخه تازه صادر کنید."
                     nav="reports">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['گزارش‌ها', route('reports.index')], [$report->title ?? 'گزارش', null]]" />
    </x-slot:breadcrumb>

    @include('reports::partials.steps', ['current' => $step, 'report' => $report])

    @if ($errors->has('issue'))
        <x-alert tone="error" class="mb-6">{{ $errors->first('issue') }}</x-alert>
    @endif

    @if (session('entitlement'))
        <x-alert tone="caution" class="mb-6">{{ session('entitlement') }}</x-alert>
    @endif

    @if ($data === null)
        <x-alert tone="error" title="منبع این گزارش در دسترس نیست">
            پروژه یا محاسبه‌ای که این گزارش از آن ساخته شده دیگر پیدا نمی‌شود. این پیش‌نویس را حذف کنید و گزارش تازه‌ای بسازید.
        </x-alert>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat label="سطر نتیجه" :value="PersianDigits::from(count($data->measurements))" :numeric="false" />
            <x-stat label="تجهیز ضمیمه" :value="PersianDigits::from(count($data->equipment))" :numeric="false" />
            <x-stat label="نسخه فرمول" :value="PersianDigits::from(count($data->formulas()))" :numeric="false" />
        </div>

        <x-card class="mt-6" title="پیش‌نمایش">
            <p class="text-copy text-muted">
                همان قالب و همان داده‌ای که صادر می‌شود، بدون شناسه رهگیری و با نشان «پیش‌نمایش» روی هر صفحه.
            </p>
            <div class="mt-4">
                <x-button :href="route('reports.preview', $report->uuid)" variant="secondary" icon="file" target="_blank" rel="noopener">
                    باز کردن پیش‌نمایش PDF
                </x-button>
            </div>
        </x-card>

        <form method="POST" action="{{ route('reports.issue', $report->uuid) }}" class="mt-6">
            @csrf

            @if ($blocking !== [])
                <x-card tone="caution" title="تجهیز بدون کالیبراسیون معتبر">
                    <ul class="list-inside list-disc text-copy">
                        @foreach ($blocking as $item)
                            <li>{{ $item->name }} — {{ $item->calibrationStatus }}@if ($item->validUntil) (اعتبار تا {{ $item->validUntil }})@endif</li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-copy">
                        می‌توانید گزارش را صادر کنید، ولی تأییدتان در دفتر رویداد ثبت و روی خود گزارش چاپ می‌شود.
                    </p>
                    <label class="mt-4 flex min-h-touch cursor-pointer items-center gap-3">
                        <input type="checkbox" name="acknowledge_calibration" value="1" class="size-5 shrink-0 accent-primary">
                        <span class="text-label font-bold">می‌دانم و با همین شرایط گزارش را صادر می‌کنم</span>
                    </label>
                </x-card>
            @endif

            <x-card class="mt-6" title="صدور گزارش">
                @if ($decision->allowed())
                    <p class="text-copy text-muted">
                        با صدور، شناسه رهگیری ساخته می‌شود، PDF یک بار ساخته و هش آن ثبت می‌شود و صفحه تأیید اصالت فعال می‌شود.
                    </p>
                    <div class="mt-4">
                        <x-button type="submit" variant="primary" icon="check">صدور گزارش</x-button>
                    </div>
                @else
                    <x-permission-notice :title="$decision->message ?: 'صدور گزارش به اشتراک حرفه‌ای نیاز دارد'"
                                         description="پیش‌نویس و پیش‌نمایش برای همه آزاد است؛ صدور گزارش با شناسه رهگیری و صفحه تأیید اصالت، امکان اشتراک حرفه‌ای است.">
                        @if ($decision->upgradeUrl)
                            <x-slot:action>
                                <x-button :href="$decision->upgradeUrl" variant="primary">دیدن اشتراک حرفه‌ای</x-button>
                            </x-slot:action>
                        @endif
                    </x-permission-notice>
                @endif
            </x-card>
        </form>
    @endif

</x-layouts.workspace>
