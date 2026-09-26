<x-layouts.public title="آمادگی آزمون"
                  description="بانک سؤال آزمون‌های بهداشت حرفه‌ای با تمرین موضوعی، آزمون زمان‌دار و کارنامه ضعف‌ها؛ هر بسته ۱۰ سؤال نمونه رایگان دارد."
                  :canonical="route('exam_prep.index')"
                  active="exam-prep">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['آمادگی آزمون', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="آمادگی آزمون"
                   lede="بانک سؤال دسته‌بندی‌شده به موضوع و سختی، تمرین با پاسخ فوری، آزمون زمان‌دار و کارنامه‌ای که نشان می‌دهد کجا را مرور کنید." />

    <x-page-help topic="exam-prep" class="mt-5" />

    <div class="mt-8">
        @if ($packs->isEmpty())
            <x-empty-state icon="list"
                           title="هنوز بسته‌ای منتشر نشده"
                           description="بسته‌های آمادگی آزمون به‌زودی این‌جا می‌آیند." />
        @else
            <ul class="flex list-none flex-col divide-y divide-line border-y border-line ps-0">
                @foreach ($packs as $pack)
                    <li>
                        <a href="{{ route('exam_prep.show', $pack->slug) }}"
                           class="flex min-h-touch flex-col gap-1 py-5 no-underline hover:no-underline md:flex-row md:items-center md:justify-between md:gap-6">
                            <span class="min-w-0">
                                <span class="block text-h4 text-ink">{{ $pack->title }}</span>
                                <span class="mt-1 block text-note text-muted">
                                    {{ $pack->exam_name }} · @fa($pack->published_questions_count) سؤال
                                </span>
                            </span>
                            <span class="shrink-0 text-label font-semibold text-ink">{{ $pack->price()->format() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

</x-layouts.public>
