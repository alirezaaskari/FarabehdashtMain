@php use App\Support\Measurement\MeasurementNumber; @endphp

<x-layouts.workspace :title="$project->title" :heading="$project->title">

    <div class="max-w-5xl">

        <nav aria-label="مسیر صفحه" class="mb-4 text-sm">
            <a href="{{ route('projects.index') }}"
               class="inline-flex h-touch items-center text-primary">پروژه‌ها</a>
            <span class="text-muted"> / {{ $project->title }}</span>
        </nav>

        <header class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="mt-1.5 text-sm text-muted">
                    {{ $project->client_name ? $project->client_name.' · ' : '' }}
                    {{ $project->industry?->label() ?? 'بدون صنعت' }}
                </p>
            </div>

            <x-button :href="route('projects.compare', $project->uuid)" variant="secondary" icon="forward">
                مقایسه دو دور
            </x-button>
        </header>

        @if (session('status'))
            <x-alert tone="success" class="mt-6">{{ session('status') }}</x-alert>
        @endif

        @foreach ($warnings as $warning)
            <x-alert :tone="$warning->blocking() ? 'error' : 'caution'" class="mt-4"
                     :title="$warning->identification">
                {{ $warning->message() }}
                @if ($warning->validUntil)
                    اعتبار تا {{ $warning->validUntil }}.
                @endif
                این تجهیز در @fa($warning->readingCount) قرائت این پروژه به‌کار رفته است.
            </x-alert>
        @endforeach

        @if ($needsAcknowledgement)
            <x-card class="mt-4" tone="surface" title="پیش از صدور گزارش" :level="2">
                <p class="text-sm text-muted">
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
            <x-alert tone="success" class="mt-4">
                هشدار کالیبراسیون این پروژه تأیید شده است.
            </x-alert>
        @endif

        @if ($template !== null && $template->tools !== [])
            <x-card class="mt-8" :title="'ابزارهای پیشنهادی برای '.$template->label()" :level="2">
                <p class="text-sm text-muted">{{ $template->note }}</p>

                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($template->tools as $slug)
                        @if (Route::has('tools.show'))
                            <a href="{{ route('tools.show', $slug) }}"
                               class="inline-flex h-touch items-center rounded-full border border-line
                                      px-4 text-xs font-bold text-primary no-underline hover:border-primary">
                                {{ $slug }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </x-card>
        @endif

        <section class="mt-8" aria-labelledby="grid-heading">
            <h2 id="grid-heading" class="text-lg font-extrabold text-ink">قرائت‌ها</h2>

            @if ($stations->isEmpty())
                <x-empty-state class="mt-4"
                               title="ایستگاهی تعریف نشده"
                               description="بدون ایستگاه، قرائتی ثبت نمی‌شود." />
            @else
                <div class="mt-3 overflow-x-auto rounded-xl border border-line">
                    <table class="w-full text-sm">
                        <caption class="sr-only">مقدار هر ایستگاه در هر دور اندازه‌گیری</caption>
                        <thead class="bg-surface-2">
                            <tr>
                                <th scope="col" class="p-3 text-start font-bold text-ink">ایستگاه</th>
                                @foreach ($rounds as $round)
                                    <th scope="col" class="p-3 text-start font-bold text-ink">{{ $round->title }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stations as $station)
                                <tr class="border-t border-line">
                                    <th scope="row" class="p-3 text-start font-semibold text-ink">{{ $station->title }}</th>
                                    @foreach ($rounds as $round)
                                        @php $reading = $readings->get($round->id.':'.$station->id); @endphp
                                        <td class="p-3 text-muted" dir="ltr" data-numeric>
                                            @if ($reading)
                                                {{ MeasurementNumber::format($reading->value) }} {{ $reading->unit }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($project->editable() && $stations->isNotEmpty() && $rounds->isNotEmpty())
            <x-card class="mt-8" title="ثبت قرائت" :level="2">
                <form method="POST" action="{{ route('projects.readings.store', $project->uuid) }}"
                      class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-bold text-ink">دور</span>
                        <select name="round_id" class="h-field rounded-md border border-line-strong bg-surface px-3 text-base text-ink">
                            @foreach ($rounds as $round)
                                <option value="{{ $round->id }}">{{ $round->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-bold text-ink">ایستگاه</span>
                        <select name="station_id" class="h-field rounded-md border border-line-strong bg-surface px-3 text-base text-ink">
                            @foreach ($stations as $station)
                                <option value="{{ $station->id }}">{{ $station->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <x-field name="value" label="مقدار" numeric required :error="$errors->first('value')" />
                    <x-field name="unit" label="واحد" value="dB" required :error="$errors->first('unit')"
                             hint="همه قرائت‌های یک پروژه باید واحد یکسان داشته باشند." />

                    <label class="flex flex-col gap-1.5 sm:col-span-2">
                        <span class="text-sm font-bold text-ink">تجهیز به‌کاررفته (اختیاری)</span>
                        <select name="equipment_id" class="h-field rounded-md border border-line-strong bg-surface px-3 text-base text-ink">
                            <option value="">ثبت نشده</option>
                            @foreach ($equipment as $item)
                                <option value="{{ $item->id }}">{{ $item->identification() }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-muted">
                            بدون ثبت تجهیز، هشدار کالیبراسیون پیش از گزارش کار نمی‌کند.
                        </span>
                    </label>

                    <div class="sm:col-span-2">
                        <x-button type="submit" variant="primary" icon="plus">ثبت قرائت</x-button>
                    </div>
                </form>
            </x-card>
        @endif

    </div>

</x-layouts.workspace>
