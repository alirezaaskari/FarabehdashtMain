@php use App\Support\JalaliDate; @endphp

<x-layouts.public :title="'تاریخچه تغییرات '.$substance->name_fa"
                  :description="'تغییرات ثبت‌شده داده‌های '.$substance->name_fa.' در بانک مواد فرابهداشت، با تاریخ و دلیل.'"
                  :canonical="route('chemicals.history', $substance->slug)"
                  active="chemicals">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', Route::has('home') ? route('home') : '/'],
            ['بانک مواد شیمیایی', route('chemicals.index')],
            [$substance->name_fa, route('chemicals.show', $substance->slug)],
            ['تاریخچه تغییرات', null],
        ]" />
    </x-slot:breadcrumb>

    <x-page-header art="chemicals-history" :title="'تاریخچه تغییرات '.$substance->name_fa"
                   lede="هر بار که نام، شماره CAS، فرمول یا حد مواجهه این ماده عوض شده، این‌جا با تاریخ و دلیلش ثبت است. تازه‌ترین بالاست." />

    <x-page-help topic="chemicals-history" class="mt-5" />

    <div class="mt-8">
        @if ($entries === [])
            <x-empty-state art="empty-chem-history" icon="clock"
                           title="هنوز تغییری ثبت نشده"
                           description="داده‌های این ماده از زمان ورود به بانک همان است که در صفحه ماده می‌بینید." />
        @else
            <ol class="list-none divide-y divide-line border-y border-line p-0">
                @foreach ($entries as $entry)
                    <li class="py-5">
                        <h2 class="text-h4 text-ink">نسخه @fa($entry['version'])</h2>
                        <p class="mt-1 text-note text-muted">ثبت در {{ JalaliDate::short($entry['at']) }}</p>

                        @if ($entry['reason'])
                            <p class="mt-2 text-copy text-body">{{ $entry['reason'] }}</p>
                        @endif

                        @if ($entry['first'])
                            <p class="mt-2 text-note text-muted">ماده با همین داده‌ها به بانک اضافه شد.</p>
                        @else
                            <ul class="mt-3 flex flex-col gap-2">
                                @foreach ($entry['changes'] as $change)
                                    <li class="flex flex-wrap items-baseline gap-x-2 text-label">
                                        <span class="font-semibold text-ink">{{ $change['label'] }}:</span>
                                        @if ($change['before'] !== null)
                                            <del class="text-muted">@if ($change['ltr'])<bdi dir="ltr" data-numeric>{{ $change['before'] }}</bdi>@else{{ $change['before'] }}@endif</del>
                                            <span class="text-muted" aria-hidden="true">←</span>
                                        @endif
                                        @if ($change['after'] !== null)
                                            @if ($change['ltr'])
                                                <bdi class="font-semibold text-ink" dir="ltr" data-numeric>{{ $change['after'] }}</bdi>
                                            @else
                                                <span class="font-semibold text-ink">{{ $change['after'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">حذف شد</span>
                                        @endif
                                        @if ($change['before'] === null)
                                            <span class="text-muted">(افزوده شد)</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    <p class="mt-6">
        <x-button :href="route('chemicals.show', $substance->slug)" variant="secondary">بازگشت به صفحه ماده</x-button>
    </p>

</x-layouts.public>
