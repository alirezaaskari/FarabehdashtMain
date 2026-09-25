<x-layouts.workspace :title="$article?->title ?? 'نوشته تازه'"
                     :heading="$article?->title ?? 'نوشته تازه'"
                     :lede="$article ? $article->type->label().' · '.$article->status->label() : 'پیش‌نویس تا وقتی نفرستاده‌اید فقط برای شما دیده می‌شود.'"
                     nav="writing">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['نوشته‌های دانشنامه', route('encyclopedia.writing.index')], [$article?->title ?? 'نوشته تازه', null]]" />
    </x-slot:breadcrumb>

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($article?->review_note)
        <div class="mb-6"><x-alert tone="caution" title="یادداشت مدیر برای اصلاح">{{ $article->review_note }}</x-alert></div>
    @endif

    @unless ($editable)
        <div class="mb-6">
            <x-alert tone="info">این نوشته برای بازبینی فرستاده شده و از این‌جا ویرایش نمی‌شود. اگر مدیر برگرداندش، دوباره قابل ویرایش می‌شود.</x-alert>
        </div>
    @endunless

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <x-card class="min-w-0">
            <form method="POST"
                  action="{{ $article ? route('encyclopedia.writing.update', $article->uuid) : route('encyclopedia.writing.store') }}"
                  class="flex flex-col gap-5">
                @csrf
                @if ($article) @method('PUT') @endif

                <fieldset @disabled(! $editable) class="flex flex-col gap-5">
                    <x-field name="title" label="عنوان" :value="old('title', $article?->title)" required :error="$errors->first('title')" />

                    <div>
                        <label for="type" class="mb-2 block text-label font-bold text-ink">نوع محتوا</label>
                        <select id="type" name="type" required
                                class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" @selected(old('type', $article?->type->value ?? 'article') === $type->value)>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @foreach ([
                        ['summary', 'خلاصه', 3, $article?->summary, 'دو یا سه جمله؛ در فهرست و جست‌وجو دیده می‌شود.', 'rtl'],
                        ['body', 'متن', 18, $body, 'هر سطر «## عنوان» یک بخش تازه باز می‌کند.', 'rtl'],
                        ['references', 'منابع', 5, $references, 'هر منبع یک سطر: عنوان | ناشر | ویرایش | سال | نشانی', 'auto'],
                    ] as [$name, $label, $rows, $value, $hint, $dir])
                        <div>
                            <label for="{{ $name }}" class="mb-2 block text-label font-bold text-ink">{{ $label }}</label>
                            <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" dir="{{ $dir }}"
                                      aria-describedby="{{ $name }}-hint"
                                      @if ($errors->has($name)) aria-invalid="true" @endif
                                      class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old($name, $value) }}</textarea>
                            <p id="{{ $name }}-hint" class="mt-1 text-note text-muted">{{ $hint }}</p>
                            @error($name)
                                <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    @if ($editable)
                        <div>
                            <x-button type="submit" variant="primary">ذخیره پیش‌نویس</x-button>
                        </div>
                    @endif
                </fieldset>
            </form>

            @if ($article && $editable)
                <form method="POST" action="{{ route('encyclopedia.writing.submit', $article->uuid) }}"
                      class="mt-6 border-t border-line pt-5">
                    @csrf
                    <p class="mb-3 text-label text-muted">پیش از ارسال، آخرین تغییرها را ذخیره کنید. پس از ارسال تا تصمیم مدیر ویرایش نمی‌شود.</p>
                    <x-button type="submit" variant="secondary">ارسال برای بازبینی</x-button>
                </form>
            @endif
        </x-card>

        <x-card title="راهنمای نوشتن برای دانشنامه">
            @include('encyclopedia::writing.guide')
        </x-card>
    </div>

</x-layouts.workspace>
