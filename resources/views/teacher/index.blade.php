@extends('layouts.app')
@section('title', 'Kelas saya')
@section('content')
<h1>Kelas saya</h1>

@php $doneCount = collect($steps)->where('done', true)->count(); @endphp
@if ($doneCount < count($steps))
    <section class="card setup" style="margin:16px 0 4px">
        <div class="row spread">
            <h2 style="margin:0">Siap mengajar dengan AMIRA</h2>
            <span class="badge">{{ $doneCount }} dari {{ count($steps) }} selesai</span>
        </div>
        <div class="meter" style="margin:12px 0 4px"><i style="width:{{ $doneCount / count($steps) * 100 }}%"></i></div>
        <ol class="setup-steps">
            @php $nextFound = false; @endphp
            @foreach ($steps as $step)
                @php $isNext = ! $step['done'] && ! $nextFound; if ($isNext) { $nextFound = true; } @endphp
                <li class="{{ $step['done'] ? 'done' : ($isNext ? 'next' : '') }}">
                    <span class="setup-dot" aria-hidden="true">{{ $step['done'] ? '✓' : $loop->iteration }}</span>
                    <div class="grow">
                        <b>{{ $step['title'] }}</b>
                        <div class="small muted">{{ $step['hint'] }}</div>
                    </div>
                    @if ($isNext)<a class="btn btn-sm btn-yellow" href="{{ $step['url'] }}">Kerjakan</a>@endif
                </li>
            @endforeach
        </ol>
    </section>
@endif

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin:20px 0">
    <div class="stat"><b>{{ $usage['gemini'] }}</b><span>jawaban dari key Gemini Anda hari ini</span></div>
    <div class="stat"><b>{{ $usage['groq'] }}</b><span>jawaban dari cadangan Groq hari ini</span></div>
    <div class="stat"><b>{{ $usage['student'] }}</b><span>jawaban dari key milik murid hari ini</span></div>
    <div class="stat"><b>{{ number_format($usage['tokens'], 0, ',', '.') }}</b><span>token dari key Anda hari ini</span></div>
</div>
<p class="small muted">Kuota gratis Gemini dihitung per project Google untuk semua kelas Anda sekaligus, dan terisi ulang tengah malam waktu Pasifik (sekitar pukul 14.00–15.00 WIB).</p>

<div class="grid grid-2" style="margin-top:24px">
    <form method="post" action="{{ route('guru.kelas.store') }}" class="card stack" id="buat-kelas">
        @csrf
        <h2>Buat kelas baru</h2>
        <div class="field">
            <label for="name">Nama kelas</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Pemrograman Web — Angkatan 2026" required>
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="description">Keterangan <span class="hint">opsional</span></label>
            <input id="description" type="text" name="description" value="{{ old('description') }}" placeholder="Kelas Selasa, ruang lab 2">
        </div>
        <button class="btn btn-yellow">Buat kelas</button>
    </form>

    @foreach ($classrooms as $classroom)
        <a class="card mentor-card" href="{{ route('guru.kelas.show', $classroom) }}">
            <div class="row spread">
                <h2 style="margin:0">{{ $classroom->name }}</h2>
                <span class="code-pill" style="font-size:1rem">{{ $classroom->join_code }}</span>
            </div>
            @if ($classroom->description)<p class="tagline">{{ $classroom->description }}</p>@endif
            @foreach ($classroom->setupWarnings() as $warning)
                <div class="warn-line small">{{ $warning }}</div>
            @endforeach
            <div class="row" style="gap:6px">
                <span class="badge">{{ $classroom->students_count }} murid</span>
                <span class="badge">{{ $classroom->mentors_count }} mentor</span>
                <span class="badge">{{ $classroom->daily_limit_per_student }} pertanyaan/murid/hari</span>
            </div>
        </a>
    @endforeach
</div>
@endsection
