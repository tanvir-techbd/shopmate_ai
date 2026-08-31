@extends('layouts.base')

@section('title', 'Profile — ShopMate AI')

@section('extra_style')
<style>
    .profile-layout { max-width: 560px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
    .profile-header { display: flex; align-items: center; gap: 14px; }
    .profile-avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--brand); color: #fff; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .profile-header h2 { margin: 0; }
    .profile-header p { margin: 2px 0 0; }
    .toggle-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
    .toggle-row .label { font-weight: 600; font-size: 0.95rem; }
    .toggle-row .hint { font-size: 0.82rem; color: var(--muted); margin-top: 2px; }
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .track { position: absolute; inset: 0; background: #D1D5DB; border-radius: 999px; cursor: pointer; transition: background 0.15s; }
    .switch .track::before { content: ""; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: transform 0.15s; }
    .switch input:checked + .track { background: var(--brand); }
    .switch input:checked + .track::before { transform: translateX(20px); }
    .switch input:focus-visible + .track { outline: 2px solid var(--brand-dark); outline-offset: 2px; }
</style>
@endsection

@section('content')
<div class="profile-layout">
    <div class="profile-header">
        <div class="profile-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
        <div>
            <h2>{{ $user->name }}</h2>
            <p class="muted">{{ $user->is_admin ? 'Admin' : 'Member' }} since {{ $user->created_at->format('M Y') }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <div class="stat-grid">
        <div class="stat"><div class="value">{{ $stats['conversations'] }}</div><div class="label">Chats</div></div>
        <div class="stat"><div class="value">{{ $stats['orders'] }}</div><div class="label">Orders</div></div>
        <div class="stat"><div class="value">{{ $stats['price_alerts'] }}</div><div class="label">Active alerts</div></div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Account details</h3>

        @if ($errors->has('name') || $errors->has('email'))
            <div class="errors">
                @foreach ($errors->get('name') as $error){{ $error }}<br>@endforeach
                @foreach ($errors->get('email') as $error){{ $error }}<br>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')
            <div class="field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <button type="submit" class="btn">Save changes</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Search Preferences</h3>

        <form method="POST" action="{{ route('profile.preferences') }}">
            @csrf
            @method('PATCH')
            <div class="toggle-row">
                <div>
                    <div class="label">Include international stores</div>
                    <div class="hint">Also search stores that ship from abroad (longer delivery, ships internationally). Off by default - domestic Bangladeshi stores only.</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="include_international_stores" value="1" onchange="this.form.requestSubmit()" {{ $user->include_international_stores ? 'checked' : '' }}>
                    <span class="track"></span>
                </label>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Change password</h3>

        @if ($errors->has('current_password') || $errors->has('password'))
            <div class="errors">
                @foreach ($errors->get('current_password') as $error){{ $error }}<br>@endforeach
                @foreach ($errors->get('password') as $error){{ $error }}<br>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PATCH')
            <div class="field">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="field">
                <label for="password">New password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6">
            </div>
            <button type="submit" class="btn">Change password</button>
        </form>
    </div>
</div>
@endsection
