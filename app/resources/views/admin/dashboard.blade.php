@extends('layouts.base')

@section('title', 'Admin — ShopMate AI')

@section('main_class', 'wide')

@section('extra_style')
<style>
    .page-head { margin-bottom: 24px; }
    .page-head h2 { margin: 0 0 4px; font-size: 1.5rem; }
    .page-head p { margin: 0; color: var(--text-muted); font-size: 0.9rem; }

    .stat-grid { grid-template-columns: repeat(auto-fit, minmax(178px, 1fr)); }
    .stat { display: flex; align-items: flex-start; gap: 12px; }
    .stat .icon { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--brand-tint); color: var(--brand-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat .icon svg { width: 17px; height: 17px; }

    .section-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: 30px; margin-bottom: 4px; }
    .section-head h3 { margin: 0; color: var(--brand-dark); font-size: 1.05rem; font-weight: 700; }
    .section-head .count { font-size: 0.78rem; color: var(--text-muted); font-weight: 600; background: var(--surface-sunken); border-radius: 999px; padding: 2px 10px; }
    .section-hint { margin: 0 0 12px; font-size: 0.83rem; color: var(--text-muted); }

    .pill { display: inline-flex; align-items: center; gap: 5px; font-size: 0.72rem; font-weight: 700; border-radius: 999px; padding: 3px 10px; white-space: nowrap; }
    .pill.success { background: var(--success-bg); color: var(--success); }
    .pill.neutral { background: var(--surface-sunken); color: var(--text-muted); }
    .pill.intl { background: #EEF2FF; color: #4338CA; }
    .pill.domestic { background: var(--brand-tint); color: var(--brand-dark); }
    .pill.score { background: var(--warning-bg); color: var(--warning); }

    .dupe-row { display: flex; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--border); }
    .dupe-row:first-child { padding-top: 2px; }
    .dupe-row:last-child { border-bottom: none; padding-bottom: 2px; }
    .dupe-col { flex: 1; min-width: 0; }
    .dupe-col .title { font-weight: 700; font-size: 0.9rem; }
    .dupe-col .meta { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
    .dupe-vs { color: var(--text-faint); font-size: 0.75rem; font-weight: 700; }
    .dupe-actions { display: flex; flex-direction: column; gap: 6px; }
    .dupe-actions form { display: inline; }
</style>
@endsection

@section('content')
<div class="page-head">
    <h2>Admin Dashboard</h2>
    <p>Catalogue health, duplicate review, and demand signals across every connected store.</p>
</div>

<div class="stat-grid">
    <div class="stat">
        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
        <div><div class="value">{{ $stats['users'] }}</div><div class="label">Users</div></div>
    </div>
    <div class="stat">
        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
        <div><div class="value">{{ $stats['products'] }}</div><div class="label">Products</div></div>
    </div>
    <div class="stat">
        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg></span>
        <div><div class="value">{{ $stats['stores'] }}</div><div class="label">Stores</div></div>
    </div>
    <div class="stat">
        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg></span>
        <div><div class="value">{{ $stats['orders'] }}</div><div class="label">Orders</div></div>
    </div>
    <div class="stat">
        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <div><div class="value">{{ $stats['searches'] }}</div><div class="label">Searches logged</div></div>
    </div>
    <div class="stat">
        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></span>
        <div><div class="value">{{ $stats['pre_orders'] }}</div><div class="label">Pre-order requests</div></div>
    </div>
    <div class="stat">
        <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 2v10l7-7"/></svg></span>
        <div><div class="value">{{ $stats['avg_confidence'] !== null ? $stats['avg_confidence'] : '—' }}</div><div class="label">Avg. AI confidence</div></div>
    </div>
</div>

@if (session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="section-head">
    <h3>Possible Duplicate Products</h3>
    <span class="count">{{ $duplicates->count() }} pending</span>
</div>
<p class="section-hint">Pairs the matcher flagged as possibly the same product - review and merge, or dismiss.</p>
<div class="card">
    @forelse ($duplicates as $dupe)
        <div class="dupe-row">
            <div class="dupe-col">
                <div class="title">{{ $dupe->productA->canonical_title }}</div>
                <div class="meta">{{ $dupe->productA->brand ?? '—' }} &middot; {{ $dupe->productA->category }} &middot; {{ $dupe->productA->prices->pluck('store.name')->implode(', ') }}</div>
            </div>
            <span class="dupe-vs">VS</span>
            <div class="dupe-col">
                <div class="title">{{ $dupe->productB->canonical_title }}</div>
                <div class="meta">{{ $dupe->productB->brand ?? '—' }} &middot; {{ $dupe->productB->category }} &middot; {{ $dupe->productB->prices->pluck('store.name')->implode(', ') }}</div>
            </div>
            <span class="pill score">{{ number_format($dupe->similarity_score, 2) }} similarity</span>
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
        <thead><tr><th>Store</th><th>Status</th><th>Origin</th><th>Listings</th><th>Last price check</th></tr></thead>
        <tbody>
        @foreach ($storeHealth as $store)
            <tr>
                <td>{{ $store['name'] }}</td>
                <td><span class="pill {{ $store['is_active'] ? 'success' : 'neutral' }}">{{ $store['is_active'] ? 'Active' : 'Inactive' }}</span></td>
                <td><span class="pill {{ $store['origin'] === 'international' ? 'intl' : 'domestic' }}">{{ $store['origin'] === 'international' ? '🌍 International' : 'Domestic' }}</span></td>
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

<div class="section-head">
    <h3>Pre-order Requests</h3>
    <span class="count">{{ $preOrderRequests->count() }} logged</span>
</div>
<p class="section-hint">Products people searched for that the catalogue doesn't have — candidates for a new provider or a manually added listing.</p>
<div class="card">
    <table class="data">
        <thead><tr><th>User</th><th>Query</th><th>Category</th><th>Brand</th><th>When</th></tr></thead>
        <tbody>
        @forelse ($preOrderRequests as $request)
            <tr>
                <td>{{ $request->user->name ?? '—' }}</td>
                <td>{{ $request->query }}</td>
                <td>{{ $request->category ?? '—' }}</td>
                <td>{{ $request->brand ?? '—' }}</td>
                <td>{{ $request->created_at->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No pre-order requests yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
