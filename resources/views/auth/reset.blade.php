@extends('layouts.app')
@section('title', 'Kata sandi baru')
@section('full')
<main class="auth-wrap">
    <div class="card auth-card">
        <h1>Kata sandi baru</h1>
        <form method="post" action="{{ route('password.update') }}" class="stack">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">Kata sandi baru <span class="hint">minimal 8 karakter</span></label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button class="btn" style="width:100%">Simpan kata sandi</button>
        </form>
    </div>
</main>
@endsection
