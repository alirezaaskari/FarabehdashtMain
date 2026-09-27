<x-layouts.workspace art="job-alerts" title="شغل‌های مناسب من" heading="شغل‌های مناسب من"
                     lede="آگهی‌های باز به ترتیب تطبیق با مهارت‌های گذرنامه شما، و هشدارهایی که آگهی تازه را خبر می‌دهند. این مقایسه فقط برای شماست و کارفرما آن را نمی‌بیند."
                     nav="job-alerts" help="job-alerts">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif
    @error('alert')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="grid items-start gap-8 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section aria-labelledby="suggestions-heading" class="min-w-0">
            <h2 id="suggestions-heading" class="text-h3 text-ink">به ترتیب تطبیق</h2>

            @if (! $hasSkills)
                <x-empty-state art="empty-job-alerts" icon="badge" class="mt-4" title="گذرنامه‌تان هنوز مهارتی ندارد"
                               description="در گذرنامه مهارتی مهارت‌هایتان را علامت بزنید یا با دوره، آزمون و گزارش آن‌ها را ثبت کنید تا آگهی‌ها با شما تطبیق داده شوند.">
                    @if (Route::has('jobs.passport.edit'))
                        <x-slot:action>
                            <x-button :href="route('jobs.passport.edit')" variant="primary">گذرنامه مهارتی</x-button>
                        </x-slot:action>
                    @endif
                </x-empty-state>
            @elseif ($suggestions === [])
                <p class="mt-4 text-copy text-muted">فعلاً آگهی بازی با مهارت‌های شما جور نیست. هشدار بسازید تا آگهی تازه را خبر بدهیم.</p>
            @else
                <ul class="mt-4 flex list-none flex-col gap-3 ps-0">
                    @foreach ($suggestions as $row)
                        <li>
                            <a href="{{ route('jobs.show', $row['posting']->id) }}"
                               class="flex min-h-touch flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-4 no-underline hover:bg-surface-2 hover:no-underline">
                                <span class="flex min-w-0 flex-col gap-1">
                                    <span class="text-h4 text-ink">{{ $row['posting']->title }}</span>
                                    <span class="text-note text-muted">{{ $row['posting']->company->name }} · {{ $catalog->cityName($row['posting']->city) }}</span>
                                </span>
                                <x-badge tone="primary">@fa($row['have']) از @fa($row['total']) مهارت</x-badge>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="flex flex-col gap-6">
            <x-card title="هشدارهای من" heading="text-h4">
                @if ($alerts->isEmpty())
                    <p class="text-copy text-muted">هنوز هشداری نساخته‌اید.</p>
                @else
                    <ul class="flex list-none flex-col divide-y divide-line ps-0">
                        @foreach ($alerts as $alert)
                            @php
                                $parts = array_filter([
                                    $alert->match_passport ? 'مطابق گذرنامه' : null,
                                    $alert->city ? $catalog->cityName($alert->city) : null,
                                    $alert->skill_id ? ($skillNames[$alert->skill_id] ?? null) : null,
                                    $alert->employment_type?->label(),
                                ]);
                            @endphp
                            <li class="flex flex-col gap-2 py-3">
                                <p class="text-label text-body">{{ implode(' · ', $parts) }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('jobs.alerts.sms', $alert->id) }}">
                                        @csrf
                                        <input type="hidden" name="sms" value="{{ $alert->sms ? '0' : '1' }}">
                                        <x-button type="submit" variant="secondary" size="sm">{{ $alert->sms ? 'خاموش‌کردن پیامک' : 'روشن‌کردن پیامک' }}</x-button>
                                    </form>
                                    <form method="POST" action="{{ route('jobs.alerts.destroy', $alert->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="ghost" size="sm">برداشتن</x-button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            @if ($alerts->count() < $max)
                <x-card title="هشدار تازه" heading="text-h4">
                    <form method="POST" action="{{ route('jobs.alerts.store') }}" class="flex flex-col gap-4">
                        @csrf
                        <x-toggle name="match_passport" :checked="(bool) old('match_passport', true)" label="مطابق مهارت‌های گذرنامه من"
                                  description="آگهی‌ای که دست‌کم یکی از مهارت‌های گذرنامه شما را بخواهد." />
                        <div>
                            <label for="alert-city" class="mb-2 block text-label font-semibold text-ink">شهر</label>
                            <select id="alert-city" name="city" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                                <option value="">همه شهرها</option>
                                @foreach ($regions as $region)
                                    <optgroup label="{{ $region['name'] }}">
                                        @foreach ($region['cities'] as $key => $name)
                                            <option value="{{ $key }}" @selected(old('city') === $key)>{{ $name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="alert-skill" class="mb-2 block text-label font-semibold text-ink">مهارت</label>
                            <select id="alert-skill" name="skill" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                                <option value="">همه مهارت‌ها</option>
                                @foreach ($skills as $term)
                                    <option value="{{ $term->id }}" @selected((int) old('skill') === $term->id)>{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="alert-type" class="mb-2 block text-label font-semibold text-ink">نوع همکاری</label>
                            <select id="alert-type" name="employment_type" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                                <option value="">همه</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->value }}" @selected(old('employment_type') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-toggle name="sms" :checked="(bool) old('sms')" label="پیامک خلاصه روزانه"
                                  description="اختیاری؛ روزی حداکثر یک پیامک با تعداد آگهی‌های تازه. اعلان درون سایت همیشه می‌آید." />
                        <div><x-button type="submit" variant="primary" icon="bell">ساختن هشدار</x-button></div>
                    </form>
                </x-card>
            @endif
        </div>
    </div>

</x-layouts.workspace>
