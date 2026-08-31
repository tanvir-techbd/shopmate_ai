@extends('layouts.base')

@section('title', 'Spending Habits — ShopMate AI')

@section('main_class', 'wide')

@section('extra_style')
<style>
    .tip-list { list-style: none; margin: 0; padding: 0; }
    .tip-list li { padding: 10px 0; border-bottom: 1px solid #E5E7EB; font-size: 0.88rem; }
    .tip-list li:last-child { border-bottom: none; }
    .tip-list li::before { content: "💡"; margin-right: 8px; }
    .bar-row { margin-bottom: 14px; }
    .bar-row:last-child { margin-bottom: 0; }
    .bar-label { display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px; }
    .bar-label .name { font-weight: 600; }
    .bar-label .value { color: var(--muted); }
    .bar-track { background: #EEF2F2; border-radius: 999px; height: 10px; overflow: hidden; }
    .bar-fill { background: var(--brand); height: 100%; border-radius: 999px; }
    .trend .bar-fill { background: var(--brand-dark); }
    .change-up { color: var(--danger); }
    .change-down { color: #067647; }
</style>
@endsection

@section('content')
<h2 style="margin-top:0;">Spending Habits</h2>
<p class="muted">Based on orders you've confirmed through ShopMate AI. This reflects what you told the app you're buying, not a connection to your bank or the stores' own systems.</p>

<div class="stat-grid">
    <div class="stat"><div class="value">৳{{ number_format($totalSpent) }}</div><div class="label">Total spent</div></div>
    <div class="stat"><div class="value">{{ $orderCount }}</div><div class="label">Orders placed</div></div>
    <div class="stat"><div class="value">৳{{ number_format($avgOrderValue) }}</div><div class="label">Average order value</div></div>
    <div class="stat">
        <div class="value">
            ৳{{ number_format($thisMonthSpent) }}
            @if ($monthOverMonthChange !== null)
                <span style="font-size:0.9rem" class="{{ $monthOverMonthChange >= 0 ? 'change-up' : 'change-down' }}">
                    ({{ $monthOverMonthChange >= 0 ? '+' : '' }}{{ $monthOverMonthChange }}%)
                </span>
            @endif
        </div>
        <div class="label">This month{{ $monthOverMonthChange !== null ? ' vs. last' : '' }}</div>
    </div>
</div>

@if ($cancelledCount > 0)
    <p class="muted">({{ $cancelledCount }} cancelled order(s) excluded from these totals.)</p>
@endif

<h3 class="section-title">Money Management Tips</h3>
<div class="card">
    <ul class="tip-list">
        @foreach ($tips as $tip)
            <li>{{ $tip }}</li>
        @endforeach
    </ul>
</div>

<h3 class="section-title">Spending by Category</h3>
<div class="card">
    @forelse ($byCategory as $row)
        <div class="bar-row">
            <div class="bar-label">
                <span class="name">{{ $row['category'] }}</span>
                <span class="value">৳{{ number_format($row['total']) }} ({{ $row['percent'] }}%)</span>
            </div>
            <div class="bar-track"><div class="bar-fill" style="width: {{ $row['percent'] }}%;"></div></div>
        </div>
    @empty
        <p class="muted">No orders yet.</p>
    @endforelse
</div>

<h3 class="section-title">Monthly Trend (last 6 months)</h3>
<div class="card trend">
    @foreach ($monthlyTrend as $row)
        <div class="bar-row">
            <div class="bar-label">
                <span class="name">{{ $row['label'] }}</span>
                <span class="value">৳{{ number_format($row['total']) }}</span>
            </div>
            <div class="bar-track"><div class="bar-fill" style="width: {{ round($row['total'] / $maxMonthlyTotal * 100, 1) }}%;"></div></div>
        </div>
    @endforeach
</div>
@endsection
