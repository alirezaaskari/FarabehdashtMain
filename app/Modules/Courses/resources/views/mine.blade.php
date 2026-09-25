<x-layouts.workspace title="دوره‌های من"
                     heading="دوره‌های من"
                     lede="دوره‌هایی که در آن‌ها ثبت‌نام کرده‌اید؛ دوره نیمه‌تمام بالای فهرست است."
                     nav="my-courses">

    @if ($enrollments->isEmpty())
        <x-empty-state icon="book"
                       title="هنوز در دوره‌ای ثبت‌نام نکرده‌اید"
                       description="دوره‌هایی که می‌خرید این‌جا می‌مانند و از همان جلسه‌ای که رها کرده‌اید ادامه می‌دهید.">
            @if (Route::has('courses.index'))
                <x-slot:action>
                    <x-button :href="route('courses.index')" variant="primary">دیدن دوره‌ها</x-button>
                </x-slot:action>
            @endif
        </x-empty-state>
    @else
        <ul class="flex list-none flex-col gap-3.5 ps-0">
            @foreach ($enrollments as $enrollment)
                @php
                    $total = (int) $enrollment->course->approved_sessions_count;
                    $done = min((int) $enrollment->progress_count, $total);
                    $percent = $total > 0 ? intdiv($done * 100, $total) : 0;
                @endphp

                <li class="flex flex-col gap-4 rounded-xl border border-line bg-surface p-5 md:flex-row md:items-center">
                    <div class="min-w-0 grow">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2 class="text-h4 text-ink">{{ $enrollment->course->title }}</h2>
                            @if ($enrollment->completed_at)
                                <x-badge tone="primary">تمام‌شده</x-badge>
                            @endif
                        </div>

                        <p class="mt-1.5 text-label text-muted">
                            @fa($done) از @fa($total) جلسه
                        </p>

                        <div class="mt-3 h-2 w-full max-w-md overflow-hidden rounded-full bg-surface-2"
                             role="progressbar" aria-label="پیشرفت دوره"
                             aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>

                    <x-button :href="route('courses.learn', $enrollment->course)"
                              :variant="$enrollment->completed_at ? 'secondary' : 'primary'"
                              class="shrink-0">
                        {{ $enrollment->completed_at ? 'مرور دوره' : ($done > 0 ? 'ادامه یادگیری' : 'شروع دوره') }}
                    </x-button>
                </li>
            @endforeach
        </ul>
    @endif

</x-layouts.workspace>
