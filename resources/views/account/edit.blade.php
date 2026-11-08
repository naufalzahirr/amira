@extends('layouts.app')
@section('title', 'Akun')
@section('page-class', 'page-narrow')
@section('content')
<h1>Akun</h1>
<form method="post" action="{{ route('akun.update') }}" class="stack">
    @csrf @method('put')
    <div class="card">
        <div class="field">
            <label for="name">Nama</label>
            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="field">
            <label>Email</label>
            <input type="email" value="{{ $user->email }}" disabled>
        </div>
    </div>

    @if ($user->isTeacher())
        <div class="card">
            <h2>API key</h2>
            <p class="muted" style="margin:0">Key Gemini dan Groq untuk kelas Anda diatur di <a href="{{ route('guru.keys.edit') }}">Pengaturan AI</a>.</p>
        </div>
    @else
    <div class="card">
        <h2>Key Gemini milikmu <span class="badge">opsional</span></h2>
        <p class="muted">Tanpa key pun kamu bisa belajar memakai jatah dari guru. Kalau kamu memasang key sendiri, AMIRA memakai key-mu lebih dulu, jadi jatah dari guru tersisa untuk teman-temanmu, dan kamu tetap bisa bertanya saat jatah harian habis.</p>
        <ol class="small" style="padding-left:1.2em;color:var(--ink-2)">
            <li>Buka <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">aistudio.google.com/apikey</a> dan masuk dengan akun Google-mu.</li>
            <li>Klik <b>Create API key</b>, lalu salin key-nya.</li>
            <li>Tempel di bawah, klik <b>Tes key</b>, lalu <b>Simpan</b>.</li>
        </ol>
        @if ($maskedKey)
            <p>Key terpasang: <code>{{ $maskedKey }}</code></p>
        @endif
        <div class="field">
            <label for="gemini_key">{{ $maskedKey ? 'Ganti key' : 'Tempel key' }}</label>
            <input id="gemini_key" type="password" name="gemini_key" autocomplete="off" placeholder="AIza...">
        </div>
        <div class="row" style="margin-top:10px">
            <button type="button" class="btn btn-ghost btn-sm" data-test-key>Tes key</button>
            <span class="small" data-test-result></span>
        </div>
        @if ($maskedKey)
            <label class="check" style="margin-top:14px"><input type="checkbox" name="remove_key" value="1"> Hapus key-ku dari AMIRA</label>
        @endif
        <p class="small muted" style="margin-top:12px">Key disimpan terenkripsi dan tidak pernah ditampilkan utuh. Pada kuota gratis Google, isi percakapan bisa dibaca peninjau Google, jadi jangan menulis data pribadi.</p>
    </div>
    @endif
    <button class="btn">Simpan</button>
</form>

<form method="post" action="{{ route('akun.password') }}" class="card stack" style="margin-top:24px">
    @csrf @method('put')
    <h2>Ganti kata sandi</h2>
    <div class="field">
        <label for="current_password">Kata sandi lama</label>
        <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
        @error('current_password')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="password">Kata sandi baru <span class="hint">minimal 8 karakter</span></label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        @error('password')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="password_confirmation">Ulangi kata sandi baru</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
    </div>
    <div><button class="btn btn-ghost">Ganti kata sandi</button></div>
</form>
<p class="small muted" style="margin-top:16px"><a href="{{ route('privasi') }}">Cara AMIRA memakai data belajarmu</a></p>
@endsection
@push('scripts')
<script>
document.querySelector('[data-test-key]')?.addEventListener('click', async (e) => {
    const out = document.querySelector('[data-test-result]');
    e.target.disabled = true; out.textContent = 'Mengetes...'; out.style.color = '';
    try {
        const res = await fetch(@json(route('akun.test')), {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
            body: JSON.stringify({key: document.getElementById('gemini_key').value}),
        });
        const data = await res.json();
        out.textContent = data.message || 'Gagal mengetes.';
        out.style.color = data.ok ? 'var(--olive)' : 'var(--danger)';
    } catch { out.textContent = 'Gagal terhubung.'; out.style.color = 'var(--danger)'; }
    e.target.disabled = false;
});
</script>
@endpush
