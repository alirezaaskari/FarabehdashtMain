@php
    use App\Modules\Reports\Domain\Enums\ReportStep;

    $current = collect($sources)->first(fn ($entry) => $entry['source']->key() === $selected) ?? ($sources[0] ?? null);
    $oldReferences = (array) old('references', []);
@endphp

<x-layouts.workspace title="گزارش تازه"
                     heading="گزارش تازه"
                     lede="داده گزارش از کجا بیاید؟ جدول نتایج و مشخصات تجهیزات از همین منبع خودکار ساخته می‌شود."
                     nav="reports">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['گزارش‌ها', route('reports.index')], ['گزارش تازه', null]]" />
    </x-slot:breadcrumb>

    @include('reports::partials.steps', ['current' => ReportStep::Source, 'report' => null])

    @if ($current === null)
        <x-empty-state icon="file"
                       title="منبعی برای گزارش در دسترس نیست"
                       description="گزارش از پروژه‌های اندازه‌گیری یا محاسبه‌های ذخیره‌شده ساخته می‌شود و هیچ‌کدام در حال حاضر فعال نیستند." />
    @else
        @if (count($sources) > 1)
            <div class="mb-6 flex flex-wrap gap-2" role="list" aria-label="نوع منبع">
                @foreach ($sources as $entry)
                    @php $active = $entry['source']->key() === $current['source']->key(); @endphp
                    <a href="{{ route('reports.create', ['source' => $entry['source']->key()]) }}" role="listitem"
                       @if ($active) aria-current="true" @endif
                       @class([
                           'inline-flex h-touch items-center rounded-md border px-4 text-label no-underline hover:no-underline',
                           'border-primary bg-primary-soft font-semibold text-on-primary-soft' => $active,
                           'border-line bg-surface font-semibold text-ink hover:border-primary' => ! $active,
                       ])>{{ $entry['source']->label() }}</a>
                @endforeach
            </div>
        @endif

        @if ($current['options'] === [])
            <x-empty-state icon="empty-box"
                           :title="$current['source']->label().' با نتیجه‌ای برای گزارش ندارید'"
                           :description="$current['source']->key() === 'project'
                               ? 'پروژه‌ای که دست‌کم یک قرائت داشته باشد این‌جا فهرست می‌شود.'
                               : 'نتیجه هر ابزار را ذخیره کنید تا این‌جا برای گزارش قابل انتخاب شود.'">
                @php
                    $first = $current['source']->key() === 'project'
                        ? ['projects.index', 'رفتن به پروژه‌ها']
                        : ['tools.index', 'رفتن به ابزارها'];
                @endphp
                @if (Route::has($first[0]))
                    <x-slot:action>
                        <x-button :href="route($first[0])" size="sm">{{ $first[1] }}</x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <form method="POST" action="{{ route('reports.store') }}">
                @csrf
                <input type="hidden" name="source" value="{{ $current['source']->key() }}">

                <x-card :title="$current['source']->label()"
                        :subtitle="$current['source']->multiple() ? 'یک یا چند مورد را انتخاب کنید.' : 'یک مورد را انتخاب کنید.'">
                    <fieldset>
                        <legend class="sr-only">{{ $current['source']->label() }}</legend>
                        <ul class="flex flex-col divide-y divide-line-soft">
                            @foreach ($current['options'] as $index => $option)
                                <li>
                                    <label class="flex min-h-touch cursor-pointer items-center gap-3 py-3">
                                        <input type="{{ $current['source']->multiple() ? 'checkbox' : 'radio' }}"
                                               name="references[]" value="{{ $option->reference }}"
                                               @checked(in_array($option->reference, $oldReferences, true)
                                                   || (! $current['source']->multiple() && $oldReferences === [] && $index === 0))
                                               class="size-5 shrink-0 accent-primary">
                                        <span class="min-w-0">
                                            <span class="block text-label font-semibold text-ink">{{ $option->title }}</span>
                                            @if ($option->meta)
                                                <span class="block text-note text-muted">{{ $option->meta }}</span>
                                            @endif
                                        </span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </fieldset>

                    @error('references')
                        <x-alert tone="error" class="mt-4">{{ $message }}</x-alert>
                    @enderror

                    <div class="mt-6">
                        <x-button type="submit" variant="primary" icon="forward">ادامه به مشخصات</x-button>
                    </div>
                </x-card>
            </form>
        @endif
    @endif

</x-layouts.workspace>
