@extends('layouts.app')
@section('title', $mentor->exists ? 'Atur '.$mentor->name : 'Mentor baru')
@section('content')
<a href="{{ route('guru.kelas.show', $classroom) }}" class="small">← {{ $classroom->name }}</a>
<div class="row spread" style="margin-top:8px">
    <h1>{{ $mentor->exists ? $mentor->name : 'Mentor baru' }}</h1>
    @if ($mentor->exists)
        <a class="btn btn-ghost" href="{{ route('chat.show', $mentor) }}">Coba sebagai murid</a>
    @endif
</div>

@unless ($mentor->exists)
    <section style="margin:8px 0 22px">
        <h2 style="font-size:1.05rem">Mulai dari template</h2>
        <div class="template-grid">
            @foreach (config('amira_mentor.templates') as $key => $t)
                @php $preview = new \App\Models\Mentor(['name' => $t['name'], 'icon' => $t['icon'], 'color' => $t['color']]); @endphp
                <a href="{{ route('guru.mentor.create', [$classroom, 'template' => $key]) }}" class="template-card {{ $templateKey === $key ? 'on' : '' }}">
                    <x-mentor-avatar :mentor="$preview" />
                    <div><b>{{ $t['title'] }}</b><div class="small muted">{{ $t['summary'] }}</div></div>
                </a>
            @endforeach
            <a href="{{ route('guru.mentor.create', $classroom) }}" class="template-card {{ $templateKey ? '' : 'on' }}">
                <div class="avatar c-olive" aria-hidden="true">+</div>
                <div><b>Mulai kosong</b><div class="small muted">Isi sendiri dari awal dengan aturan dasar AMIRA.</div></div>
            </a>
        </div>
        @if ($templateKey)<p class="small muted" style="margin-top:8px">Isi di bawah sudah terisi dari template. Sesuaikan dengan kelas Anda sebelum menyimpan.</p>@endif
    </section>
@endunless

