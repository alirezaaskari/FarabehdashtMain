<x-layouts.public :seo="$seo" active="consultants">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['مشاوران', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="consultants-index" title="مشاوران بهداشت حرفه‌ای"
                   lede="مشاورانی که نقششان در فرابهداشت تأیید شده و صفحه‌شان پیش از انتشار بازبینی شده است. حوزه تخصص، شهر و پاسخ‌هایی که در «پرسش از متخصص» داده‌اند را ببینید." />

    <x-page-help topic="consultants" class="mt-5" />

    <form method="GET" action="{{ route('consulting.index') }}" class="mt-8 flex flex-wrap items-end gap-3">
        @if ($terms !== [])
            <div class="w-full sm:w-64">
                <label for="domain" class="mb-2 block text-label font-semibold text-ink">حوزه تخصص</label>
                <select id="domain" name="domain"
                        class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                    <option value="">همه حوزه‌ها</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->slug }}" @selected($domain?->slug === $term->slug)>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="w-full sm:w-64">
            <label for="province" class="mb-2 block text-label font-semibold text-ink">استان</label>
            <select id="province" name="province"
                    class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                <option value="">همه استان‌ها</option>
                @foreach ($provinces as $key => $name)
                    <option value="{{ $key }}" @selected($province === $key)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <x-button type="submit" variant="secondary" icon="search">نمایش</x-button>
    </form>

    <div class="mt-6">
        @if ($profiles->isEmpty())
            <x-empty-state art="empty-consultants-index" icon="user"
                           title="{{ $domain || $province ? 'در این حوزه یا استان هنوز مشاوری صفحه ندارد' : 'هنوز صفحه مشاوری منتشر نشده' }}"
                           description="مشاوران پس از تأیید نقش، صفحه‌شان را از میزکار می‌سازند. پرسش تخصصی‌تان را تا آن وقت در «پرسش از متخصص» بپرسید.">
                @if (Route::has('expert.index'))
                    <x-slot:action>
                        <x-button :href="route('expert.index')" variant="primary">پرسش از متخصص</x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <ul class="grid list-none gap-4 ps-0 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($profiles as $profile)
                    @php $photo = $profile->photo_id ? ($photos[$profile->photo_id] ?? null) : null; @endphp
                    <li>
                        <a href="{{ route('consulting.show', $profile->slug) }}"
                           class="flex h-full min-h-touch items-start gap-4 rounded-xl border border-line bg-surface px-5 py-5 no-underline hover:bg-surface-2 hover:no-underline">
                            @if ($photo)
                                <img src="{{ $photo->url }}" alt="" width="64" height="64" loading="lazy"
                                     class="size-16 shrink-0 rounded-full object-cover">
                            @else
                                <span aria-hidden="true" class="flex size-16 shrink-0 items-center justify-center rounded-full bg-primary-soft text-h3 text-on-primary-soft">{{ mb_substr((string) $profile->display_name, 0, 1) }}</span>
                            @endif
                            <span class="flex min-w-0 flex-col gap-1">
                                <span class="text-h4 text-ink">{{ $profile->display_name }}</span>
                                <span class="text-copy text-body">{{ $profile->headline }}</span>
                                <span class="text-note text-muted">{{ $presenter->place($profile->province, $profile->city) }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $profiles->links() }}</div>
        @endif
    </div>

</x-layouts.public>
