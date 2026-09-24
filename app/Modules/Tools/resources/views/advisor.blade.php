<x-layouts.public title="دستیار انتخاب ابزار"
                  description="سه سؤال تا ابزار، مقاله، فایل و دوره‌ای که لازم دارید."
                  :canonical="route('tools.advisor')"
                  active="tools">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', route('home')],
            ['ابزارها', route('tools.index')],
            ['دستیار انتخاب', null],
        ]" />
    </x-slot:breadcrumb>

    <x-page-header title="دستیار انتخاب ابزار"
                   lede="سه سؤال بپرسیم تا دقیقاً ابزار، مقاله، فایل و دوره‌ای را که لازم دارید نشان بدهیم." />

    @php
        // کارت انتخاب: رادیوی واقعی زیر قاب، تا صفحه‌کلید، صفحه‌خوان و فرم
        // بدون جاوااسکریپت همان رفتار استاندارد را داشته باشند.
        $option = 'flex h-full min-h-touch cursor-pointer flex-col rounded-lg border border-line bg-surface px-5 py-4.5
                   hover:border-primary peer-checked:border-2 peer-checked:border-primary peer-checked:bg-primary-soft
                   peer-checked:px-[1.1875rem] peer-checked:py-[1.0625rem]
                   peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus';
        $current = $step ?? $steps;
    @endphp

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1.4fr_1fr]">

        <x-card size="lg">
            <div class="flex items-center justify-between gap-4">
                <span class="text-note font-bold text-muted">
                    @if ($step)
                        سؤال @fa($step) از @fa($steps)
                    @else
                        پیشنهاد آماده است
                    @endif
                </span>

                <span aria-hidden="true" class="flex w-24 gap-1">
                    @foreach (range(1, $steps) as $i)
                        <span @class(['h-1.5 flex-1 rounded-full', 'bg-primary' => $step === null || $i < $step, 'bg-primary-soft' => $step !== null && $i >= $step])></span>
                    @endforeach
                </span>
            </div>

            @if ($step !== null)
                <form method="GET" action="{{ route('tools.advisor') }}" data-advisor>
                    @foreach (match ($step) { 2 => $answers->withoutStage()->query(), 3 => $answers->withoutSituation()->query(), default => [] } as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach

                    <fieldset>
                        <legend class="mt-2.5 text-h2 text-ink">
                            @switch($step)
                                @case(1) چه چیزی می‌خواهید اندازه بگیرید؟ @break
                                @case(2) الان در چه مرحله‌ای از کار هستید؟ @break
                                @default کدام موقعیت به کار شما نزدیک‌تر است؟
                            @endswitch
                        </legend>

                        <div @class(['mt-5 grid gap-3', 'sm:grid-cols-2' => $step !== 3])>
                            @if ($step === 1)
                                @foreach ($hazards as $hazard)
                                    <label class="relative">
                                        <input type="radio" name="hazard" value="{{ $hazard->value }}" required
                                               class="peer sr-only" @checked($answers->hazard === $hazard)>
                                        <span class="{{ $option }}">
                                            <span class="text-copy font-bold text-ink">{{ $hazard->label() }}</span>
                                            <span class="mt-1 text-note text-muted">{{ $hazard->hint() }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            @elseif ($step === 2)
                                @foreach ($stages as $stage)
                                    <label class="relative">
                                        <input type="radio" name="stage" value="{{ $stage->value }}" required
                                               class="peer sr-only" @checked($answers->stage === $stage)>
                                        <span class="{{ $option }}">
                                            <span class="text-copy font-bold text-ink">{{ $stage->label() }}</span>
                                            <span class="mt-1 text-note text-muted">{{ $stage->hint() }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            @else
                                @foreach ($situations as $situation)
                                    <label class="relative">
                                        <input type="radio" name="situation" value="{{ $situation->key() }}" required
                                               class="peer sr-only" @checked($answers->situation === $situation->key())>
                                        <span class="{{ $option }}">
                                            <span class="text-copy font-bold text-ink">{{ $situation->text }}</span>
                                            <span class="mt-1 text-note text-muted">{{ $situation->tool->definition->title }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            @endif
                        </div>
                    </fieldset>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <x-button type="submit">{{ $step < $steps ? 'سؤال بعدی' : 'نمایش پیشنهاد' }}</x-button>

                        @if ($step > 1)
                            <x-button variant="secondary"
                                      :href="route('tools.advisor', $step === 2 ? [] : $answers->withoutStage()->query())">
                                سؤال قبلی
                            </x-button>
                        @else
                            <x-button variant="secondary" :href="route('tools.index')">رد کردن دستیار</x-button>
                        @endif
                    </div>
                </form>
            @else
                <h2 class="mt-2.5 text-h2 text-ink">
                    @if ($chosen)
                        ابزار شما: {{ $chosen->tool->definition->title }}
                    @else
                        ابزار محاسبه {{ $answers->hazard?->label() }} هنوز منتشر نشده است
                    @endif
                </h2>

                <p class="mt-3 text-copy text-body">
                    @if ($chosen)
                        {{ $chosen->reason }}
                    @else
                        تا آن زمان، مقاله‌ها، فایل‌ها و دوره‌های این حوزه را در ستون کنار ببینید.
                    @endif
                </p>

                <dl class="mt-6 grid gap-3 border-t border-line-soft pt-5 text-label">
                    @foreach (array_filter([
                        ['خطر', $answers->hazard?->label(), []],
                        ['مرحله کار', $answers->stage?->label(), ['hazard' => $answers->hazard?->value]],
                        ['موقعیت', $chosen?->text, $answers->withoutSituation()->query()],
                    ], static fn (array $row): bool => $row[1] !== null) as [$label, $value, $edit])
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="shrink-0 text-muted">{{ $label }}</dt>
                            <dd class="min-w-0 grow font-semibold text-ink">{{ $value }}</dd>
                            <dd class="shrink-0">
                                <a href="{{ route('tools.advisor', $edit) }}" class="inline-flex min-h-touch items-center font-bold">
                                    تغییر<span class="sr-only"> {{ $label }}</span>
                                </a>
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($chosen)
                        <x-button :href="route('tools.show', $chosen->tool->slug())" icon="calculator">باز کردن ابزار</x-button>
                    @endif
                    <x-button variant="secondary" :href="route('tools.advisor')">از اول</x-button>
                </div>
            @endif
        </x-card>

        <div class="flex flex-col gap-6">
            <x-card title="نتیجه پیشنهادی" aria-live="polite" data-advisor-result>
                @if ($answers->hazard === null)
                    <p class="mt-2.5 text-note text-muted">
                        یک گزینه را انتخاب کنید تا ابزار، مقاله، فایل و دوره مناسبش همین‌جا بیاید.
                    </p>
                @elseif ($suggestions === [])
                    <x-empty-state title="فعلاً پیشنهادی نیست"
                                   description="محتوای این حوزه پس از بازبینی علمی منتشر می‌شود." />
                @else
                    <p class="mt-2.5 text-note text-muted">
                        با انتخاب «{{ $answers->hazard->label() }}»{{ $answers->stage ? ' و «'.$answers->stage->label().'»' : '' }}
                        این موارد پیشنهاد می‌شوند:
                    </p>

                    <ul class="mt-3 list-none ps-0">
                        @foreach ($suggestions as $suggestion)
                            <li class="border-b border-line-soft last:border-b-0">
                                <a href="{{ $suggestion->url }}"
                                   class="flex min-h-touch items-center gap-3 py-3 text-ink no-underline hover:text-primary hover:no-underline">
                                    <x-badge tone="primary" class="shrink-0">{{ $suggestion->kind }}</x-badge>
                                    <span class="min-w-0 grow">
                                        <span class="block text-label font-semibold">{{ $suggestion->title }}</span>
                                        @if ($suggestion->note !== '')
                                            <span class="mt-0.5 block text-note text-muted">{{ $suggestion->note }}</span>
                                        @endif
                                    </span>
                                    <x-icon name="forward" :size="15" class="shrink-0 text-muted" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="چرا این دستیار؟">
                <p class="mt-2.5 text-copy text-body">
                    کاربر تازه‌کار وارد فهرست ابزارها می‌شود و گم می‌شود. این مسیر سه‌مرحله‌ای او را
                    مستقیم به محتوای درست می‌رساند و دلیلش را هم می‌گوید.
                </p>
            </x-card>

            <x-disclaimer>
                این دستیار راهنماست و جای تصمیم کارشناس را نمی‌گیرد. انتخاب روش اندازه‌گیری و
                دامنه پایش بر عهده کارشناس بهداشت حرفه‌ای است.
            </x-disclaimer>
        </div>

    </div>

</x-layouts.public>
