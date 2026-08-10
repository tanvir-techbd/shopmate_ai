@extends('layouts.base')

@section('title', 'Confirm order — ShopMate AI')

@section('content')
<div class="card" style="max-width: 480px; margin: 20px auto;">
    <h2 style="margin-top:0;">Confirm before we redirect you</h2>
    <p class="muted">ShopMate AI never places or pays for an order automatically. Review the details, then confirm to be redirected to the store.</p>

    <table class="data" style="margin: 16px 0;">
        <tr><th>Product</th><td>{{ $listing->product->canonical_title }}</td></tr>
        <tr><th>Store</th><td>{{ $listing->store->name }}</td></tr>
        <tr><th>Listing title</th><td>{{ $listing->store_title }}</td></tr>
        <tr><th>Price</th><td>৳{{ number_format($listing->price) }}</td></tr>
        <tr><th>Delivery</th><td>৳{{ number_format($listing->delivery_charge) }}</td></tr>
        <tr><th>Total</th><td><b>৳{{ number_format($listing->price + $listing->delivery_charge) }}</b></td></tr>
    </table>

    <form method="POST" action="{{ route('orders.store') }}" style="display:flex; gap:10px;">
        @csrf
        <input type="hidden" name="product_price_id" value="{{ $listing->id }}">
        <button type="submit" class="btn">Confirm &amp; go to store</button>
        <a href="{{ url()->previous() }}" class="btn secondary">Cancel</a>
    </form>
</div>
@endsection