<div class="grid" style="grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:20px;align-items:start" id="mentor-grid">
    <form method="post" action="{{ $mentor->exists ? route('guru.mentor.update', $mentor) : route('guru.mentor.store', $classroom) }}" class="card stack">
        @csrf
        @if ($mentor->exists) @method('put') @endif

        <div class="field">
            <label for="name">Nama mentor</label>
            <input id="name" type="text" name="name" value="{{ old('name', $mentor->name) }}" placeholder="Mentor HTML & CSS" required maxlength="80">
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="tagline">Deskripsi singkat <span class="hint">tampil di kartu mentor</span></label>
            <input id="tagline" type="text" name="tagline" value="{{ old('tagline', $mentor->tagline) }}" placeholder="Menemani membangun landing page pertamamu" maxlength="150">
        </div>
        <div class="field">
            <label>Warna</label>
            <div class="swatches">
                @foreach (\App\Models\Mentor::COLORS as $color)
                    <label><input type="radio" name="color" value="{{ $color }}" @checked(old('color', $mentor->color) === $color)><span class="c-{{ $color }}" title="{{ $color }}"></span></label>
                @endforeach
            </div>
        </div>
        <div class="field">
            <label>Ikon</label>
            <div class="icon-pick">
                @foreach (config('amira_mentor.icons') as $key => [$label, $svg])
                    <label title="{{ $label }}"><input type="radio" name="icon" value="{{ $key }}" @checked(old('icon', $mentor->icon) === $key)><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $svg !!}</svg><small>{{ $label }}</small></span></label>
                @endforeach
            </div>
        </div>
        <div class="field">
            <label for="goal">Tujuan belajar <span class="hint">CP/TP atau target tugas</span></label>
            <textarea id="goal" name="goal" rows="4" placeholder="Peserta mampu menyusun struktur HTML semantik dan menautkan file CSS eksternal untuk landing page sederhana.">{{ old('goal', $mentor->goal) }}</textarea>
        </div>
        <div class="field">
            <label for="instructions">Instruksi untuk mentor <span class="hint">gaya, batasan, hal yang harus/tidak boleh</span></label>
            <textarea id="instructions" name="instructions" rows="7" placeholder="Pakai contoh toko online sederhana. Jangan berikan kode utuh satu halaman; maksimal 10 baris contoh. Kalau peserta bertanya soal JavaScript, arahkan kembali ke HTML dan CSS dulu.">{{ old('instructions', $mentor->instructions) }}</textarea>
            <div class="hint">Aturan dasar AMIRA (mencoba dulu, tidak mengerjakan ujian, sitasi) selalu berlaku.</div>
        </div>
        <div class="field">
            <label for="presets">Prompt Preset <span class="hint">satu per baris, maksimal 8 tombol</span></label>
            <textarea id="presets" name="presets" rows="5">{{ old('presets', implode("\n", $mentor->presets ?? [])) }}</textarea>
        </div>
        <label class="check"><input type="checkbox" name="hint_ladder" value="1" @checked(old('hint_ladder', $mentor->hint_ladder))> Tangga petunjuk: petunjuk 1 → 2 → contoh mirip</label>
        <label class="check"><input type="checkbox" name="explain_back" value="1" @checked(old('explain_back', $mentor->explain_back))> Minta peserta menjelaskan balik setelah masalah selesai</label>
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $mentor->is_active))> Tampilkan ke murid</label>
        <div class="row">
            <button class="btn">{{ $mentor->exists ? 'Simpan' : 'Buat mentor' }}</button>
        </div>
    </form>

    <div class="stack">
        <div class="card">
            <h2>Materi rujukan</h2>
            @if (! $mentor->exists)
                <p class="muted">Simpan mentor dulu, lalu unggah materi PDF atau teks di sini.</p>
            @else
                <p class="small muted">Teks materi dibaca mentor sebagai rujukan utama. Maksimal sekitar {{ number_format(config('amira.material_char_budget'), 0, ',', '.') }} karakter per pesan; sisanya dipotong.</p>
                @error('file')<div class="flash flash-error">{{ $message }}</div>@enderror
                @php $total = 0; @endphp
                @foreach ($mentor->materials as $material)
                    @php $total += $material->char_count; @endphp
                    <div class="row spread" style="padding:10px 0;border-top:1px solid var(--line)">
                        <div style="min-width:0">
                            <b>{{ $material->title }}</b>
                            <div class="small muted">{{ $material->original_name }} · {{ number_format($material->char_count, 0, ',', '.') }} karakter</div>
                        </div>
                        <form method="post" action="{{ route('guru.mentor.materi.destroy', $material) }}" onsubmit="return confirm('Hapus materi ini?')">
                            @csrf @method('delete') <button class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </div>
                @endforeach
                @if ($total > config('amira.material_char_budget'))
                    <div class="note small" style="margin-top:10px">Total materi melebihi batas, jadi bagian akhir tidak terbaca mentor. Pertimbangkan memecah materi ke mentor lain.</div>
                @endif
                <form method="post" action="{{ route('guru.mentor.materi.store', $mentor) }}" enctype="multipart/form-data" class="stack" style="margin-top:14px">
                    @csrf
                    <div class="field">
                        <label for="title">Judul materi <span class="hint">opsional</span></label>
                        <input id="title" type="text" name="title" placeholder="Modul 2: Struktur HTML">
                    </div>
                    <div class="field">
                        <label for="file">File (PDF, TXT, MD · maks 20 MB)</label>
                        <input id="file" type="file" name="file" accept=".pdf,.txt,.md" required>
                    </div>
                    <button class="btn btn-ghost">Unggah materi</button>
                </form>
            @endif
        </div>

        @if ($mentor->exists)
            <div class="card">
                <h2>Hapus mentor</h2>
                <p class="small muted">Percakapan murid dengan mentor ini ikut terhapus.</p>
                <form method="post" action="{{ route('guru.mentor.destroy', $mentor) }}" onsubmit="return confirm('Hapus mentor {{ $mentor->name }} beserta percakapannya?')">
                    @csrf @method('delete') <button class="btn btn-danger">Hapus mentor</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
@push('head')
<style>@media (max-width: 860px) { #mentor-grid { grid-template-columns: 1fr !important; } }</style>
@endpush
