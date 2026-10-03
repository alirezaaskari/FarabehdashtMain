{{-- پالایش؛ فقط خدمت یا فقط شهر به نشانی ثابت همان صفحه می‌رود. --}}
<form method="GET" action="{{ route('market.index') }}" class="flex flex-wrap items-end gap-3">
    <div class="w-full sm:w-64">
        <label for="service" class="mb-2 block text-label font-semibold text-ink">نوع کار</label>
        <select id="service" name="service" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
            <option value="">همه کارها</option>
            @foreach ($services as $key => $name)
                <option value="{{ $key }}" @selected(($service ?? null) === $key)>{{ $name }}</option>
            @endforeach
        </select>
    </div>
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
    <x-button type="submit" variant="secondary" icon="search">نمایش</x-button>
</form>
