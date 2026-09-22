@php
    use Illuminate\Support\Facades\Route;
@endphp

<x-layouts.public title="دوره‌های من" description="مدیریت دوره‌های آموزشی شما.">

    <x-page-header title="دوره‌های من" lede="هر دوره پیش از انتشار باید تأیید مدیر را بگیرد.">
        <x-slot:actions>
            <x-button :href="route('courses.instructor.sales')" variant="secondary">گزارش فروش</x-button>
            @if (Route::has('commerce.vendor.settlement'))
                <x-button :href="route('commerce.vendor.settlement')" variant="secondary">تسویه</x-button>
            @endif
            <x-button :href="route('courses.instructor.courses.create')" variant="primary">دوره تازه</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-8">
        @if ($courses->isEmpty())
            <x-empty-state icon="book"
                           title="هنوز دوره‌ای نساخته‌اید"
                           description="اولین دوره خود را اضافه کنید.">
                <x-slot:action>
                    <x-button :href="route('courses.instructor.courses.create')" variant="primary" size="sm">
                        دوره تازه
                    </x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <x-card size="lg">
                <ul class="divide-y divide-line">
                    @foreach ($courses as $course)
                        <li class="flex items-center justify-between gap-4 py-4">
                            <div>
                                <a href="{{ route('courses.instructor.courses.edit', $course) }}"
                                   class="text-sm font-bold text-ink no-underline hover:no-underline">
                                    {{ $course->title }}
                                </a>
                                <p class="mt-1 text-sm text-muted">{{ $course->price()->format() }}</p>
                            </div>

                            <x-badge :tone="$course->status->tone()">{{ $course->status->label() }}</x-badge>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>

</x-layouts.public>
