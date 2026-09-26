@php
    use App\Modules\Commerce\Domain\Enums\ProductStatus;
    use App\Modules\Commerce\Domain\Enums\VersionReviewStatus;
@endphp

<x-layouts.workspace :title="$product->title" nav="vendor-products">

    <x-page-header :title="$product->title" :lede="$product->price()->format()">
        <x-slot:actions>
            <x-badge :tone="$product->status->tone()">{{ $product->status->label() }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <x-alert tone="error" title="مشکلی پیش آمد" class="mt-6">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @if ($product->status === ProductStatus::Rejected && $product->review_note)
        <x-alert tone="caution" title="دلیل رد شدن" class="mt-6">{{ $product->review_note }}</x-alert>
    @endif

    <x-card size="lg" class="mt-6">
        <h2 class="text-h4 text-ink">نسخه‌ها</h2>

        @if ($product->status === ProductStatus::Published)
            <x-alert tone="info" class="mt-4">
                نسخه تازه پیش از رسیدن به خریداران به تأیید مدیر نیاز دارد؛ تا آن زمان خریداران نسخه تأییدشده قبلی را دانلود می‌کنند.
            </x-alert>
        @endif

        @if ($product->versions->isEmpty())
            <p class="mt-3 text-label text-muted">هنوز فایلی برای این محصول ثبت نشده است.</p>
        @else
            <ul class="mt-4 divide-y divide-line">
                @foreach ($product->versions as $version)
                    <li class="py-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <p class="text-label font-semibold text-ink" dir="ltr" data-numeric>{{ $version->version }}</p>
                            @if ($version->review_status !== VersionReviewStatus::Approved)
                                <x-badge :tone="$version->review_status->tone()">{{ $version->review_status->label() }}</x-badge>
                            @endif
                        </div>
                        @if ($version->changelog)
                            <p class="mt-1 text-label text-muted">{{ $version->changelog }}</p>
                        @endif
                        @if ($version->review_status === VersionReviewStatus::Rejected && $version->review_note)
                            <p class="mt-1 text-label text-danger">دلیل رد: {{ $version->review_note }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($product->status !== ProductStatus::Retired)
            <form method="POST" action="{{ route('commerce.vendor.products.versions', $product) }}"
                  enctype="multipart/form-data" class="mt-6 flex flex-col gap-4 border-t border-line pt-6">
                @csrf

                <x-field name="version" label="شماره نسخه" placeholder="مثلاً ۱٫۰٫۰" required />

                <div>
                    <label for="changelog" class="mb-2 block text-label font-semibold text-ink">تغییرات این نسخه</label>
                    <textarea id="changelog" name="changelog" rows="3"
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink"></textarea>
                </div>

                <div>
                    <label for="file" class="mb-2 block text-label font-semibold text-ink">فایل</label>
                    <input id="file" type="file" name="file" required
                           class="block w-full text-label text-muted">
                </div>

                <div>
                    <x-button type="submit" variant="secondary">افزودن نسخه</x-button>
                </div>
            </form>
        @endif
    </x-card>

    <x-card size="lg" class="mt-6">
        <h2 class="text-h4 text-ink">انتشار</h2>

        <div class="mt-4 flex flex-wrap gap-3">
            @if (in_array($product->status, [ProductStatus::Draft, ProductStatus::Rejected], true))
                <form method="POST" action="{{ route('commerce.vendor.products.submit', $product) }}">
                    @csrf
                    <x-button type="submit" variant="primary">ارسال برای بررسی</x-button>
                </form>
            @endif

            @if ($product->status === ProductStatus::Published)
                <form method="POST" action="{{ route('commerce.vendor.products.retire', $product) }}">
                    @csrf
                    <x-button type="submit" variant="danger">خروج از فروش</x-button>
                </form>
            @endif
        </div>
    </x-card>

</x-layouts.workspace>
