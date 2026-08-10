@extends('layouts.base')

@section('title', 'Alerts — ShopMate AI')

@section('extra_style')
<style>
    .alert-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #E5E7EB; }
    .alert-row:last-child { border-bottom: none; }
    .alert-title { flex: 1; }
    .alert-title .name { font-weight: 600; }
    .alert-title .desc { font-size: 0.8rem; color: var(--muted); }
    .badge { font-size: 0.72rem; border-radius: 999px; padding: 3px 10px; white-space: nowrap; }
    .badge.active { background: #FEF3C7; color: #92400E; }
    .badge.triggered { background: #D1FAE5; color: #065F46; }
</style>
@endsection

@section('content')
<h2 style="margin-top:0;">Price &amp; Stock Alerts</h2>
<p class="muted">Set these from a product card in the chat ("Notify me on price drop" / "Notify me when back in stock"). Checked periodically by the <code>alerts:check</code> scheduled command.</p>

@if (session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="card">
    @forelse ($alerts as $alert)
        <div class="alert-row">
            <div class="alert-title">
                <div class="name">{{ $alert->product->canonical_title }}</div>
                <div class="desc">
                    @if ($alert->type === 'price_drop')
                        Notify when price drops below ৳{{ number_format($alert->target_price) }}
                    @else
                        Notify when back in stock
                    @endif
                    @if ($alert->triggered_at)
                        &middot; triggered {{ $alert->triggered_at->diffForHumans() }}
                    @endif
                </div>
            </div>
            <span class="badge {{ $alert->is_active ? 'active' : 'triggered' }}">
                {{ $alert->is_active ? 'Watching' : 'Triggered' }}
            </span>
            <form method="POST" action="{{ route('alerts.destroy', $alert) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn small danger">Remove</button>
            </form>
        </div>
    @empty
        <p class="muted">No alerts yet.</p>
    @endforelse
</div>
@endsection
