@extends('layouts.app')
@section('title', $classroom->name)
@section('content')
<a href="{{ route('guru.index') }}" class="small">← Semua kelas</a>
<div class="row spread" style="margin-top:8px;align-items:flex-start">
    <div class="grow" style="min-width:260px">
        <h1>{{ $classroom->name }}</h1>
        <p class="muted">{{ $classroom->description }}</p>
    </div>
    <div class="card card-flat invite">
        <div class="small muted">Ajak murid bergabung</div>
        <div class="row" style="margin-top:8px;gap:8px;flex-wrap:nowrap">
            <input type="text" readonly value="{{ route('gabung', $classroom->join_code) }}" id="join-url" aria-label="Tautan gabung kelas" onclick="this.select()">
            <button type="button" class="btn btn-sm" data-copy-join>Salin</button>
        </div>
        <div class="row" style="margin-top:10px;gap:8px">
            <span class="small muted">Kode</span>
            <span class="code-pill" style="font-size:1rem">{{ $classroom->join_code }}</span>
            <a class="btn btn-ghost btn-sm" href="{{ route('guru.kelas.qr', $classroom) }}" target="_blank" rel="noopener">Tampilkan QR</a>
            <form method="post" action="{{ route('guru.kelas.code', $classroom) }}" onsubmit="return confirm('Buat kode baru? Tautan dan kode lama tidak berlaku lagi.')">
                @csrf <button class="linkbtn small">Ganti kode</button>
            </form>
        </div>
    </div>
</div>

@foreach ($classroom->setupWarnings() as $warning)
    <div class="flash flash-error" style="margin:16px 0 0">{{ $warning }}</div>
@endforeach

<nav class="tabs">
    @foreach (['mentor' => 'Mentor', 'radar' => 'Radar Kelas', 'murid' => 'Murid ('.$students->count().')', 'pengaturan' => 'Pengaturan'] as $key => $label)
        <a href="{{ route('guru.kelas.show', [$classroom, 'tab' => $key]) }}" class="{{ $tab === $key ? 'on' : '' }}">{{ $label }}</a>
    @endforeach
</nav>

@if ($tab === 'mentor')
    <div class="row spread">
        <p class="muted" style="margin:0">Satu mentor untuk satu topik atau tugas, seperti Gem. Murid hanya melihat mentor yang aktif.</p>
        <a class="btn btn-yellow" href="{{ route('guru.mentor.create', $classroom) }}">+ Mentor baru</a>
    </div>
    @if ($mentors->isEmpty())
        <div class="empty" style="margin-top:20px">
            <img src="{{ asset('img/amira-mascot.png') }}" alt="">
            <h2 style="color:var(--ink)">Belum ada mentor</h2>
            <p>Mulai dari template, lalu sesuaikan dengan topik minggu ini.</p>
            <div class="row" style="justify-content:center;gap:8px;margin-top:10px">
                @foreach (config('amira_mentor.templates') as $key => $t)
                    <a class="btn btn-ghost btn-sm" href="{{ route('guru.mentor.create', [$classroom, 'template' => $key]) }}">{{ $t['title'] }}</a>
                @endforeach
            </div>
        </div>
    @else
        <div class="grid grid-3" style="margin-top:20px">
            @foreach ($mentors as $mentor)
                <div class="card mentor-card">
                    <div class="row" style="gap:12px">
                        <x-mentor-avatar :mentor="$mentor" />
                        <div>
                            <h3 style="margin:0">{{ $mentor->name }}</h3>
                            <span class="small {{ $mentor->is_active ? '' : 'muted' }}">{{ $mentor->is_active ? 'Aktif' : 'Disembunyikan' }}</span>
                        </div>
                    </div>
                    <p class="tagline">{{ $mentor->tagline ?: 'Belum ada deskripsi singkat.' }}</p>
                    <div class="row" style="gap:6px">
                        @if ($mentor->hint_ladder)<span class="badge">Petunjuk bertahap</span>@endif
                        <span class="badge">{{ $mentor->materials_count }} materi</span>
                    </div>
                    <div class="row" style="margin-top:auto">
                        <a class="btn btn-sm" href="{{ route('guru.mentor.edit', $mentor) }}">Atur</a>
                        <a class="btn btn-ghost btn-sm" href="{{ route('chat.show', $mentor) }}">Coba sebagai murid</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endif

