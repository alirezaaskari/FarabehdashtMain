<x-layouts.workspace :title="$service ? 'ویرایش خدمت' : 'خدمت تازه'"
                     :heading="$service ? 'ویرایش خدمت' : 'خدمت تازه'"
                     lede="خدمت پس از تأیید مدیر روی صفحه عمومی شما خریدنی می‌شود. ویرایش خدمت منتشرشده، آن را تا تأیید دوباره از فروش بیرون می‌برد."
                     nav="consulting-services" help="consulting-services">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خدمت‌های من', route('consulting.services.index')], [$service ? 'ویرایش' : 'خدمت تازه', null]]" />
    </x-slot:breadcrumb>

    @if ($errors->has('service'))
        <div class="mb-6"><x-alert tone="error">{{ $errors->first('service') }}</x-alert></div>
    @endif

    <x-card class="min-w-0">
        <form method="POST" action="{{ $service ? route('consulting.services.update', $service->uuid) : route('consulting.services.store') }}" class="flex flex-col gap-5">
            @csrf
            @if ($service) @method('PUT') @endif

            <fieldset>
                <legend class="mb-2 text-label font-semibold text-ink">نوع خدمت</legend>
                @foreach ($kinds as $kind)
                    <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                        <input type="radio" name="kind" value="{{ $kind->value }}" required
                               @checked(old('kind', $service?->kind->value ?? 'online') === $kind->value)
                               class="size-5 shrink-0 accent-primary">
                        {{ $kind->label() }}
                    </label>
                @endforeach
            </fieldset>

            <x-field name="title" label="عنوان" :value="old('title', $service?->title)" required
                     hint="روشن و مشخص؛ مثلاً «بررسی آنلاین برنامه حفاظت شنوایی کارخانه»."
                     :error="$errors->first('title')" />

            <div>
                <label for="description" class="mb-2 block text-label font-semibold text-ink">شرح <span class="text-danger" aria-hidden="true">*</span></label>
                <textarea id="description" name="description" rows="7" required aria-describedby="description-hint"
                          @if ($errors->has('description')) aria-invalid="true" @endif
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('description', $service?->description) }}</textarea>
                <p id="description-hint" class="mt-1 text-note text-muted">
                    خریدار چه چیزی می‌گیرد و چه چیزی باید آماده کند. شماره تماس، ایمیل و ادعای «تأیید رسمی» یا «انطباق قانونی تضمینی» ننویسید.
                </p>
                @error('description')
                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="duration_minutes" type="number" label="مدت (دقیقه)" :value="old('duration_minutes', $service?->duration_minutes ?? 60)" required numeric inputmode="numeric"
                         :error="$errors->first('duration_minutes')" />
                <x-field name="price_toman" type="number" label="قیمت" suffix="تومان" :value="old('price_toman', $service?->price_toman)" required numeric inputmode="numeric"
                         hint="کمینه {{ \App\Support\PersianNumber::format((int) ($limits['price_min_toman'] ?? 100000)) }} تومان."
                         :error="$errors->first('price_toman')" />
            </div>

            <details class="rounded-lg border border-line px-4 py-3" @if (old('kind', $service?->kind->value) === 'visit') open @endif>
                <summary class="min-h-touch cursor-pointer content-center text-label font-semibold text-ink">شهرهای بازدید حضوری</summary>
                <p class="mt-2 text-note text-muted">فقط برای بازدید حضوری؛ خریدار از میان همین شهرها انتخاب می‌کند.</p>
                @error('cities')
                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
                @php $chosen = (array) old('cities', $service?->cities ?? []); @endphp
                @foreach ($regions as $region)
                    <fieldset class="mt-3">
                        <legend class="text-note font-semibold text-muted">{{ $region['name'] }}</legend>
                        <div class="grid gap-x-4 sm:grid-cols-3">
                            @foreach ($region['cities'] as $key => $name)
                                <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                    <input type="checkbox" name="cities[]" value="{{ $key }}" @checked(in_array($key, $chosen, true))
                                           class="size-5 shrink-0 accent-primary">
                                    {{ $name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </details>

            <div>
                <x-button type="submit" variant="primary" icon="check">فرستادن برای تأیید</x-button>
            </div>
        </form>
    </x-card>

</x-layouts.workspace>
