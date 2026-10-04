@php
    $max = (int) ($limits['milestones_max'] ?? 5);
    $rows = old('milestones', $bid ? array_map(static fn (array $row): array => [
        'title' => $row['title'], 'amount' => $row['amount_toman'], 'days' => $row['days'],
    ], $bid->milestones) : []);
    $rows = array_pad(array_slice(array_values((array) $rows), 0, $max), $max, []);
@endphp

<x-layouts.workspace art="bid-form" :title="$bid ? 'ویرایش پیشنهاد' : 'ثبت پیشنهاد'" :heading="$bid ? 'ویرایش پیشنهاد' : 'ثبت پیشنهاد'"
                     lede="کار را به چند مرحله با مبلغ و زمان روشن بشکنید. کارفرما پیشنهادها را کنار هم می‌بیند و پول هر مرحله را پیش از شروعش در امانت می‌گذارد."
                     nav="market-bids" help="bid-form">

    @error('bid')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <x-card>
            <form method="POST" action="{{ route('market.bid.store', $project->id) }}" class="flex flex-col gap-6">
                @csrf

                <div>
                    <label for="cover" class="mb-2 block text-label font-semibold text-ink">متن پیشنهاد <span class="text-danger" aria-hidden="true">*</span></label>
                    <textarea id="cover" name="cover" rows="7" required aria-describedby="cover-hint"
                              @if ($errors->has('cover')) aria-invalid="true" @endif
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('cover', $bid?->cover) }}</textarea>
                    <p id="cover-hint" class="mt-1 text-note text-muted">
                        روش کار، دستگاه و کالیبراسیون، تجربه مشابه و خروجی تحویلی. شماره، ایمیل، لینک و نام پیام‌رسان ننویسید؛ پیشنهادی که راه تماس داشته باشد فرستاده نمی‌شود.
                    </p>
                    @error('cover')
                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset>
                    <legend class="mb-2 text-label font-semibold text-ink">مرحله‌ها</legend>
                    <p class="mb-4 text-note text-muted">
                        حداکثر @fa($max) مرحله؛ مبلغ هر مرحله دست‌کم {{ $milestoneMin }}. ردیف‌های خالی نادیده گرفته می‌شوند.
                        کمیسیون فرابهداشت امروز {{ $commission }} است و فقط از سهم شما برداشته می‌شود؛ کارفرما همین مبلغ را می‌پردازد و نرخ روز پذیرش روی قرارداد ثابت می‌ماند.
                    </p>
                    @error('rows')
                        <p class="mb-3 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                    <ol class="flex list-none flex-col gap-4 ps-0">
                        @foreach ($rows as $index => $row)
                            <li class="rounded-lg border border-line p-4">
                                <p class="mb-3 text-label font-semibold text-ink">مرحله @fa($index + 1)</p>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <x-field name="milestones[{{ $index }}][title]" id="milestone-{{ $index }}-title" label="عنوان" :required="$index === 0"
                                             :value="$row['title'] ?? null" :error="$errors->first('rows.'.$index.'.title')" />
                                    <x-field name="milestones[{{ $index }}][amount]" id="milestone-{{ $index }}-amount" label="مبلغ (تومان)" numeric inputmode="numeric" :required="$index === 0"
                                             :value="$row['amount'] ?? null" :error="$errors->first('rows.'.$index.'.amount')" />
                                    <x-field name="milestones[{{ $index }}][days]" id="milestone-{{ $index }}-days" label="روز کار" numeric inputmode="numeric" :required="$index === 0"
                                             :value="$row['days'] ?? null" :error="$errors->first('rows.'.$index.'.days')" />
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </fieldset>

                <div class="flex flex-wrap gap-3">
                    <x-button type="submit" variant="primary">{{ $bid ? 'ذخیره پیشنهاد' : 'فرستادن پیشنهاد' }}</x-button>
                    <x-button :href="route('market.show', $project->id)" variant="ghost">بازگشت به پروژه</x-button>
                </div>
            </form>
        </x-card>

        <aside>
            <x-card :title="$project->title" heading="text-h4">
                <dl class="flex flex-col gap-3 text-copy">
                    <div><dt class="text-note text-muted">نوع کار</dt><dd class="text-ink">{{ $catalog->serviceName($project->service) }}</dd></div>
                    <div><dt class="text-note text-muted">محل</dt><dd class="text-ink">{{ $catalog->place($project) }}</dd></div>
                    <div><dt class="text-note text-muted">بودجه کارفرما</dt><dd class="text-ink">{{ $catalog->budgetLabel($project) }}</dd></div>
                </dl>
            </x-card>
        </aside>
    </div>

</x-layouts.workspace>
