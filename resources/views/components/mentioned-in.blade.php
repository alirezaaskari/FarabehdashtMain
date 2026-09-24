@props(['items', 'title' => 'مقاله‌هایی که به این اشاره دارند'])

{{--
    فهرست «مرتبط» از موتور پیوند داخلی. اگر خالی باشد، هیچ چیز چاپ نمی‌شود:
    عنوان بدون فهرست از نبودنش بدتر است.
--}}
@if ($items !== [])
    <section data-print="hide" aria-labelledby="mentioned-in-heading" {{ $attributes->class('rounded-xl border border-line bg-surface px-6 py-5') }}>
        <h2 id="mentioned-in-heading" class="mb-2 text-h4 text-ink">{{ $title }}</h2>

        <ul class="flex list-none flex-col ps-0">
            @foreach ($items as $item)
                <li>
                    <a href="{{ $item->url }}"
                       class="flex min-h-touch items-center gap-2 text-label font-semibold text-primary no-underline hover:no-underline">
                        <x-icon name="book" :size="16" />
                        {{ $item->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
