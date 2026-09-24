@php use App\Support\JalaliDate; @endphp

<x-layouts.public :seo="$seo">

    <x-page-header :title="$document->label()"
                   :lede="$version
                       ? 'نسخه '.\App\Support\PersianDigits::from($version->version).' · در اثر از '.JalaliDate::long($version->effective_at)
                       : null" />

    @if ($version === null)
        <div class="mt-8">
            <x-empty-state icon="file"
                           title="این صفحه هنوز منتشر نشده است"
                           description="متن این صفحه به‌زودی منتشر می‌شود. برای پرسش، از صفحه تماس با ما استفاده کنید." />
        </div>
    @else
        @unless ($isLatest)
            <div class="mt-6">
                <x-alert tone="caution" title="این نسخه قدیمی است">
                    متن جاری را در
                    <a href="{{ route('workspace.legal.show', $document->value) }}" class="inline-flex min-h-touch items-center">نسخه تازه</a>
                    بخوانید.
                </x-alert>
            </div>
        @endunless

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <x-card size="lg">
                <article class="flex flex-col gap-4">
                    @foreach ($version->blocks() as $block)
                        @if ($block['heading'])
                            <h2 class="mt-2 text-h3 text-ink">{{ $block['text'] }}</h2>
                        @else
                            <p class="text-copy whitespace-pre-line text-ink">{{ $block['text'] }}</p>
                        @endif
                    @endforeach
                </article>
            </x-card>

            @if ($history->count() > 1)
                <x-card title="نسخه‌ها" tone="muted">
                    <ol class="flex flex-col gap-3">
                        @foreach ($history as $item)
                            <li>
                                <a href="{{ route('workspace.legal.version', [$document->value, $item->version]) }}"
                                   @if ($item->is($version)) aria-current="page" @endif
                                   class="inline-flex min-h-touch flex-col justify-center text-label font-semibold">
                                    نسخه @fa($item->version) · {{ JalaliDate::long($item->effective_at) }}
                                </a>
                                @if ($item->summary)
                                    <p class="text-note text-muted">{{ $item->summary }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </x-card>
            @endif
        </div>
    @endif

</x-layouts.public>
