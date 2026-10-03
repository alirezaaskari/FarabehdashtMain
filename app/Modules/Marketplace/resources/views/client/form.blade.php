<x-layouts.workspace art="client-project-form" :title="$project ? 'اصلاح پروژه' : 'تعریف پروژه'" :heading="$project ? 'اصلاح پروژه' : 'تعریف پروژه'"
                     lede="کار را روشن بنویسید تا پیشنهاد دقیق بگیرید. پس از فرستادن، مدیر پروژه را بازبینی می‌کند و نتیجه را با اعلان می‌گیرید."
                     nav="market-projects" help="client-project-form">

    @error('project')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    @unless ($verified)
        <div class="mb-6"><x-alert tone="caution">تعریف پروژه برای حسابی است که شماره موبایلش تأیید شده؛ از «حساب من» تأییدش کنید.</x-alert></div>
    @endunless

    <x-card>
        <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-5"
              action="{{ $project ? route('market.client.update', $project->uuid) : route('market.client.store') }}">
            @csrf
            @if ($project) @method('PUT') @endif

            <x-field name="title" label="عنوان پروژه" :value="old('title', $project?->title)" required
                     hint="کوتاه و روشن؛ مثلاً «اندازه‌گیری صدای سالن نورد و گزارش»." :error="$errors->first('title')" />

            <div>
                <label for="service" class="mb-2 block text-label font-semibold text-ink">نوع کار <span class="text-danger" aria-hidden="true">*</span></label>
                <select id="service" name="service" required class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                    <option value="">انتخاب کنید</option>
                    @foreach ($services as $key => $name)
                        <option value="{{ $key }}" @selected(old('service', $project?->service) === $key)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('service')
                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                <input type="checkbox" name="remote" value="1" @checked(old('remote', $project?->remote)) class="size-5 shrink-0 accent-primary">
                کار از راه دور انجام می‌شود و به شهر خاصی بسته نیست (مثل مستندسازی)
            </label>

            @include('marketplace::client._place', [
                'province' => old('province', $project?->province),
                'city' => old('city', $project?->city),
            ])

            <fieldset>
                <legend class="mb-2 text-label font-semibold text-ink">بودجه (تومان)</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="budget_min" label="از" numeric inputmode="numeric" required :value="old('budget_min', $project?->budget_min_toman)" :error="$errors->first('budget_min')" />
                    <x-field name="budget_max" label="تا" numeric inputmode="numeric" required :value="old('budget_max', $project?->budget_max_toman)" :error="$errors->first('budget_max')" />
                </div>
                <p class="mt-1 text-note text-muted">کمینه بودجه پروژه {{ $budgetMin }} است. مجری‌ها با همین بازه پیشنهاد می‌دهند.</p>
            </fieldset>

            <x-field name="wanted_by" label="مهلت دلخواه تحویل (اختیاری)" type="date" numeric :value="old('wanted_by', $project?->wanted_by?->toDateString())"
                     hint="مجری‌ها زمان‌بندی پیشنهادشان را با این تاریخ می‌سنجند." :error="$errors->first('wanted_by')" />

            <div>
                <label for="description" class="mb-2 block text-label font-semibold text-ink">شرح کار <span class="text-danger" aria-hidden="true">*</span></label>
                <textarea id="description" name="description" rows="10" required aria-describedby="description-hint"
                          @if ($errors->has('description')) aria-invalid="true" @endif
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('description', $project?->description) }}</textarea>
                <p id="description-hint" class="mt-1 text-note text-muted">
                    محدوده کار، تعداد ایستگاه یا سالن، شیفت‌ها و خروجی لازم (مثلاً گزارش با نقشه صدا). شماره تماس، ایمیل و نشانی پیام‌رسان ننویسید؛ گفت‌وگو درون سایت است.
                </p>
                @error('description')
                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="files" class="mb-2 block text-label font-semibold text-ink">پیوست خصوصی (اختیاری)</label>
                <input id="files" name="files[]" type="file" multiple aria-describedby="files-hint"
                       class="block min-h-touch text-label text-muted">
                <p id="files-hint" class="mt-1 text-note text-muted">
                    نقشه سالن، عکس یا گزارش قبلی؛ حداکثر @fa($limits['files_max'] ?? 5) فایل. فقط مدیر و مجری پس از قرارداد آن‌ها را می‌بینند.
                </p>
                @error('files')
                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
                @error('files.*')
                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
                @if ($project?->files?->isNotEmpty())
                    <ul class="mt-3 flex list-none flex-col gap-2 ps-0">
                        @foreach ($project->files as $file)
                            <li class="flex flex-wrap items-center gap-3 text-label text-body">
                                <a href="{{ route('market.files.download', $file->uuid) }}">{{ $file->original_name }}</a>
                                <button type="submit" form="remove-{{ $file->uuid }}" class="min-h-touch text-label text-danger underline">برداشتن</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <fieldset class="flex flex-col gap-3">
                <legend class="mb-2 text-label font-semibold text-ink">نمایش</legend>
                <x-field name="client_name" label="نام شرکت یا کارخانه (اختیاری)" :value="old('client_name', $project?->client_name)" :error="$errors->first('client_name')" />
                <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                    <input type="checkbox" name="show_client_name" value="1" @checked(old('show_client_name', $project?->show_client_name)) class="size-5 shrink-0 accent-primary">
                    نام شرکت در صفحه پروژه نمایش داده شود؛ وگرنه «کارفرما در شهر شما» می‌آید
                </label>
                <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                    <input type="checkbox" name="private" value="1" @checked(old('private', $project?->is_private)) class="size-5 shrink-0 accent-primary">
                    پروژه خصوصی: در فهرست بازار و جست‌وجو نیاید و فقط مشاورانی که دعوت می‌کنم آن را ببینند
                </label>
            </fieldset>

            <div class="flex flex-wrap gap-3">
                <x-button type="submit" variant="primary" icon="check">فرستادن برای تأیید</x-button>
                <x-button :href="route('market.client.index')" variant="secondary">انصراف</x-button>
            </div>
        </form>

        @foreach ($project?->files ?? [] as $file)
            <form id="remove-{{ $file->uuid }}" method="POST" action="{{ route('market.files.destroy', $file->uuid) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </x-card>

</x-layouts.workspace>
