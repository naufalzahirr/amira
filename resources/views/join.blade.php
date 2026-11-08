@extends('layouts.app')
@section('title', $classroom ? 'Gabung '.$classroom->name : 'Kelas tidak ditemukan')
@section('full')
<main class="auth-wrap">
    <div class="card auth-card" style="text-align:center">
        <img src="{{ asset('img/amira-mascot.png') }}" alt="" style="width:120px">
        @if ($classroom)
            <p class="muted" style="margin:6px 0 0">Kamu diundang ke kelas</p>
            <h1 style="margin:4px 0">{{ $classroom->name }}</h1>
            <p class="muted">oleh {{ $classroom->teacher->name }}</p>
            <div class="stack" style="margin-top:18px">
                <a class="btn btn-yellow" style="width:100%" href="{{ route('register') }}">Buat akun &amp; gabung</a>
                <a class="btn btn-ghost" style="width:100%" href="{{ route('login') }}">Sudah punya akun? Masuk</a>
            </div>
            <p class="small muted" style="margin-top:16px">AMIRA untuk peserta belajar berusia 18 tahun ke atas.</p>
        @else
            <h1 style="margin-top:8px">Kelas tidak ditemukan</h1>
            <p class="muted">Tautan atau kodenya mungkin sudah diganti guru. Minta tautan terbaru ke gurumu.</p>
            <a class="btn" href="{{ route('home') }}">Ke beranda</a>
        @endif
    </div>
</main>
@endsection
