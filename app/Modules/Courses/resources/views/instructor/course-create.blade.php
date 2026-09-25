<x-layouts.workspace title="دوره تازه" nav="instructor-courses">

    <x-page-header title="دوره تازه" lede="پس از ساخت، جلسه‌ها را اضافه کنید و برای بررسی بفرستید." />

    <x-card size="lg" class="mt-8">
        @if ($errors->any())
            <x-alert tone="error" title="فرم را دوباره بررسی کنید" class="mb-6">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('courses.instructor.courses.store') }}" class="flex flex-col gap-5">
            @csrf

            <x-field name="title" label="عنوان دوره" :value="old('title')" required />

            <div>
                <label for="description" class="mb-2 block text-label font-bold text-ink">توضیح</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('description') }}</textarea>
            </div>

            <x-field name="price" label="قیمت (تومان)" :value="old('price')" numeric required hint="مثلاً ۳۰۰۰۰۰؛ برای دوره رایگان ۰ بنویسید" />

            <div>
                <x-button type="submit" variant="primary">ساخت دوره</x-button>
            </div>
        </form>
    </x-card>

</x-layouts.workspace>
