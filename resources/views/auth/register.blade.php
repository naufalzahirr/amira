@extends('layouts.app')
@section('title', 'Daftar')
@section('full')
<main class="auth-wrap">
    <div class="card auth-card">
        <h1>Buat akun</h1>
        <p class="muted">Satu akun untuk semua kelasmu.</p>
        <form method="post" action="{{ route('register') }}" class="stack">
            @csrf
            <div class="field">
                <label>Saya mendaftar sebagai</label>
                <div class="role-pick">
                    <label><input type="radio" name="role" value="murid" @checked(old('role', 'murid') === 'murid')><span>Peserta belajar<small>Bergabung lewat kode kelas</small></span></label>
                    <label><input type="radio" name="role" value="guru" @checked(old('role') === 'guru')><span>Guru<small>Membuat kelas dan mentor</small></span></label>
                </div>
                @error('role')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="name">Nama</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">Kata sandi <span class="hint">minimal 8 karakter</span></label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi kata sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <div class="note">
                <label class="check" style="margin:0"><input type="checkbox" name="adult" value="1" @checked(old('adult'))> Saya berusia 18 tahun atau lebih.</label>
                <div class="small muted" style="margin-top:6px">AMIRA memakai layanan AI (Gemini dan Groq) yang hanya boleh dipakai pengguna 18 tahun ke atas.</div>
                @error('adult')<div class="error">{{ $message }}</div>@enderror
            </div>
            <button class="btn" style="width:100%">Daftar</button>
            <p class="small muted" style="text-align:center">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </form>
    </div>
</main>
@endsection
