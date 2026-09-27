@php use App\Support\JalaliDate; @endphp

<ul class="flex list-none flex-col gap-3 ps-0">
    @foreach ($orders as $order)
        <li>
            <a href="{{ route('consulting.orders.show', $order->uuid) }}"
               class="flex min-h-touch flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-4 no-underline hover:bg-surface-2 hover:no-underline">
                <span class="flex min-w-0 flex-col gap-1">
                    <span class="text-h4 text-ink">{{ $order->service->title }}</span>
                    <span class="text-note text-muted">
                        @unless ($incoming) {{ $order->service->profile->display_name }} · @endunless
                        {{ JalaliDate::short($order->paid_at ?? $order->created_at) }} · {{ $order->price()->format() }}
                    </span>
                </span>
                <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
            </a>
        </li>
    @endforeach
</ul>

<div class="mt-6">{{ $orders->links() }}</div>
