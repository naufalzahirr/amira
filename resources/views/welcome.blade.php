@extends('layouts.app')
@section('title', 'Mentor AI yang dibentuk guru')
@section('content')
<section class="hero">
    <div>
        <span class="badge">Untuk peserta belajar 18 tahun ke atas</span>
        <h1 style="margin-top:14px">Mentor AI yang dibentuk oleh gurumu.</h1>
        <p class="lead">Guru menyusun mentor untuk tiap topik: tujuan belajar, gaya membimbing, dan materi rujukan. Peserta bertanya, mencoba, lalu menjelaskan balik dengan kata-kata sendiri.</p>
        <ul class="points">
            <li>Petunjuk bertahap, bukan contekan jawaban.</li>
            <li>Materi dari guru jadi rujukan utama mentor.</li>
            <li>Radar Kelas menunjukkan topik yang paling sering membuat buntu.</li>
        </ul>
        <div class="row">
            <a class="btn btn-yellow" href="{{ route('register') }}">Mulai sekarang</a>
            <a class="btn btn-ghost" href="{{ route('login') }}">Saya sudah punya akun</a>
        </div>
    </div>
    <div class="hero-art">
        <img src="{{ asset('img/amira-mascot.png') }}" alt="Maskot AMIRA">
    </div>
</section>
@endsection
