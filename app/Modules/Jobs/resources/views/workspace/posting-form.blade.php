<x-layouts.workspace art="posting-form" :title="$posting ? 'ویرایش آگهی' : 'آگهی تازه'" :heading="$posting ? 'ویرایش آگهی' : 'آگهی تازه'"
                     :lede="'آگهی برای '.$company->name.'. پس از فرستادن، مدیر آن را بازبینی می‌کند و نتیجه را با اعلان می‌گیرید.'"
                     nav="employer-postings" help="posting-form">

    @error('posting')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    @if ($posting?->published_at)
        <div class="mb-6"><x-alert tone="info">تا تأیید ویرایش، متن فعلی آگهی روی سایت می‌ماند و روزهای اعتبار عوض نمی‌شود.</x-alert></div>
    @endif

    <x-card>
        <form method="POST" action="{{ $posting ? route('jobs.employer.postings.update', $posting->uuid) : route('jobs.employer.postings.store') }}" class="flex flex-col gap-5">
            @csrf
            @if ($posting) @method('PUT') @endif

            <x-field name="title" label="عنوان شغل" :value="old('title', $draft?->title)" required
                     hint="روشن و بی‌اغراق؛ مثلاً «کارشناس بهداشت حرفه‌ای کارخانه»." :error="$errors->first('title')" />

            @include('jobs::workspace._place', ['province' => old('province', $draft?->province ?? $company->province), 'city' => old('city', $draft?->city ?? $company->city)])

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="employment_type" class="mb-2 block text-label font-semibold text-ink">نوع همکاری</label>
                    <select id="employment_type" name="employment_type" required class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('employment_type', $draft?->employmentType->value) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field name="min_experience_years" label="کمینه سابقه (سال)" type="number" numeric inputmode="numeric" required min="0" max="{{ $limits['experience_max'] ?? 30 }}"
                         :value="old('min_experience_years', $draft?->minExperienceYears ?? 0)" hint="صفر یعنی تازه‌کار هم می‌تواند درخواست بدهد."
                         :error="$errors->first('min_experience_years')" />
            </div>

            <fieldset>
                <legend class="mb-2 text-label font-semibold text-ink">حقوق ماهانه (تومان، اختیاری)</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="salary_min" label="از" numeric inputmode="numeric" :value="old('salary_min', $draft?->salaryMinToman)" :error="$errors->first('salary_min')" />
                    <x-field name="salary_max" label="تا" numeric inputmode="numeric" :value="old('salary_max', $draft?->salaryMaxToman)" :error="$errors->first('salary_max')" />
                </div>
                <p class="mt-1 text-note text-muted">خالی بماند، «توافقی» نوشته می‌شود و آگهی در فهرست پایین‌تر نمی‌رود.</p>
            </fieldset>

            @if ($skills !== [])
                <fieldset>
                    <legend class="mb-2 text-label font-semibold text-ink">مهارت‌های لازم</legend>
                    <p class="mb-2 text-note text-muted">حداکثر @fa($limits['skills_max'] ?? 8) مهارت اصلی؛ کارجوها با همین‌ها آگهی را پیدا می‌کنند.</p>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        @foreach ($skills as $term)
                            <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                <input type="checkbox" name="skills[]" value="{{ $term->id }}"
                                       @checked(in_array($term->id, array_map(intval(...), (array) old('skills', $draft?->skillIds ?? [])), true))
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

            <div>
                <label for="description" class="mb-2 block text-label font-semibold text-ink">شرح شغل <span class="text-danger" aria-hidden="true">*</span></label>
                <textarea id="description" name="description" rows="10" required aria-describedby="description-hint"
                          @if ($errors->has('description')) aria-invalid="true" @endif
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('description', $draft?->description) }}</textarea>
                <p id="description-hint" class="mt-1 text-note text-muted">
                    وظیفه‌ها، محیط کار، ساعت کار و مزایا. شماره تماس، ایمیل و درخواست هر نوع پول از کارجو ننویسید؛ درخواست‌ها از راه سایت می‌رسد.
                </p>
                @error('description')
                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <x-button type="submit" variant="primary" icon="check">فرستادن برای تأیید</x-button>
                <x-button :href="route('jobs.employer.postings.index')" variant="secondary">انصراف</x-button>
            </div>
        </form>
    </x-card>

</x-layouts.workspace>
