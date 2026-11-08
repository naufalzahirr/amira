@extends('layouts.app')
@section('title', 'Masuk')
@section('full')
<main class="auth-wrap">
    <div class="card auth-card">
        <h1>Masuk</h1>
        <p class="muted">Lanjutkan belajar bersama mentormu.</p>
        <form method="post" action="{{ route('login') }}" class="stack">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
            <div class="row spread">
                <label class="check" style="margin:0"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <a href="{{ route('password.request') }}" class="small">Lupa kata sandi?</a>
            </div>
            <button class="btn" style="width:100%">Masuk</button>
            <p class="small muted" style="text-align:center">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
        </form>
    </div>
</main>
@endsection
