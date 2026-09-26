<x-layouts.public :title="$bundle->title"
                  :description="\Illuminate\Support\Str::limit($bundle->description, 155)"
                  :canonical="route('bundles.show', $bundle->slug)"
                  active="bundles">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['بسته‌های راه‌حل', route('bundles.index')], [$bundle->title, null]]" />
    </x-slot:breadcrumb>

    <x-page-header :title="$bundle->title" />

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

    <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0">
            <p class="whitespace-pre-line text-copy text-body">{{ $bundle->description }}</p>

            <h2 class="mt-10 text-h3 text-ink">داخل این بسته</h2>
            <ul class="mt-4 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
                @foreach ($rows as $row)
                    <li class="flex min-h-touch flex-wrap items-center justify-between gap-3 py-3">
                        @if ($row['component'] === null)
                            <span class="text-label text-muted">این جزء دیگر در دسترس نیست.</span>
                        @else
                            <span class="min-w-0">
                                @if ($row['component']->url !== null && ($bought || $row['owned']))
                                    <a href="{{ $row['component']->url }}" class="inline-flex min-h-touch items-center text-label text-ink">{{ $row['component']->title }}</a>
                                @else
                                    <span class="block text-label text-ink">{{ $row['component']->title }}</span>
                                @endif
                                <span class="block text-note text-muted">{{ $row['kind'] }}</span>
                            </span>
                            <span class="flex items-center gap-3">
                                @if ($row['owned'])
                                    <x-badge tone="primary" icon="check">در حساب شما</x-badge>
                                @endif
                                <span class="text-note text-muted">{{ $row['component']->listPrice->format() }}</span>
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <aside class="flex flex-col gap-6 lg:border-s lg:border-line lg:ps-8">
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-note text-muted">جمع قیمت جداگانه</dt>
                    <dd class="mt-1 text-h4 text-ink">{{ $listTotal->format() }}</dd>
                </div>
                <div>
                    <dt class="text-note text-muted">صرفه‌جویی</dt>
                    <dd class="mt-1 text-h4 text-ink">{{ $saving->format() }}</dd>
                </div>
            </dl>

            <p class="text-h3 text-ink">{{ $bundle->price()->format() }}</p>

            @if ($bought)
                <x-badge tone="primary" icon="check">خریده‌اید</x-badge>
                <p class="text-note text-muted">همه اجزا در حساب شما فعال است. از فهرست کنار هر جزء به آن بروید.</p>
            @else
                @auth
                    @if ($onSale)
                        <form method="POST" action="{{ route('bundles.purchase', $bundle->slug) }}" class="flex flex-col gap-3">
                            @csrf
                            <x-payment-method :total="$bundle->price()" />
                            <x-button type="submit" variant="primary">خرید بسته</x-button>
                        </form>
                    @else
                        <p class="text-note text-muted">فروش این بسته در حال حاضر بسته است.</p>
                    @endif
                @else
                    <x-button :href="Route::has('login') ? route('login') : url('/')" variant="primary">ورود برای خرید</x-button>
                @endauth

                <p class="text-note text-muted">
                    اگر یکی از اجزا را از قبل دارید، با خرید بسته دوباره برایتان ثبت نمی‌شود و بقیه اجزا فعال می‌شوند.
                </p>
            @endif
        </aside>
    </div>

</x-layouts.public>
