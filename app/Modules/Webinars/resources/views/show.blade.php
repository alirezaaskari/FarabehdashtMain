@php use App\Modules\Webinars\Domain\Enums\WebinarStatus; use App\Support\JalaliDate; @endphp

<x-layouts.public :title="$webinar->title"
                  :description="\Illuminate\Support\Str::limit($webinar->description, 155)"
                  :canonical="route('webinars.show', $webinar->slug)"
                  active="webinars">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['رویداد و وبینار', route('webinars.index')], [$webinar->title, null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="webinars-show" :title="$webinar->title" :lede="'با '.$webinar->instructor_name" />

    @if (session('status'))
        <x-alert tone="success" class="mt-6">{{ session('status') }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert tone="error" title="انجام نشد" class="mt-6">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-alert>
    @endif

    @if ($webinar->status === WebinarStatus::Cancelled)
        <x-alert tone="caution" class="mt-6">این رویداد لغو شده است. مبلغ ثبت‌نام‌های پولی به کیف پول ثبت‌نام‌کننده‌ها برگشته است.</x-alert>
    @endif

    <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0">
            <p class="whitespace-pre-line text-copy text-body">{{ $webinar->description }}</p>

            @if ($webinar->hasEnded() && $webinar->recording_url)
                <h2 class="mt-10 text-h3 text-ink">ضبط جلسه</h2>
                <p class="mt-3 text-copy text-body">ضبط این رویداد به شکل دوره در دسترس است.</p>
                <x-button :href="url($webinar->recording_url)" variant="secondary" class="mt-4">دیدن ضبط جلسه</x-button>
            @endif
        </div>

        <aside class="flex flex-col gap-6 lg:border-s lg:border-line lg:ps-8">
            <dl class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <dt class="text-note text-muted">زمان شروع</dt>
                    <dd class="mt-1 text-h4 text-ink">{{ JalaliDate::longWithTime($webinar->starts_at) }}</dd>
                </div>
                <div>
                    <dt class="text-note text-muted">مدت</dt>
                    <dd class="mt-1 text-h4 text-ink">@fa($webinar->duration_minutes) دقیقه</dd>
                </div>
                <div>
                    <dt class="text-note text-muted">جای خالی</dt>
                    <dd class="mt-1 text-h4 text-ink">@fa($remaining) از @fa($webinar->capacity)</dd>
                </div>
            </dl>

            <p class="text-h3 text-ink">{{ $webinar->isFree() ? 'رایگان' : $webinar->price()->format() }}</p>

            @if ($webinar->status === WebinarStatus::Cancelled)
                {{-- لغوشده: نه ثبت‌نام نه ورود --}}
            @elseif ($registered)
                <x-badge tone="primary" icon="check">ثبت‌نام کرده‌اید</x-badge>
                @if ($webinar->isJoinOpen())
                    <x-button :href="route('webinars.join', $webinar->slug)" variant="primary" rel="noreferrer">ورود به جلسه</x-button>
                @elseif (! $webinar->hasEnded())
                    <p class="text-note text-muted">پیوند ورود از {{ JalaliDate::longWithTime($webinar->joinOpensAt()) }} همین‌جا باز می‌شود. پیش از شروع هم یادآور می‌گیرید.</p>
                @endif
            @elseif ($webinar->hasStarted())
                <p class="text-note text-muted">ثبت‌نام این رویداد بسته شده است.</p>
            @elseif ($remaining === 0)
                <p class="text-note text-muted">ظرفیت این رویداد پر شده است.</p>
            @elseif (! $paidOpen)
                <p class="text-note text-muted">ثبت‌نام رویدادهای پولی در حال حاضر بسته است.</p>
            @else
                @auth
                    <form method="POST" action="{{ route('webinars.register', $webinar->slug) }}" class="flex flex-col gap-3">
                        @csrf
                        @unless ($webinar->isFree())
                            <x-payment-method :total="$webinar->price()" />
                        @endunless
                        <x-button type="submit" variant="primary">{{ $webinar->isFree() ? 'ثبت‌نام رایگان' : 'ثبت‌نام و پرداخت' }}</x-button>
                    </form>
                @else
                    <x-button :href="Route::has('login') ? route('login') : url('/')" variant="primary">ورود برای ثبت‌نام</x-button>
                @endauth
            @endif

            <p class="text-note text-muted">
                جلسه روی سرویس برگزاری بیرونی برگزار می‌شود. فرابهداشت هیچ اطلاعاتی از شما به آن سرویس نمی‌فرستد؛
                ممکن است آن سرویس خودش نامی برای نمایش در جلسه بپرسد.
            </p>
        </aside>
    </div>

</x-layouts.public>
