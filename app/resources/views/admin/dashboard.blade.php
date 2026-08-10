@extends('layouts.base')

@section('title', 'Admin — ShopMate AI')

@section('main_class', 'wide')

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
