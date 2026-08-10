@extends('layouts.base')

@section('title', 'Register — ShopMate AI')

@section('content')
<div class="card auth-box">
    <h2>Create an account</h2>

    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6">
        </div>
        <button type="submit" class="btn">Register</button>
    </form>

    <p class="switch-link">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</div>
@endsection
