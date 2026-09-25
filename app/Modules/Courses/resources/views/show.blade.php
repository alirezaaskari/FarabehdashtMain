<x-layouts.public :seo="$seo" active="courses">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['دوره‌ها', route('courses.index')], [$course->title, null]]" />
    </x-slot:breadcrumb>

    <x-page-header :title="$course->title" :lede="$course->description">
        <x-slot:actions>
            @auth
                <form method="POST" action="{{ route('courses.enroll', $course) }}" class="flex flex-col gap-3">
                    @csrf
                    <x-payment-method :total="$course->price()" />
                    <x-button type="submit" variant="primary">
                        {{ $course->isFree() ? 'ثبت‌نام رایگان' : 'ثبت‌نام — '.$course->priceLabel() }}
                    </x-button>
                </form>
            @else
                <x-button :href="Route::has('login') ? route('login') : route('home')" variant="primary">
                    ورود برای ثبت‌نام — {{ $course->priceLabel() }}
                </x-button>
            @endauth
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <x-alert tone="error" title="ثبت‌نام انجام نشد" class="mt-6">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-alert>
    @endif

    @if ($course->sessions->isNotEmpty())
        <x-card size="lg" class="mt-8">
            <h2 class="text-h4 text-ink">سرفصل دوره</h2>

            <ol class="mt-4 list-inside list-decimal divide-y divide-line">
                @foreach ($course->sessions as $session)
                    <li class="py-3 text-label font-bold text-ink">{{ $session->title }}</li>
                @endforeach
            </ol>
        </x-card>
    @endif

</x-layouts.public>
