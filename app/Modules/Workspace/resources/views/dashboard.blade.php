<x-layouts.workspace art="workspace-dashboard" title="میزکار"
                     :heading="filled($user->name) ? 'سلام، '.$user->name : 'سلام'"
                     :lede="$view->isPersonal()
                         ? 'خلاصه کار شما در همه بخش‌های فرابهداشت.'
                         : 'نمای '.$view->label.' — فقط آنچه به این نقش مربوط است.'"
                     nav="dashboard" help="dashboard">

    @if (count($views) > 1)
        <form method="POST" action="{{ route('workspace.view') }}" class="mb-8">
            @csrf
            <fieldset>
                <legend class="mb-3 text-label font-semibold text-muted">نمای میزکار</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($views as $option)
                        <button type="submit" name="view" value="{{ $option->key }}"
                                @class([
                                    'inline-flex min-h-touch items-center rounded-md border px-4 text-label font-semibold',
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
        نوار آمار، سپس در نمای نقش کارت‌های همان نقش و در نمای شخصی «آخرین
        فعالیت‌ها». پیمایش به هر بخش کار ستون کناری است و این‌جا تکرار نمی‌شود.
        همه از کارت‌هایی که ماژول‌ها می‌دهند؛ قالب نام هیچ ماژولی را نمی‌داند.
    --}}
    @if ($highlights !== [])
        {{-- یک جدول بی‌قاب با خط مو، نه چهار کارت جدا: عددها کنار هم خوانده می‌شوند. --}}
        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-line bg-line xl:grid-cols-4">
            @foreach ($highlights as $stat)
                <div class="bg-surface px-5 py-4 md:px-6">
                    <dt class="text-note font-semibold text-muted">{{ $stat->label }}</dt>
                    <dd class="mt-2 text-stat text-ink">{{ $stat->value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    <div @class(['mt-8' => $highlights !== []])>
        @if (! $view->isPersonal())
            <div class="grid items-start gap-6 lg:grid-cols-2">
                @foreach ($widgets as $widget)
                    <x-card :title="$widget->title">
                        @if ($widget->rows !== [])
                            <ul class="-my-2 list-none divide-y divide-line-soft ps-0">
                                @foreach ($widget->rows as $row)
                                    <li class="flex min-h-touch flex-col justify-center py-3">
                                        @if ($row->url)
                                            <a href="{{ $row->url }}" class="text-label font-semibold">{{ $row->label }}</a>
                                        @else
                                            <span class="text-label font-semibold text-ink">{{ $row->label }}</span>
                                        @endif
                                        @if ($row->meta)
                                            <span class="mt-0.5 text-note text-muted">{{ $row->meta }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @elseif ($widget->empty)
                            <p class="text-label text-muted">{{ $widget->empty }}</p>
                        @endif

                        @if ($widget->actionUrl && $widget->actionLabel)
                            <x-button :href="$widget->actionUrl" variant="secondary" class="mt-4">{{ $widget->actionLabel }}</x-button>
                        @endif
                    </x-card>
                @endforeach
            </div>
        @else
            <section aria-labelledby="recent-activity" class="min-w-0">
                <h2 id="recent-activity" class="text-h3 text-ink">
                    {{ $activity !== [] ? 'آخرین فعالیت‌ها' : 'شروع کار با میزکار' }}
                </h2>

                <div class="mt-3 border-t border-line">
                    @if ($activity !== [])
                        <ul class="list-none divide-y divide-line-soft border-b border-line ps-0">
                            @foreach ($activity as $row)
                                <li class="flex min-h-touch items-baseline py-2.5">
                                    <span class="min-w-0 grow">
                                        @if ($row->url)
                                            <a href="{{ $row->url }}" class="inline-flex min-h-touch items-center text-label font-semibold">{{ $row->label }}</a>
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
                        <ol class="list-none divide-y divide-line-soft border-b border-line ps-0">
                            @foreach (array_filter([
                                Route::has('tools.index') ? ['یک ابزار محاسبه را امتحان کنید و نتیجه را ذخیره کنید', route('tools.index')] : null,
                                Route::has('projects.index') ? ['اولین پروژه اندازه‌گیری را بسازید', route('projects.index')] : null,
                                Route::has('reports.create') ? ['از نتیجه‌ها یک گزارش PDF بسازید', route('reports.create')] : null,
                            ]) as $i => [$label, $url])
                                {{-- کل ردیف پیوند است تا هدف لمسی ۴۴ پیکسل باشد، نه فقط متن. --}}
                                <li>
                                    <a href="{{ $url }}" class="flex min-h-touch items-center gap-3 py-3 text-label font-semibold">
                                        <span class="w-6 shrink-0 text-note font-semibold text-muted">@fa($loop->iteration)</span>
                                        {{ $label }}
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        @endif
    </div>

</x-layouts.workspace>
