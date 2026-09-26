<x-layouts.public title="ارتقا به اشتراک حرفه‌ای"
                  description="برای ادامه این کار به اشتراک حرفه‌ای نیاز دارید."
                  active="pro">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['اشتراک حرفه‌ای', route('monetization.plans')], ['ارتقا', null]]" />
    </x-slot:breadcrumb>

    <x-page-header :title="$feature === null ? 'اشتراک حرفه‌ای' : $feature->label()"
                   lede="برای ادامه، اشتراک حرفه‌ای لازم است." />

    {{-- متن دقیقاً همان چیزی است که لایه دسترسی گفت؛ این صفحه خودش تصمیم
         نمی‌گیرد و پیام نمی‌سازد. --}}
    @if ($decision !== null && $decision->denied())
        <x-alert tone="caution" title="ادامه ممکن نیست" class="mt-6">
            {{ $decision->message }}
        </x-alert>
    @elseif ($decision !== null)
        <x-alert tone="success" title="این کار برای شما باز است" class="mt-6">
            می‌توانید به صفحه قبل برگردید و ادامه دهید.
        </x-alert>
    @endif

    {{-- به‌جای یک خطای خشک، کاربر همان امکانی را که به سقفش خورده کنار Pro می‌بیند. --}}
    @if ($feature !== null)
        <x-card class="mt-6" :title="'رایگان در برابر Pro: '.$feature->label()">
            <dl class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-lg border border-line px-4 py-3.5">
                    <dt class="text-note font-semibold text-muted">رایگان</dt>
                    <dd class="mt-1 text-h4 text-ink">
                        @if ($freeAllowance !== null)
                            تا @fa($freeAllowance) مورد
                        @else
                            ندارد
                        @endif
                    </dd>
                </div>
                <div class="rounded-lg border-2 border-primary bg-primary-soft px-4 py-3.5">
                    <dt class="text-note font-semibold text-primary">Pro</dt>
                    <dd class="mt-1 text-h4 text-ink">{{ $freeAllowance !== null ? 'نامحدود' : 'دارد' }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-note text-muted">
                آنچه تا الان ساخته‌اید سر جایش می‌ماند؛ ارتقا فقط سقف را برمی‌دارد.
            </p>
        </x-card>
    @endif

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @foreach ($plans as $plan)
            <x-card size="lg">
                <p class="text-h3 text-ink">{{ $plan->title }}</p>
                <p class="mt-4 text-stat text-ink" dir="ltr" data-numeric>{{ $plan->price()->formatWithoutUnit() }}</p>
                <p class="mt-1 text-note text-muted">تومان، هر {{ $plan->billing_cycle->label() }}</p>

                @auth
                    <form method="POST" action="{{ route('monetization.checkout', $plan->slug) }}" class="mt-7">
                        @csrf
                        <x-payment-method :total="$plan->price()" class="mb-4" />
                        <x-button type="submit" variant="primary" block>خرید این پلن</x-button>
                    </form>
                @else
                    <x-button :href="Route::has('login') ? route('login') : '#'" variant="primary" block class="mt-7">
                        ورود و خرید
                    </x-button>
                @endauth
            </x-card>
        @endforeach
    </div>

    <p class="mt-6 text-note text-muted">
        <a href="{{ route('monetization.plans') }}" class="inline-flex min-h-touch items-center">همه امکانات اشتراک</a>
    </p>

</x-layouts.public>
