@extends('layouts.base')

@section('title', 'Confirm order — ShopMate AI')

@php $hasRealUrl = $listing->product_url && $listing->product_url !== '#'; @endphp

@section('extra_style')
<style>
    .order-flow { max-width: 480px; margin: 20px auto; display: flex; flex-direction: column; gap: 16px; }
    .step-label { display: inline-block; font-size: 0.72rem; font-weight: 600; background: var(--brand); color: #fff; border-radius: 999px; padding: 3px 10px; margin-bottom: 10px; }
    .preview-body { display: flex; gap: 14px; }
    .preview-photo { flex-shrink: 0; width: 96px; height: 96px; border-radius: 10px; overflow: hidden; background: #fff; border: 1px solid #E5E7EB; display: flex; align-items: center; justify-content: center; }
    .preview-photo img { width: 100%; height: 100%; object-fit: contain; }
    .preview-photo .photo-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #EEF2F2; }
    .preview-photo .photo-placeholder svg { width: 34px; height: 34px; color: var(--brand); opacity: 0.5; }
    .preview-info { flex: 1; min-width: 0; }
    .preview-title { font-size: 1.05rem; font-weight: 600; margin: 0 0 4px; }
    .preview-meta { font-size: 0.82rem; color: var(--muted); margin-bottom: 10px; }
    .preview-meta span:not(:last-child)::after { content: " · "; }
</style>
@endsection

@section('content')
<div class="order-flow">
    <div class="card">
        <span class="step-label">Step 1 · Preview the product</span>
        <div class="preview-body">
            <div class="preview-photo">
                @if ($listing->image_url)
                    <img src="{{ $listing->image_url }}" alt="{{ $listing->product->canonical_title }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="photo-placeholder" style="display:none;">@include('chat._photo-icon')</div>
                @else
                    <div class="photo-placeholder">@include('chat._photo-icon')</div>
                @endif
            </div>
            <div class="preview-info">
                <p class="preview-title">{{ $listing->product->canonical_title }}</p>
                <p class="preview-meta">
                    <span>{{ $listing->store->name }}</span>
                    @if ($listing->store->origin === 'international')<span>🌍 Ships from abroad</span>@endif
                    @if ($listing->product->brand)<span>{{ $listing->product->brand }}</span>@endif
                    @if ($listing->product->category)<span>{{ $listing->product->category }}</span>@endif
                    @if ($listing->rating)<span>{{ $listing->rating }}★ ({{ $listing->review_count }})</span>@endif
                    <span>{{ $listing->in_stock ? 'In stock' : 'Out of stock' }}</span>
                </p>
                <p class="muted" style="margin-bottom: 14px;">Listed as: "{{ $listing->store_title }}"</p>

                @if ($hasRealUrl)
                    <a href="{{ $listing->product_url }}" target="_blank" rel="noopener noreferrer" class="btn secondary">View product on {{ $listing->store->name }} ↗</a>
                @else
                    <p class="muted">{{ $listing->store->name }} is a demo store in this MVP catalogue, so there's no live product page to preview.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <span class="step-label">Step 2 · Confirm order</span>
        <p class="muted">ShopMate AI never places or pays for an order automatically - it only records what you confirm here.</p>

        <table class="data" style="margin: 14px 0;">
            <tr><th>Price</th><td>৳{{ number_format($listing->price) }}</td></tr>
            <tr><th>Delivery</th><td>৳{{ number_format($listing->delivery_charge) }}</td></tr>
            <tr><th>Total</th><td><b>৳{{ number_format($listing->price + $listing->delivery_charge) }}</b></td></tr>
        </table>

        <form method="POST" action="{{ route('orders.store') }}" style="display:flex; gap:10px;">
            @csrf
            <input type="hidden" name="product_price_id" value="{{ $listing->id }}">
            <button type="submit" class="btn">Confirm order</button>
            <a href="{{ url()->previous() }}" class="btn secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
