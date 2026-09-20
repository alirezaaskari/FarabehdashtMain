@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="تقویم الزامات پایش" heading="تقویم الزامات پایش">

    <div class="max-w-3xl">

        <nav aria-label="مسیر صفحه" class="mb-4 text-sm">
            <a href="{{ route('projects.index') }}"
               class="inline-flex h-touch items-center text-primary">پروژه‌ها</a>
            <span class="text-muted"> / تقویم پایش</span>
        </nav>

        <p class="mt-1.5 text-sm text-muted">
            تاریخ‌هایی که خودتان ثبت کرده‌اید، کنار هم: انقضای کالیبراسیون تجهیزات و
            دورهای اندازه‌گیری. موارد گذشته هم نمایش داده می‌شوند.
        </p>

        @if ($entries === [])
            <x-empty-state class="mt-8"
                           icon="calendar"
                           title="تاریخی برای نمایش نیست"
                           description="با ثبت اعتبار کالیبراسیون تجهیزات یا تاریخ دورهای پروژه، این تقویم پر می‌شود." />
        @else
            <ul class="mt-8 flex flex-col gap-3">
                @foreach ($entries as $entry)
                    <li class="flex items-start justify-between gap-4 rounded-xl border border-line bg-surface p-4">
                        <div>
                            <span class="flex items-center gap-2">
                                <x-icon :name="$entry->kind === 'calibration' ? 'badge' : 'calendar'"
                                        :size="16" class="text-muted" />
                                <span class="font-bold text-ink">{{ $entry->title }}</span>
                            </span>
                            <span class="mt-1 block text-xs text-muted">{{ $entry->detail }}</span>
                        </div>

                        <div class="shrink-0 text-end">
                            <span class="block text-sm font-semibold text-ink">
                                {{ JalaliDate::short($entry->dueOn) }}
                            </span>
                            <x-badge :tone="$entry->tone()" class="mt-1">
                                @if ($entry->overdue)
                                    گذشته
                                @else
                                    @fa($entry->daysAway()) روز مانده
                                @endif
                            </x-badge>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-disclaimer class="mt-8" size="sm">
            این تقویم فقط تاریخ‌های ثبت‌شده خودتان را نشان می‌دهد و الزام قانونی پایش را
            تعیین نمی‌کند. دوره و دامنه پایش بر عهده کارشناس است.
        </x-disclaimer>

    </div>

</x-layouts.workspace>
