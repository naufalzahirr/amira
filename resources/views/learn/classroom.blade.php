@extends('layouts.app')
@section('title', $classroom->name)
@section('content')
<a href="{{ auth()->user()->isTeacher() ? route('guru.kelas.show', $classroom) : route('belajar.index') }}" class="small">← Kembali</a>
<div class="row spread" style="margin-top:8px">
    <div>
        <h1>{{ $classroom->name }}</h1>
        <p class="muted">{{ $classroom->description ?: 'Pilih mentor untuk mulai bertanya.' }}</p>
    </div>
    @if (! is_null($used))
        <div class="card card-flat" style="padding:12px 16px;min-width:220px">
            <div class="small muted">Jatah pertanyaan hari ini</div>
            <b>{{ $used }} / {{ $classroom->daily_limit_per_student }}</b>
            <div class="meter {{ $used >= $classroom->daily_limit_per_student ? 'warn' : '' }}" style="margin-top:6px">
                <i style="width:{{ min(100, $used / max(1, $classroom->daily_limit_per_student) * 100) }}%"></i>
            </div>
        </div>
    @endif
</div>

@if ($mentors->isEmpty())
    <div class="empty" style="margin-top:20px">
        <img src="{{ asset('img/amira-mascot.png') }}" alt="">
        <p>Guru belum menyiapkan mentor di kelas ini.</p>
    </div>
@else
    <div class="grid grid-3" style="margin-top:20px">
        @foreach ($mentors as $mentor)
            <a class="card mentor-card" href="{{ route('chat.show', $mentor) }}">
                <div class="row" style="gap:12px">
                    <x-mentor-avatar :mentor="$mentor" />
                    <h3 style="margin:0">{{ $mentor->name }}</h3>
                </div>
                <p class="tagline">{{ $mentor->tagline ?: 'Mentor dari gurumu.' }}</p>
                <div class="row" style="gap:6px">
                    @if ($mentor->hint_ladder)<span class="badge">Petunjuk bertahap</span>@endif
                    @if ($mentor->materials_count)<span class="badge">{{ $mentor->materials_count }} materi</span>@endif
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
