@extends('layouts.app')
@section('title', 'Privasi')
@section('page-class', 'page-narrow')
@section('content')
<h1>Privasi di AMIRA</h1>
<p class="muted">Ringkasan cara AMIRA memakai data belajarmu.</p>
<div class="card">@include('partials.privacy-points')</div>
<div class="card" style="margin-top:16px">
    <h2>Data yang disimpan</h2>
    <ul class="small" style="padding-left:1.2em;color:var(--ink-2)">
        <li>Akun: nama, email, dan pernyataan usia 18+.</li>
        <li>Percakapan dan gambar lampiran. Kamu bisa menghapusnya lewat <i>Hapus percakapan ini</i> di halaman chat.</li>
        <li>Label otomatis untuk Radar Kelas: topik, tingkat petunjuk, masalah selesai, dan hasil jelaskan balik.</li>
        <li>API key (bila kamu memasangnya) disimpan terenkripsi dan tidak pernah ditampilkan utuh.</li>
    </ul>
</div>
@endsection