@if ($tab === 'radar')
    <p class="muted">Ringkasan {{ \App\Services\RadarKelas::DAYS }} hari terakhir. Isi percakapan murid tidak ditampilkan; semua angka berasal dari label yang diberikan mentor pada tiap jawaban.</p>
    <div class="grid stat-grid" style="margin:16px 0 20px">
        <div class="stat"><b>{{ $radar['week'] }}</b><span>pertanyaan · {{ $radar['today'] }} hari ini</span></div>
        <div class="stat"><b>{{ $radar['active'] }} / {{ $students->count() }}</b><span>murid aktif</span></div>
        <div class="stat"><b>{{ $radar['solved'] }}</b><span>masalah selesai</span></div>
        <div class="stat"><b>{{ $radar['hint3'] }}</b><span>kali sampai petunjuk 3</span></div>
        <div class="stat"><b>{{ $radar['understood_yes'] }}<small class="muted"> / {{ $radar['understood_yes'] + $radar['understood_no'] }}</small></b><span>jelaskan balik dinilai paham</span></div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2>Perlu disapa</h2>
            <p class="small muted">Murid yang sering sampai petunjuk terakhir, belum tepat saat menjelaskan balik, atau banyak bertanya tanpa ada yang selesai.</p>
            @forelse ($radar['attention'] as $row)
                <div class="attention-row">
                    <b>{{ $row['name'] }}</b>
                    <div class="row" style="gap:6px;margin-top:4px">
                        @foreach ($row['reasons'] as $reason)<span class="badge badge-warn">{{ $reason }}</span>@endforeach
                    </div>
                </div>
            @empty
                <p class="muted" style="margin-top:12px">Belum ada murid yang perlu perhatian khusus.</p>
            @endforelse
        </div>

        <div class="card">
            <div class="row spread">
                <h2 style="margin:0">Usulan mini-lesson</h2>
                <form method="post" action="{{ route('guru.kelas.suggest', $classroom) }}" data-busy-form>
                    @csrf
                    <button class="btn btn-yellow btn-sm">{{ $classroom->radar_suggestion ? 'Susun ulang' : 'Susun usulan' }}</button>
                </form>
            </div>
            <p class="small muted" style="margin-top:6px">Disusun AI dari angka per topik dan tujuan mentor saja, tanpa nama murid dan isi chat. Memakai key Anda.</p>
            @if ($classroom->radar_suggestion)
                <div class="suggestion">{!! \App\Models\Message::renderMarkdown($classroom->radar_suggestion) !!}</div>
                <div class="small muted">Disusun {{ $classroom->radar_suggestion_at?->locale('id')->diffForHumans() }}</div>
            @else
                <p class="muted" style="margin-top:12px">Belum ada usulan. Tekan <b>Susun usulan</b> setelah murid mulai bertanya.</p>
            @endif
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <h2>Topik</h2>
        <p class="small muted">Bahan mini-lesson. Kolom merah menandai topik yang paling menyulitkan.</p>
        @if ($radar['topics']->isEmpty())
            <p class="muted">Belum ada data.</p>
        @else
            @php $max = $radar['topics']->max('total'); @endphp
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Topik</th><th style="width:30%">Pertanyaan</th><th>Sampai petunjuk 3</th><th>Selesai</th><th>Jelaskan balik</th></tr></thead>
                    <tbody>
                    @foreach ($radar['topics'] as $row)
                        <tr>
                            <td><b>{{ $row->topic }}</b></td>
                            <td><div class="row" style="gap:8px;flex-wrap:nowrap"><div class="meter grow"><i style="width:{{ $row->total / $max * 100 }}%"></i></div><span>{{ $row->total }}</span></div></td>
                            <td class="{{ $row->hint3 > 0 ? 'hot' : 'muted' }}">{{ $row->hint3 }}</td>
                            <td>{{ $row->solved }}</td>
                            <td>
                                @if ($row->understood_yes + $row->understood_no)
                                    <span class="{{ $row->understood_no > $row->understood_yes ? 'hot' : '' }}">{{ $row->understood_yes }} paham · {{ $row->understood_no }} belum</span>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="grid grid-2" style="margin-top:16px">
        <div class="card">
            <h2>Per murid</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Nama</th><th>Hari ini</th><th>7 hari</th><th>Selesai</th><th>Petunjuk 3</th><th>Terakhir</th></tr></thead>
                    <tbody>
                    @forelse ($students as $student)
                        @php $s = $radar['perStudent'][$student->id] ?? null; @endphp
                        <tr>
                            <td>{{ $student->name }}</td>
                            <td>{{ $s->today_total ?? 0 }}/{{ $classroom->daily_limit_per_student }}</td>
                            <td>{{ $s->week_total ?? 0 }}</td>
                            <td>{{ $s->solved ?? 0 }}</td>
                            <td class="{{ ($s->hint3 ?? 0) >= 2 ? 'hot' : '' }}">{{ $s->hint3 ?? 0 }}</td>
                            <td class="muted small">{{ $s ? \Illuminate\Support\Carbon::parse($s->last_at)->locale('id')->diffForHumans() : 'belum bertanya' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Belum ada murid.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <h2>Mentor yang dipakai</h2>
            @forelse ($radar['perMentor'] as $row)
                <div class="row spread" style="padding:8px 0;border-top:1px solid var(--line)"><span>{{ $row->name }}</span><b>{{ $row->total }}</b></div>
            @empty
                <p class="muted">Belum ada data.</p>
            @endforelse
        </div>
    </div>
@endif

@if ($tab === 'murid')
    <p class="muted">Murid bergabung lewat tautan, QR, atau kode <b>{{ $classroom->join_code }}</b>.</p>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Nama</th><th>Email</th><th>Key sendiri</th><th>Bergabung</th><th></th></tr></thead>
                <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>{{ $student->name }}</td>
                        <td class="muted">{{ $student->email }}</td>
                        <td>{{ $student->gemini_key ? 'Ada' : '—' }}</td>
                        <td class="muted">{{ $student->pivot->created_at?->locale('id')->isoFormat('D MMM YYYY') }}</td>
                        <td style="text-align:right">
                            <form method="post" action="{{ route('guru.kelas.murid.remove', [$classroom, $student]) }}" onsubmit="return confirm('Keluarkan {{ $student->name }} dari kelas?')">
                                @csrf @method('delete') <button class="btn btn-danger btn-sm">Keluarkan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada murid. Bagikan kode kelas di atas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($tab === 'pengaturan')
    <form method="post" action="{{ route('guru.kelas.update', $classroom) }}" class="grid grid-2">
        @csrf @method('put')
        <div class="card stack">
            <h2>Kelas</h2>
            <div class="field">
                <label for="name">Nama kelas</label>
                <input id="name" type="text" name="name" value="{{ old('name', $classroom->name) }}" required>
            </div>
            <div class="field">
                <label for="description">Keterangan</label>
                <input id="description" type="text" name="description" value="{{ old('description', $classroom->description) }}">
            </div>
            <div class="field">
                <label for="daily_limit_per_student">Jatah pertanyaan per murid per hari <span class="hint">dari key Anda</span></label>
                <input id="daily_limit_per_student" type="number" min="1" max="500" name="daily_limit_per_student" value="{{ old('daily_limit_per_student', $classroom->daily_limit_per_student) }}" required>
                @error('daily_limit_per_student')<div class="error">{{ $message }}</div>@enderror
            </div>
            <label class="check"><input type="checkbox" name="allow_student_keys" value="1" @checked($classroom->allow_student_keys)> Izinkan murid memakai key Gemini sendiri</label>
        </div>
        <div class="card stack">
            <h2>Model AI</h2>
            @php $teacher = auth()->user(); @endphp
            <div class="field">
                <label for="gemini_model">Model Gemini</label>
                @if ($teacher->gemini_models)
                    <select id="gemini_model" name="gemini_model">
                        @unless (in_array($classroom->gemini_model, $teacher->gemini_models, true))
                            <option value="{{ $classroom->gemini_model }}" selected>{{ $classroom->gemini_model }} (tidak tersedia)</option>
                        @endunless
                        @foreach ($teacher->gemini_models as $m)
                            <option value="{{ $m }}" @selected(old('gemini_model', $classroom->gemini_model) === $m)>{{ $m }}{{ str_contains($m, 'flash-lite') ? ' · kuota gratis terbesar' : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="gemini_model" type="text" name="gemini_model" value="{{ old('gemini_model', $classroom->gemini_model) }}" required>
                    <div class="hint">Pilihan model muncul setelah key Gemini disimpan di <a href="{{ route('guru.keys.edit') }}">Pengaturan AI</a>.</div>
                @endif
                @error('gemini_model')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="rpm_limit">Batas permintaan per menit ke key Gemini Anda</label>
                <input id="rpm_limit" type="number" min="1" max="1000" name="rpm_limit" value="{{ old('rpm_limit', $classroom->rpm_limit) }}" required>
                <div class="hint">Samakan dengan batas RPM di halaman rate limit AI Studio. Lewat batas ini, pertanyaan pindah ke cadangan atau antre.</div>
            </div>
            <label class="check"><input type="checkbox" name="use_groq_fallback" value="1" @checked($classroom->use_groq_fallback)> Pakai Groq sebagai cadangan saat Gemini penuh</label>
            <div class="field">
                <label for="groq_model">Model Groq</label>
                @if ($teacher->groq_models)
                    <select id="groq_model" name="groq_model">
                        @unless (in_array($classroom->groq_model, $teacher->groq_models, true))
                            <option value="{{ $classroom->groq_model }}" selected>{{ $classroom->groq_model }} (tidak tersedia)</option>
                        @endunless
                        @foreach ($teacher->groq_models as $m)
                            <option value="{{ $m }}" @selected(old('groq_model', $classroom->groq_model) === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="groq_model" type="text" name="groq_model" value="{{ old('groq_model', $classroom->groq_model) }}" required>
                    <div class="hint">Pilihan model muncul setelah key Groq disimpan.</div>
                @endif
                @error('groq_model')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div><button class="btn">Simpan pengaturan</button></div>
    </form>
@endif
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-busy-form]').forEach((f) => f.addEventListener('submit', () => {
    const b = f.querySelector('button'); b.disabled = true; b.textContent = 'Menyusun…';
}));
document.querySelector('[data-copy-join]')?.addEventListener('click', async (e) => {
    const input = document.getElementById('join-url');
    try { await navigator.clipboard.writeText(input.value); e.target.textContent = 'Tersalin'; }
    catch { input.select(); e.target.textContent = 'Salin manual'; }
    setTimeout(() => e.target.textContent = 'Salin', 1800);
});
</script>
@endpush
