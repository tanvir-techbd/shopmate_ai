@extends('layouts.base')

@section('title', 'Admin — ShopMate AI')

@section('main_class', 'wide')

@section('extra_style')
<style>
    .dupe-row { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid #E5E7EB; }
    .dupe-row:last-child { border-bottom: none; }
    .dupe-col { flex: 1; }
    .dupe-col .title { font-weight: 600; font-size: 0.9rem; }
    .dupe-col .meta { font-size: 0.78rem; color: var(--muted); }
    .dupe-score { font-size: 0.72rem; background: #FEF3C7; color: #92400E; border-radius: 999px; padding: 3px 10px; white-space: nowrap; }
    .dupe-actions { display: flex; flex-direction: column; gap: 6px; }
    .dupe-actions form { display: inline; }
</style>
@endsection

@section('content')
<h2 style="margin-top:0;">Admin Dashboard</h2>

<div class="stat-grid">
    <div class="stat"><div class="value">{{ $stats['users'] }}</div><div class="label">Users</div></div>
    <div class="stat"><div class="value">{{ $stats['products'] }}</div><div class="label">Products</div></div>
    <div class="stat"><div class="value">{{ $stats['stores'] }}</div><div class="label">Stores</div></div>
    <div class="stat"><div class="value">{{ $stats['orders'] }}</div><div class="label">Orders</div></div>
    <div class="stat"><div class="value">{{ $stats['searches'] }}</div><div class="label">Searches logged</div></div>
    <div class="stat"><div class="value">{{ $stats['avg_confidence'] !== null ? $stats['avg_confidence'] : '—' }}</div><div class="label">Avg. AI confidence (last 50 replies)</div></div>
</div>

@if (session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<h3 class="section-title">Possible Duplicate Products ({{ $duplicates->count() }})</h3>
<div class="card">
    @forelse ($duplicates as $dupe)
        <div class="dupe-row">
            <div class="dupe-col">
                <div class="title">{{ $dupe->productA->canonical_title }}</div>
                <div class="meta">{{ $dupe->productA->brand ?? '—' }} &middot; {{ $dupe->productA->category }} &middot; {{ $dupe->productA->prices->pluck('store.name')->implode(', ') }}</div>
            </div>
            <div class="dupe-col">
                <div class="title">{{ $dupe->productB->canonical_title }}</div>
                <div class="meta">{{ $dupe->productB->brand ?? '—' }} &middot; {{ $dupe->productB->category }} &middot; {{ $dupe->productB->prices->pluck('store.name')->implode(', ') }}</div>
            </div>
            <span class="dupe-score">{{ number_format($dupe->similarity_score, 2) }} similarity</span>
            <div class="dupe-actions">
                <form method="POST" action="{{ route('admin.duplicates.merge', $dupe) }}">
                    @csrf
                    <button type="submit" class="btn small">Merge (keep left)</button>
                </form>
                <form method="POST" action="{{ route('admin.duplicates.dismiss', $dupe) }}">
                    @csrf
                    <button type="submit" class="btn small secondary">Not a duplicate</button>
                </form>
            </div>
        </div>
    @empty
        <p class="muted">No possible duplicates flagged. Run <code>php artisan products:find-duplicates</code> after importing from providers.</p>
    @endforelse
</div>

<h3 class="section-title">Store Health</h3>
<div class="card">
    <table class="data">
        <thead><tr><th>Store</th><th>Status</th><th>Listings</th><th>Last price check</th></tr></thead>
        <tbody>
        @foreach ($storeHealth as $store)
            <tr>
                <td>{{ $store['name'] }}</td>
                <td>{{ $store['is_active'] ? 'Active' : 'Inactive' }}</td>
                <td>{{ $store['listing_count'] }}</td>
                <td>{{ $store['last_checked'] ? \Illuminate\Support\Carbon::parse($store['last_checked'])->diffForHumans() : 'never' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<h3 class="section-title">Catalogue by Category</h3>
<div class="card">
    <table class="data">
        <thead><tr><th>Category</th><th>Products</th></tr></thead>
        <tbody>
        @foreach ($topCategories as $row)
            <tr><td>{{ $row->category }}</td><td>{{ $row->total }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>

<h3 class="section-title">Recent Searches</h3>
<div class="card">
    <table class="data">
        <thead><tr><th>User</th><th>Query</th><th>Results</th><th>When</th></tr></thead>
        <tbody>
        @forelse ($recentSearches as $search)
            <tr>
                <td>{{ $search->user->name ?? '—' }}</td>
                <td>{{ $search->query }}</td>
                <td>{{ $search->result_count }}</td>
                <td>{{ $search->created_at->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">No searches logged yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
