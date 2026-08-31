@extends('layouts.base')

@section('title', 'Orders — ShopMate AI')

@section('extra_style')
<style>
    .badge { font-size: 0.72rem; border-radius: 999px; padding: 3px 10px; white-space: nowrap; }
    .badge.confirmed_redirected { background: #DBEAFE; color: #1E40AF; }
    .badge.cancelled { background: #FEE2E2; color: #991B1B; }
    .badge.pending_confirmation { background: #FEF3C7; color: #92400E; }
</style>
@endsection

@section('content')
<h2 style="margin-top:0;">Your Orders</h2>
<p class="muted">Every order here was created only after you explicitly confirmed it. ShopMate AI never places or pays for anything on the store's own site - this list is a record only.</p>

@if (session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="card">
    <table class="data">
        <thead>
            <tr><th>Product</th><th>Store</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td>{{ $order->product->canonical_title }}</td>
                <td>{{ $order->store->name }}</td>
                <td>৳{{ number_format($order->price + $order->delivery_charge) }}</td>
                <td><span class="badge {{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span></td>
                <td>{{ $order->created_at->diffForHumans() }}</td>
                <td>
                    @if ($order->product_url && $order->product_url !== '#')
                        <a href="{{ $order->product_url }}" target="_blank" rel="noopener noreferrer" class="btn small secondary">View product ↗</a>
                    @endif
                    @if ($order->status === 'confirmed_redirected')
                        <form method="POST" action="{{ route('orders.cancel', $order) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn small danger">Cancel</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No orders yet. Ask ShopMate AI to find something, then hit "Buy".</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
