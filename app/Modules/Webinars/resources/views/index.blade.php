@php use App\Support\JalaliDate; @endphp

<x-layouts.public title="رویداد و وبینار"
                  description="وبینارها و رویدادهای آموزشی بهداشت حرفه‌ای با زمان، مدرس و ظرفیت مشخص؛ رایگان یا با ثبت‌نام پولی."
                  :canonical="route('webinars.index')"
                  active="webinars">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['رویداد و وبینار', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="webinars-index" title="رویداد و وبینار"
                   lede="جلسه‌های زنده با زمان و ظرفیت مشخص. پس از ثبت‌نام، پیوند ورود از یک ساعت پیش از شروع در صفحه هر رویداد باز می‌شود." />

    <x-page-help topic="webinars" class="mt-5" />

    <div class="mt-8">
        @if ($upcoming->isEmpty())
            <x-empty-state art="empty-webinars-index" icon="clock"
                           title="فعلاً رویداد پیش‌رویی نداریم"
                           description="رویدادهای تازه این‌جا اعلام می‌شوند." />
        @else
            <ul class="flex list-none flex-col divide-y divide-line border-y border-line ps-0">
                @foreach ($upcoming as $webinar)
                    <li>
                        <a href="{{ route('webinars.show', $webinar->slug) }}"
                           class="flex min-h-touch flex-col gap-1 py-5 no-underline hover:no-underline md:flex-row md:items-center md:justify-between md:gap-6">
                            <span class="min-w-0">
                                <span class="block text-h4 text-ink">{{ $webinar->title }}</span>
                                <span class="mt-1 block text-note text-muted">
                                    {{ JalaliDate::longWithTime($webinar->starts_at) }} · {{ $webinar->instructor_name }}
                                </span>
                            </span>
                            <span class="shrink-0 text-label font-semibold text-ink">
                                {{ $webinar->isFree() ? 'رایگان' : $webinar->price()->format() }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($past->isNotEmpty())
            <h2 class="mt-12 text-h3 text-ink">برگزارشده</h2>
            <ul class="mt-4 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
                @foreach ($past as $webinar)
                    <li>
                        <a href="{{ route('webinars.show', $webinar->slug) }}"
                           class="flex min-h-touch flex-wrap items-center justify-between gap-3 py-3 no-underline hover:no-underline">
                            <span class="text-label text-ink">{{ $webinar->title }}</span>
                            <span class="text-note text-muted">
                                {{ JalaliDate::short($webinar->starts_at) }}@if ($webinar->recording_url) · ضبط موجود است @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

</x-layouts.public>
