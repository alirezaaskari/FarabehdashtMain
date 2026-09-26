@php use App\Support\PersianNumber; @endphp

<x-layouts.workspace art="character.measure" title="پروژه‌های اندازه‌گیری"
                     heading="پروژه‌های اندازه‌گیری"
                     lede="هر پروژه ایستگاه‌ها و دورهای خودش را دارد و دو دورش با هم مقایسه می‌شوند."
                     active="tools"
                     nav="projects" help="projects">

    <x-slot:actions>
        <x-button :href="route('projects.equipment.index')" variant="secondary" icon="badge">
            دفترچه تجهیزات
        </x-button>
        <x-button :href="route('projects.calendar')" variant="secondary" icon="calendar">
            تقویم پایش
        </x-button>
    </x-slot:actions>

    @if (session('status'))
        <x-alert tone="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    {{-- شمارش، عدد در جمله فارسی است، نه مقدار اندازه‌گیری: ارقام فارسی. --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['پروژه‌ها', $totals['projects']],
            ['ایستگاه‌ها', $totals['stations']],
            ['دورهای اندازه‌گیری', $totals['rounds']],
            ['قرائت‌های ثبت‌شده', $totals['readings']],
        ] as [$label, $count])
            <x-stat :label="$label" :value="PersianNumber::format($count)" :numeric="false" />
        @endforeach
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1.5fr_1fr]">

        <div class="min-w-0">
            @if ($projects->isEmpty())
                <x-empty-state icon="file"
                               title="هنوز پروژه‌ای ندارید"
                               description="با فرم کناری اولین پروژه‌تان را بسازید؛ انتخاب صنعت، ایستگاه‌های پیشنهادی را از پیش می‌سازد." />
            @else
                <x-data-table :headers="['پروژه', 'صنعت', 'ایستگاه', 'دور', 'قرائت', 'وضعیت', 'اقدام']"
                              caption="فهرست پروژه‌های اندازه‌گیری">
                    @foreach ($projects as $project)
                        <tr>
                            <td class="font-semibold text-ink">{{ $project->title }}</td>
                            <td>{{ $project->industry?->label() ?? 'بدون صنعت' }}</td>
                            <td>@fa($project->stations_count)</td>
                            <td>@fa($project->rounds_count)</td>
                            <td>@fa($project->readings_count)</td>
                            <td><x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge></td>
                            <td>
                                <a href="{{ route('projects.show', $project->uuid) }}"
                                   class="inline-flex min-h-touch items-center font-semibold">باز کردن</a>
                            </td>
                        </tr>
                    @endforeach

                    <x-slot:footnote>
                        شمارش ایستگاه و دور، وضعیت لحظه فعلی پروژه است و با ثبت هر قرائت تازه می‌شود.
                    </x-slot:footnote>
                </x-data-table>
            @endif

            <x-disclaimer class="mt-6">
                فهرست ایستگاه‌های هر قالب صنعتی پیشنهادی و عمومی است. تعیین دامنه واقعی پایش بر
                عهده کارشناس و بر اساس شناسایی عوامل زیان‌آور همان واحد است.
            </x-disclaimer>
        </div>

        <x-card title="پروژه تازه">
            <form method="POST" action="{{ route('projects.store') }}" class="flex flex-col gap-4">
                @csrf

                <x-field name="title" label="عنوان پروژه" required
                         :error="$errors->first('title')"
                         hint="مثلاً «پایش صدای سالن ریخته‌گری — بهار ۱۴۰۵»" />

                <x-field name="client_name" label="کارفرما (اختیاری)" :error="$errors->first('client_name')" />

                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-label font-semibold text-ink">صنعت (اختیاری)</span>
                    <select name="industry"
                            class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                        <option value="">بدون قالب — ایستگاه‌ها را خودم می‌سازم</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->industry->value }}">{{ $template->label() }}</option>
                        @endforeach
                    </select>
                    <span class="text-note text-muted">
                        انتخاب صنعت، ایستگاه‌های پیشنهادی را از پیش می‌سازد. هر کدام قابل حذف و تغییرند.
                    </span>
                </label>

                <x-button type="submit" variant="primary" icon="plus" block>ساخت پروژه</x-button>
            </form>
        </x-card>

    </div>

</x-layouts.workspace>
