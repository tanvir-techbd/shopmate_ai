@extends('layouts.base')

@section('title', 'Login — ShopMate AI')

@section('content')
<div class="card auth-box">
    <h2>Log in</h2>

    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Log in</button>
    </form>

    <p class="muted" style="margin-top:16px;">Demo account: <code>demo@shopmate.test</code> / <code>password</code></p>
    <p class="switch-link">No account? <a href="{{ route('register') }}">Register</a></p>
</div>
@endsection
