<x-layouts.workspace title="میزکار"
                     :heading="filled($user->name) ? 'سلام، '.$user->name : 'سلام'"
                     :lede="$view->isPersonal()
                         ? 'خلاصه کار شما در همه بخش‌های فرابهداشت.'
                         : 'نمای '.$view->label.' — فقط آنچه به این نقش مربوط است.'"
                     nav="dashboard">

    @if (count($views) > 1)
        <form method="POST" action="{{ route('workspace.view') }}" class="mb-8">
            @csrf
            <fieldset>
                <legend class="mb-3 text-label font-bold text-muted">نمای میزکار</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($views as $option)
                        <button type="submit" name="view" value="{{ $option->key }}"
                                @class([
                                    'inline-flex min-h-touch items-center rounded-full border px-4 text-label font-bold',
                                    'border-transparent bg-primary text-on-primary' => $option->key === $view->key,
                                    'border-line bg-surface text-ink hover:bg-surface-2' => $option->key !== $view->key,
                                ])
                                @if ($option->key === $view->key) aria-pressed="true" @else aria-pressed="false" @endif>
                            {{ $option->label }}
                        </button>
                    @endforeach
                </div>
            </fieldset>

            @error('view')
                <p class="mt-3 text-note text-danger" role="alert">{{ $message }}</p>
            @enderror
        </form>
    @endif

    {{--
        چیدمان پروتوتایپ: نوار آمار، «دسترسی سریع» به هر بخش و «آخرین فعالیت‌ها».
        همه از کارت‌هایی که ماژول‌ها می‌دهند؛ قالب نام هیچ ماژولی را نمی‌داند.
    --}}
    @if ($highlights !== [])
        <dl class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            @foreach ($highlights as $stat)
                <div class="rounded-xl border border-line bg-surface px-5 py-5 md:px-6">
                    <dt class="text-note font-semibold text-muted">{{ $stat->label }}</dt>
                    <dd class="mt-2 text-stat text-ink">{{ $stat->value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    <div @class(['grid items-start gap-6 lg:grid-cols-2', 'mt-8' => $highlights !== []])>
        <section aria-labelledby="quick-access" class="min-w-0">
            <h2 id="quick-access" class="text-h3 text-ink">دسترسی سریع</h2>

            <ul class="mt-4 flex list-none flex-col gap-3 ps-0">
                @foreach ($widgets as $widget)
                    @php
                        $summary = $widget->stats !== []
                            ? $widget->stats[0]->label.': '.$widget->stats[0]->value
                            : ($widget->rows[0]->label ?? $widget->empty);
                    @endphp
                    <li>
                        <a href="{{ $widget->actionUrl ?? route('workspace.dashboard') }}"
                           class="flex min-h-touch items-center gap-4 rounded-lg border border-line bg-surface px-5 py-4
                                  text-ink no-underline hover:border-primary-line hover:no-underline">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary-soft text-primary">
                                <x-icon :name="$widget->icon ?? 'list'" :size="19" />
                            </span>
                            <span class="min-w-0 grow">
                                <span class="block text-copy font-bold">{{ $widget->title }}</span>
                                @if ($summary)
                                    <span class="mt-0.5 block truncate text-note text-muted">{{ $summary }}</span>
                                @endif
                                @if ($widget->actionLabel)
                                    <span class="sr-only">— {{ $widget->actionLabel }}</span>
                                @endif
                            </span>
                            <x-icon name="forward" :size="16" class="text-muted" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="recent-activity" class="min-w-0">
            <h2 id="recent-activity" class="text-h3 text-ink">
                {{ $activity !== [] ? 'آخرین فعالیت‌ها' : 'شروع کار با میزکار' }}
            </h2>

            <x-card class="mt-4">
                @if ($activity !== [])
                    <ul class="-my-2 list-none divide-y divide-line-soft ps-0">
                        @foreach ($activity as $row)
                            <li class="flex min-h-touch items-baseline gap-3 py-3.5">
                                <span aria-hidden="true" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary"></span>
                                <span class="min-w-0 grow">
                                    @if ($row->url)
                                        <a href="{{ $row->url }}" class="text-label font-semibold">{{ $row->label }}</a>
                                    @else
                                        <span class="text-label font-semibold text-ink">{{ $row->label }}</span>
                                    @endif
                                    @if ($row->meta)
                                        <span class="mt-0.5 block text-note text-muted">{{ $row->meta }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    {{-- کاربر تازه به‌جای ستونی از صفرها، سه قدم اول را می‌بیند. --}}
                    <ol class="-my-2 list-none divide-y divide-line-soft ps-0">
                        @foreach (array_filter([
                            Route::has('tools.index') ? ['یک ابزار محاسبه را امتحان کنید و نتیجه را ذخیره کنید', route('tools.index')] : null,
                            Route::has('projects.index') ? ['اولین پروژه اندازه‌گیری را بسازید', route('projects.index')] : null,
                            Route::has('reports.create') ? ['از نتیجه‌ها یک گزارش PDF بسازید', route('reports.create')] : null,
                        ]) as $i => [$label, $url])
                            <li class="flex min-h-touch items-center gap-3 py-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-soft text-note font-bold text-primary">@fa($loop->iteration)</span>
                                <a href="{{ $url }}" class="text-label font-semibold">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-card>
        </section>
    </div>

</x-layouts.workspace>
