<x-layouts.workspace title="پروژه‌های اندازه‌گیری" heading="پروژه‌های اندازه‌گیری">

    <div class="max-w-4xl">

        <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm text-muted">
                    هر پروژه ایستگاه‌ها و دورهای خودش را دارد و دو دورش با هم مقایسه می‌شوند.
                </p>
            </div>

            <div class="flex gap-2">
                <x-button :href="route('projects.equipment.index')" variant="secondary" icon="badge">
                    دفترچه تجهیزات
                </x-button>
                <x-button :href="route('projects.calendar')" variant="secondary" icon="calendar">
                    تقویم پایش
                </x-button>
            </div>
        </header>

        @if (session('status'))
            <x-alert tone="success" class="mt-6">{{ session('status') }}</x-alert>
        @endif

        <x-card class="mt-8" title="پروژه تازه" :level="2">
            <form method="POST" action="{{ route('projects.store') }}" class="flex flex-col gap-4">
                @csrf

                <x-field name="title" label="عنوان پروژه" required
                         :error="$errors->first('title')"
                         hint="مثلاً «پایش صدای سالن ریخته‌گری — بهار ۱۴۰۵»" />

                <x-field name="client_name" label="کارفرما (اختیاری)" :error="$errors->first('client_name')" />

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-bold text-ink">صنعت (اختیاری)</span>
                    <select name="industry"
                            class="h-field rounded-md border border-line-strong bg-surface px-3 text-base text-ink">
                        <option value="">بدون قالب — ایستگاه‌ها را خودم می‌سازم</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->industry->value }}">{{ $template->label() }}</option>
                        @endforeach
                    </select>
                    <span class="text-xs text-muted">
                        انتخاب صنعت، ایستگاه‌های پیشنهادی را از پیش می‌سازد. هر کدام قابل حذف و تغییرند.
                    </span>
                </label>

                <x-button type="submit" variant="primary" icon="plus">ساخت پروژه</x-button>
            </form>
        </x-card>

        @if ($projects->isEmpty())
            <x-empty-state class="mt-8"
                           icon="file"
                           title="هنوز پروژه‌ای ندارید"
                           description="با فرم بالا اولین پروژه‌تان را بسازید." />
        @else
            <ul class="mt-8 flex flex-col gap-3">
                @foreach ($projects as $project)
                    <li>
                        <a href="{{ route('projects.show', $project->uuid) }}"
                           class="flex items-center justify-between gap-4 rounded-xl border border-line bg-surface
                                  p-4 no-underline hover:border-primary hover:no-underline">
                            <span>
                                <span class="block font-bold text-ink">{{ $project->title }}</span>
                                <span class="mt-1 block text-xs text-muted">
                                    {{ $project->industry?->label() ?? 'بدون صنعت' }} ·
                                    @fa($project->stations_count) ایستگاه ·
                                    @fa($project->rounds_count) دور ·
                                    @fa($project->readings_count) قرائت
                                </span>
                            </span>

                            <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-disclaimer class="mt-10" size="sm">
            فهرست ایستگاه‌های هر قالب صنعتی پیشنهادی و عمومی است. تعیین دامنه واقعی پایش بر
            عهده کارشناس و بر اساس شناسایی عوامل زیان‌آور همان واحد است.
        </x-disclaimer>

    </div>

</x-layouts.workspace>
