@extends('layouts.app')
@section('title', 'Lupa kata sandi')
@section('full')
<main class="auth-wrap">
    <div class="card auth-card">
        <h1>Lupa kata sandi</h1>
        <p class="muted">Masukkan email akunmu. Kami kirim tautan untuk membuat kata sandi baru.</p>
        @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
        <form method="post" action="{{ route('password.email') }}" class="stack">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <button class="btn" style="width:100%">Kirim tautan</button>
            <p class="small muted" style="text-align:center"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
        </form>
    </div>
</main>
@endsection
