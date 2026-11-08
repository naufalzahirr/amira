@extends('layouts.app')
@section('title', 'Belajar')
@section('content')
<div class="row spread" style="align-items:flex-end">
    <div>
        <h1>Halo, {{ auth()->user()->name }}</h1>
        <p class="muted">Pilih kelas, lalu pilih mentor yang sesuai topikmu.</p>
    </div>
    <form method="post" action="{{ route('belajar.join') }}" class="row" style="gap:8px">
        @csrf
        <input type="text" name="code" value="{{ old('code') }}" placeholder="Kode kelas" aria-label="Kode kelas"
               style="width:160px;text-transform:uppercase;letter-spacing:.12em;font-weight:700" maxlength="12" required>
        <button class="btn btn-yellow">Gabung</button>
    </form>
</div>
@error('code')<div class="flash flash-error">{{ $message }}</div>@enderror

@if ($classrooms->isEmpty())
    <div class="empty" style="margin-top:24px">
        <img src="{{ asset('img/amira-mascot.png') }}" alt="">
        <h2 style="color:var(--ink)">Belum ada kelas</h2>
        <p>Minta kode kelas ke gurumu, lalu masukkan di kotak <b>Kode kelas</b> di atas.</p>
    </div>
@else
    <div class="grid grid-3" style="margin-top:24px">
        @foreach ($classrooms as $classroom)
            <a class="card mentor-card" href="{{ route('belajar.kelas', $classroom) }}">
                <div class="row" style="gap:12px">
                    <div class="avatar c-{{ \App\Models\Mentor::COLORS[$loop->index % 5] }}">{{ mb_strtoupper(mb_substr($classroom->name, 0, 1)) }}</div>
                    <div>
                        <h3 style="margin:0">{{ $classroom->name }}</h3>
                        <div class="small muted">{{ $classroom->teacher->name }}</div>
                    </div>
                </div>
                @if ($classroom->description)<p class="tagline">{{ $classroom->description }}</p>@endif
                <span class="badge">{{ $classroom->mentors_count }} mentor</span>
            </a>
        @endforeach
    </div>
@endif
@endsection
