@extends('layouts.base')

@section('title', 'Shopping List — ShopMate AI')

@section('extra_style')
<style>
    .item-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #E5E7EB; }
    .item-row:last-child { border-bottom: none; }
    .item-title { flex: 1; }
    .item-title .name { font-weight: 600; }
    .item-title .price { font-size: 0.8rem; color: var(--muted); }
    .item-row.purchased .item-title .name { text-decoration: line-through; color: var(--muted); }
    .qty-form, .toggle-form, .delete-form { display: inline; }
    .qty-badge { background: #EEF2F2; border-radius: 999px; padding: 2px 10px; font-size: 0.8rem; }
    .add-item-form { display: flex; gap: 8px; margin-top: 16px; }
    .add-item-form input { flex: 1; padding: 9px 11px; border: 1px solid #D1D5DB; border-radius: 8px; }
</style>
@endsection

@section('content')
<h2 style="margin-top:0;">{{ $list->name }}</h2>

@if (session('status'))
    <div class="status">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="errors">{{ $errors->first() }}</div>
@endif

<div class="card">
    @forelse ($list->items as $item)
        <div class="item-row {{ $item->is_purchased ? 'purchased' : '' }}">
            <form class="toggle-form" method="POST" action="{{ route('shopping-list.items.update', $item) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_purchased" value="{{ $item->is_purchased ? 0 : 1 }}">
                <input type="checkbox" onchange="this.form.submit()" {{ $item->is_purchased ? 'checked' : '' }}>
            </form>

            <div class="item-title">
                <div class="name">
                    {{ $item->product->canonical_title ?? $item->note ?? 'Item' }}
                </div>
                @if ($item->product)
                    @php $cheapest = $item->product->prices->sortBy(fn($p) => (float) $p->price + (float) $p->delivery_charge)->first(); @endphp
                    @if ($cheapest)
                        <div class="price">Cheapest: {{ $cheapest->store->name }} &mdash; ৳{{ number_format($cheapest->price + $cheapest->delivery_charge) }}</div>
                    @endif
                @endif
            </div>

            <span class="qty-badge">x{{ $item->quantity }}</span>

            <form class="delete-form" method="POST" action="{{ route('shopping-list.items.destroy', $item) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn small danger">Remove</button>
            </form>
        </div>
    @empty
        <p class="muted">Your shopping list is empty. Add products from the chat, or add a plain note below.</p>
    @endforelse

    <form class="add-item-form" method="POST" action="{{ route('shopping-list.items.store') }}">
        @csrf
        <input type="text" name="note" placeholder="Add a custom item, e.g. 'buy vegetables'" required>
        <button type="submit" class="btn">Add</button>
    </form>
</div>
@endsection
