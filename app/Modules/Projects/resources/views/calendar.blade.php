@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="تقویم الزامات پایش"
                     heading="تقویم الزامات پایش"
                     lede="تاریخ‌هایی که خودتان ثبت کرده‌اید، کنار هم: انقضای کالیبراسیون تجهیزات و
                           دورهای اندازه‌گیری. موارد گذشته هم نمایش داده می‌شوند."
                     active="tools"
                     nav="calendar" help="calendar">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['پروژه‌های اندازه‌گیری', route('projects.index')],
            ['تقویم الزامات پایش', null],
        ]" />
    </x-slot:breadcrumb>

    @if ($entries === [])
        <x-empty-state icon="calendar"
                       title="تاریخی برای نمایش نیست"
                       description="با ثبت اعتبار کالیبراسیون تجهیزات یا تاریخ دورهای پروژه، این تقویم پر می‌شود.">
            <x-slot:action>
                <x-button :href="route('projects.equipment.index')" variant="primary" size="sm">
                    رفتن به دفترچه تجهیزات
                </x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <x-data-table :headers="['مورد', 'نوع', 'توضیح', 'سررسید', 'وضعیت']"
                      caption="برنامه پایش و سررسید کالیبراسیون">
            @foreach ($entries as $entry)
                <tr>
                    <td class="font-semibold text-ink">{{ $entry->title }}</td>
                    <td>
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon :name="$entry->kind === 'calibration' ? 'badge' : 'calendar'"
                                    :size="15" class="text-muted" />
                            {{ $entry->kind === 'calibration' ? 'کالیبراسیون' : 'دور اندازه‌گیری' }}
                        </span>
                    </td>
                    <td>{{ $entry->detail }}</td>
                    <td>{{ JalaliDate::short($entry->dueOn) }}</td>
                    <td>
                        <x-badge :tone="$entry->tone()">
                            @if ($entry->overdue)
                                گذشته
                            @else
                                @fa($entry->daysAway()) روز مانده
                            @endif
                        </x-badge>
                    </td>
                </tr>
            @endforeach

            <x-slot:footnote>
                سررسیدها از داده خود شما می‌آیند و با ثبت تاریخ تازه در تجهیزات یا پروژه‌ها به‌روز می‌شوند.
            </x-slot:footnote>
        </x-data-table>
    @endif

    <x-disclaimer class="mt-6">
        این تقویم فقط تاریخ‌های ثبت‌شده خودتان را نشان می‌دهد و الزام قانونی پایش را
        تعیین نمی‌کند. دوره و دامنه پایش بر عهده کارشناس است.
    </x-disclaimer>

</x-layouts.workspace>
