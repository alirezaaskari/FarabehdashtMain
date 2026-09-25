<x-layouts.public title="دوره‌ها"
                  description="دوره‌های آموزشی بهداشت حرفه‌ای، از مدرسان تأییدشده."
                  :canonical="route('courses.index')"
                  active="courses">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['دوره‌ها', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="دوره‌ها" lede="دوره‌های آموزشی بررسی‌شده پیش از انتشار." />

    <x-page-help topic="courses" class="mt-5" />

    <x-card size="lg" class="mt-8">
        <form method="GET" action="{{ route('courses.index') }}">
            <label for="q" class="mb-2 block text-label font-bold text-ink">جست‌وجو</label>
            <div class="flex gap-2.5">
                <input id="q" type="search" name="q" value="{{ $query }}"
                       placeholder="مثلاً: ایمنی پایه"
                       class="h-field min-w-0 grow rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                <x-button type="submit" variant="primary" class="shrink-0">جست‌وجو</x-button>
            </div>
        </form>
    </x-card>

    <div class="mt-6">
        @if ($courses->isEmpty())
            <x-empty-state icon="book"
                           title="دوره‌ای پیدا نشد"
                           :description="$query !== '' ? 'عبارت دیگری امتحان کنید.' : 'هنوز دوره‌ای منتشر نشده است.'">
                @if ($query !== '')
                    <x-slot:action>
                        <x-button :href="route('courses.index')" size="sm">پاک‌کردن جست‌وجو</x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <x-card>
                        <a href="{{ route('courses.show', $course->slug) }}" class="no-underline hover:no-underline">
                            <p class="text-copy font-bold text-ink">{{ $course->title }}</p>
                        </a>
                        <p class="mt-2 text-label text-muted">{{ $course->price()->format() }}</p>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>

</x-layouts.public>
