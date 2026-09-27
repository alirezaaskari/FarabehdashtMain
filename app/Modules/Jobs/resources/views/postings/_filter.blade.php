{{-- پالایش؛ فقط شهر یا فقط مهارت به نشانی ثابت همان صفحه می‌رود. --}}
<form method="GET" action="{{ route('jobs.index') }}" class="flex flex-wrap items-end gap-3">
    <div class="w-full sm:w-56">
        <label for="city" class="mb-2 block text-label font-semibold text-ink">شهر</label>
        <select id="city" name="city" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
            <option value="">همه شهرها</option>
            @foreach ($citiesByProvince as $province => $cities)
                <optgroup label="{{ $province }}">
                    @foreach ($cities as $key => $name)
                        <option value="{{ $key }}" @selected(($city ?? null) === $key)>{{ $name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
    @if ($skills !== [])
        <div class="w-full sm:w-64">
            <label for="skill" class="mb-2 block text-label font-semibold text-ink">مهارت</label>
            <select id="skill" name="skill" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                <option value="">همه مهارت‌ها</option>
                @foreach ($skills as $term)
                    <option value="{{ $term->slug }}" @selected(($skill ?? null)?->slug === $term->slug)>{{ $term->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="w-full sm:w-44">
        <label for="type" class="mb-2 block text-label font-semibold text-ink">نوع همکاری</label>
        <select id="type" name="type" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
            <option value="">همه</option>
            @foreach ($types as $option)
                <option value="{{ $option->value }}" @selected(($type ?? null) === $option)>{{ $option->label() }}</option>
            @endforeach
        </select>
    </div>
    <x-button type="submit" variant="secondary" icon="search">نمایش</x-button>
</form>
