{{-- استان و شهر از فهرست ثابت config/regions.php؛ $regions کلید استان => [name, cities]. --}}
<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="province" class="mb-2 block text-label font-semibold text-ink">استان</label>
        <select id="province" name="province" @required(! ($optional ?? false)) class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
            <option value="">انتخاب کنید</option>
            @foreach ($regions as $key => $region)
                <option value="{{ $key }}" @selected($province === $key)>{{ $region['name'] }}</option>
            @endforeach
        </select>
        @error('province')
            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="city" class="mb-2 block text-label font-semibold text-ink">شهر</label>
        <select id="city" name="city" @required(! ($optional ?? false)) class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
            <option value="">انتخاب کنید</option>
            @foreach ($regions as $region)
                <optgroup label="{{ $region['name'] }}">
                    @foreach ($region['cities'] as $key => $name)
                        <option value="{{ $key }}" @selected($city === $key)>{{ $name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('city')
            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
        @enderror
    </div>
</div>
