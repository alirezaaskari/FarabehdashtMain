<x-layouts.public title="پذیرش قوانین تازه" noindex>

    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <x-card size="lg" level="1" title="قوانین فرابهداشت به‌روز شده است"
                subtitle="پیش از ادامه، خلاصه تغییرات را بخوانید و نسخه تازه را بپذیرید. متن کامل هر سند در پیوندش آمده است.">

            <ul class="flex flex-col gap-5">
                @foreach ($pending as $version)
                    <li class="rounded-lg border border-line bg-surface-2 px-5 py-4">
                        <a href="{{ route('workspace.legal.version', [$version->document->value, $version->version]) }}"
                           class="inline-flex min-h-touch items-center text-label font-bold" target="_blank">
                            {{ $version->document->label() }} — نسخه @fa($version->version)
                        </a>
                        @if ($version->summary)
                            <p class="mt-1 text-copy text-ink">{{ $version->summary }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if ($impersonating)
                <div class="mt-6">
                    <x-alert tone="caution">در حالت مشاهده به‌عنوان کاربر، قوانین به‌جای کاربر پذیرفته نمی‌شود.</x-alert>
                </div>
            @else
                <form method="POST" action="{{ route('workspace.legal.accept.store') }}" class="mt-6 flex flex-col gap-4">
                    @csrf

                    <label class="flex min-h-touch items-center gap-3 text-copy text-ink">
                        <input type="checkbox" name="agree" value="1" class="size-5 shrink-0 accent-primary" required>
                        متن تازه را خواندم و می‌پذیرم.
                    </label>

                    @error('agree')
                        <p class="text-note text-danger" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap gap-3">
                        <x-button type="submit" variant="primary">پذیرش و ادامه</x-button>
                    </div>
                </form>
            @endif

            @if (Route::has('identity.signout'))
                <form method="POST" action="{{ route('identity.signout') }}" class="mt-4">
                    @csrf
                    <x-button type="submit" variant="ghost" size="sm">نمی‌پذیرم؛ خروج از حساب</x-button>
                </form>
            @endif
        </x-card>
    </div>

</x-layouts.public>
