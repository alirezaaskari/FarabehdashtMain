@php use App\Support\Measurement\MeasurementNumber; @endphp

<x-layouts.workspace :title="$project->title"
                     :heading="$project->title"
                     :lede="trim(($project->client_name ? $project->client_name.' · ' : '')
                            .($project->industry?->label() ?? 'بدون صنعت'))"
                     active="tools"
                     nav="projects">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['پروژه‌های اندازه‌گیری', route('projects.index')],
            [$project->title, null],
        ]" />
    </x-slot:breadcrumb>

    <x-slot:actions>
        <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>

        <x-button :href="route('projects.compare', $project->uuid)" variant="secondary" icon="forward">
            مقایسه دو دور
        </x-button>
    </x-slot:actions>

    @if (session('status'))
        <x-alert tone="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @foreach ($warnings as $warning)
        <x-alert :tone="$warning->blocking() ? 'error' : 'caution'" class="mb-4"
                 :title="$warning->identification">
            {{ $warning->message() }}
            @if ($warning->validUntil)
                اعتبار تا {{ $warning->validUntil }}.
            @endif
            این تجهیز در @fa($warning->readingCount) قرائت این پروژه به‌کار رفته است.
        </x-alert>
    @endforeach

    @if ($needsAcknowledgement)
        <x-card class="mb-4" tone="surface" title="پیش از صدور گزارش">
            <p class="text-copy text-muted">
                دست‌کم یک تجهیز این پروژه کالیبراسیون معتبر ثبت‌شده ندارد. می‌توانید ادامه
                بدهید، ولی تأییدتان در دفتر رویداد ثبت می‌شود و در گزارش دیده خواهد شد.
            </p>

            <form method="POST" action="{{ route('projects.acknowledge', $project->uuid) }}" class="mt-4">
                @csrf
                <x-button type="submit" variant="danger" icon="alert">
                    می‌دانم و با همین شرایط ادامه می‌دهم
                </x-button>
            </form>
        </x-card>
    @elseif ($warnings !== [])
        <x-alert tone="success" class="mb-4">
            هشدار کالیبراسیون این پروژه تأیید شده است.
        </x-alert>
    @endif

    @if ($template !== null && $templateTools !== [] && Route::has('tools.show'))
        <x-card class="mb-6" :title="'ابزارهای پیشنهادی برای '.$template->label()">
            <p class="text-copy text-muted">{{ $template->note }}</p>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($templateTools as $tool)
                    <a href="{{ route('tools.show', $tool['slug']) }}"
                       class="inline-flex h-touch items-center rounded-full border border-line bg-surface-2
                              px-4 text-sm font-semibold text-ink no-underline
                              hover:border-primary hover:no-underline">
                        {{ $tool['title'] }}
                    </a>
                @endforeach
            </div>
        </x-card>
    @endif

    <section aria-labelledby="grid-heading">
        <h2 id="grid-heading" class="mb-3.5 text-xl font-extrabold text-ink">قرائت‌ها</h2>

        @if ($stations->isEmpty())
            <x-empty-state title="ایستگاهی تعریف نشده"
                           description="بدون ایستگاه، قرائتی ثبت نمی‌شود." />
        @else
            <x-data-table :headers="array_merge(['ایستگاه'], $rounds->pluck('title')->all())"
                          caption="مقدار هر ایستگاه در هر دور اندازه‌گیری">
                @foreach ($stations as $station)
                    <tr>
                        <th scope="row" class="px-4 py-3.5 text-start text-sm font-semibold text-ink">
                            {{ $station->title }}
                        </th>
                        @foreach ($rounds as $round)
                            @php $reading = $readings->get($round->id.':'.$station->id); @endphp
                            <td dir="ltr" data-numeric>
                                @if ($reading)
                                    {{ MeasurementNumber::format($reading->value) }} {{ $reading->unit }}
                                @else
                                    —
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach

                <x-slot:footnote>
                    خانه خالی یعنی برای آن ایستگاه در آن دور قرائتی ثبت نشده، نه اینکه مقدارش صفر است.
                </x-slot:footnote>
            </x-data-table>
        @endif
    </section>

    @if ($project->editable() && $stations->isNotEmpty() && $rounds->isNotEmpty())
        <x-card class="mt-6" title="ثبت قرائت">
            <form method="POST" action="{{ route('projects.readings.store', $project->uuid) }}"
                  class="grid gap-4 sm:grid-cols-2">
                @csrf

                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-bold text-ink">دور</span>
                    <select name="round_id" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-base text-ink">
                        @foreach ($rounds as $round)
                            <option value="{{ $round->id }}">{{ $round->title }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-bold text-ink">ایستگاه</span>
                    <select name="station_id" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-base text-ink">
                        @foreach ($stations as $station)
                            <option value="{{ $station->id }}">{{ $station->title }}</option>
                        @endforeach
                    </select>
                </label>

                <x-field name="value" label="مقدار" numeric required :error="$errors->first('value')" />
                <x-field name="unit" label="واحد" value="dB" required :error="$errors->first('unit')"
                         hint="همه قرائت‌های یک پروژه باید واحد یکسان داشته باشند." />

                <label class="flex min-w-0 flex-col gap-1.5 sm:col-span-2">
                    <span class="text-sm font-bold text-ink">تجهیز به‌کاررفته (اختیاری)</span>
                    <select name="equipment_id" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-base text-ink">
                        <option value="">ثبت نشده</option>
                        @foreach ($equipment as $item)
                            <option value="{{ $item->id }}">{{ $item->identification() }}</option>
                        @endforeach
                    </select>
                    <span class="text-note text-muted">
                        بدون ثبت تجهیز، هشدار کالیبراسیون پیش از گزارش کار نمی‌کند.
                    </span>
                </label>

                <div class="sm:col-span-2">
                    <x-button type="submit" variant="primary" icon="plus">ثبت قرائت</x-button>
                </div>
            </form>
        </x-card>
    @endif

</x-layouts.workspace>
