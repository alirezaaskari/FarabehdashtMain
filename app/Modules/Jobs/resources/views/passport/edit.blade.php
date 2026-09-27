<x-layouts.workspace art="passport" title="گذرنامه مهارتی" heading="گذرنامه مهارتی"
                     lede="کارنامه کاری شما در دو بخش جدا: آنچه خود فرابهداشت ثبت کرده و آنچه خودتان اظهار می‌کنید. آگهی‌های شغلی با همین مهارت‌ها با شما تطبیق داده می‌شوند."
                     nav="passport" help="passport">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif
    @error('entry')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="flex flex-col gap-10">
        @include('jobs::passport._verified')

        <section aria-labelledby="declared-heading" class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <h2 id="declared-heading" class="text-h3 text-ink">به اظهار خود کاربر</h2>
                <x-badge tone="neutral">بدون تأیید</x-badge>
            </div>
            <p class="text-copy text-muted">فرابهداشت این بخش را بررسی نمی‌کند و همه‌جا با همین برچسب نشان داده می‌شود.</p>

            <x-card title="معرفی و مهارت‌ها" heading="text-h4">
                <form method="POST" action="{{ route('jobs.passport.update') }}" class="flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    <x-field name="headline" label="یک خط معرفی" :value="old('headline', $passport->headline)" maxlength="{{ $limits['headline_max'] ?? 120 }}"
                             hint="مثلاً «کارشناس بهداشت حرفه‌ای با تمرکز بر صدا و گرد و غبار»." :error="$errors->first('headline')" />

                    @include('jobs::workspace._place', ['province' => old('province', $passport->province), 'city' => old('city', $passport->city), 'optional' => true])

                    <x-field name="experience_years" label="سال‌های سابقه کار مرتبط" type="number" numeric inputmode="numeric" min="0" max="50"
                             :value="old('experience_years', $passport->experience_years)" :error="$errors->first('experience_years')" />

                    @if ($skills !== [])
                        <fieldset>
                            <legend class="mb-2 text-label font-semibold text-ink">مهارت‌هایی که با آن‌ها کار کرده‌اید</legend>
                            <div class="grid gap-x-4 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($skills as $term)
                                    <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                        <input type="checkbox" name="skills[]" value="{{ $term->id }}"
                                               @checked(in_array($term->id, array_map(intval(...), (array) old('skills', $declaredSkillIds)), true))
                                               class="size-5 shrink-0 accent-primary">
                                        {{ $term->name }}
                                    </label>
                                @endforeach
                            </div>
                            @error('skills')
                                <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    @endif

                    <div><x-button type="submit" variant="primary">ذخیره</x-button></div>
                </form>
            </x-card>

            @include('jobs::passport._declared', ['editable' => true])

            <x-card title="افزودن تحصیلات، سابقه یا گواهی" heading="text-h4">
                <form method="POST" action="{{ route('jobs.passport.entries.store') }}" class="flex flex-col gap-5">
                    @csrf
                    <div>
                        <label for="kind" class="mb-2 block text-label font-semibold text-ink">نوع</label>
                        <select id="kind" name="kind" required class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                            @foreach ($kinds as $kind)
                                <option value="{{ $kind->value }}" @selected(old('kind') === $kind->value)>{{ $kind->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-field name="title" label="عنوان" :value="old('title')" required
                             hint="مثلاً «کارشناسی مهندسی بهداشت حرفه‌ای» یا «کارشناس HSE» یا «دوره ایمنی کار در ارتفاع»." :error="$errors->first('title')" />
                    <x-field name="organization" label="دانشگاه، شرکت یا صادرکننده" :value="old('organization')" :error="$errors->first('organization')" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="start_year" label="سال شروع (شمسی)" numeric inputmode="numeric" :value="old('start_year')" :error="$errors->first('start_year')" />
                        <x-field name="end_year" label="سال پایان (شمسی)" numeric inputmode="numeric" :value="old('end_year')" hint="برای کار فعلی خالی بگذارید." :error="$errors->first('end_year')" />
                    </div>
                    <x-field name="note" label="توضیح کوتاه (اختیاری)" :value="old('note')" :error="$errors->first('note')" />
                    <div><x-button type="submit" variant="secondary" icon="plus">افزودن</x-button></div>
                </form>
            </x-card>
        </section>

        <section aria-labelledby="share-heading">
            <x-card title="صفحه اشتراکی" heading="text-h4">
                <form method="POST" action="{{ route('jobs.passport.share') }}" class="flex flex-col gap-3">
                    @csrf
                    <x-toggle name="shared" :checked="$passport->shared" label="نشانی ثابت برای فرستادن به کارفرما"
                              description="پیش‌فرض خاموش است. روشن هم که باشد، موتورهای جست‌وجو آن را نمایه نمی‌کنند و شماره و ایمیل شما در آن نیست." />
                    <div><x-button type="submit" variant="secondary" size="sm">ذخیره</x-button></div>
                </form>
                @if ($passport->shared)
                    <p class="mt-4 text-label text-body">نشانی: <a href="{{ route('jobs.passport.show', $passport->share_token) }}" data-numeric>{{ route('jobs.passport.show', $passport->share_token) }}</a></p>
                @endif
            </x-card>
        </section>

        <x-disclaimer>این گذرنامه گواهی یا مدرک رسمی نیست. بخش «ثبت‌شده» فقط کارهایی است که در فرابهداشت انجام شده و بخش «به اظهار خود کاربر» بررسی نمی‌شود.</x-disclaimer>
    </div>

</x-layouts.workspace>
