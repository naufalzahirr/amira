@extends('layouts.app')
@section('title', 'Pengaturan AI')
@section('page-class', 'page-narrow')
@section('content')
<h1>Pengaturan AI</h1>
<p class="muted">Key Anda dipakai semua kelas yang Anda ajar. Key dites sebelum disimpan, disimpan terenkripsi, dan murid tidak pernah melihatnya.</p>

@foreach ([
    'gemini' => ['Gemini', 'utama', $geminiMasked, $user->gemini_models, 'https://aistudio.google.com/apikey', 'aistudio.google.com/apikey', 'AIza...'],
    'groq' => ['Groq', 'cadangan', $groqMasked, $user->groq_models, 'https://console.groq.com/keys', 'console.groq.com/keys', 'gsk_...'],
] as $provider => [$title, $role, $masked, $models, $url, $urlLabel, $placeholder])
    <section class="card stack" style="margin-top:16px" data-provider="{{ $provider }}">
        <div class="row spread">
            <h2 style="margin:0">{{ $title }} <span class="badge">{{ $role }}</span></h2>
            <span class="status-pill {{ $masked ? 'ok' : '' }}">{{ $masked ? 'Terpasang' : 'Belum ada' }}</span>
        </div>
        <p class="small muted" style="margin:0">
            @if ($provider === 'gemini')
                Buat key gratis di <a href="{{ $url }}" target="_blank" rel="noopener">{{ $urlLabel }}</a>. Kuota gratis kecil dan dihitung per project; bila billing diaktifkan, kuota naik dan isi chat tidak dipakai Google untuk melatih model.
            @else
                Dipakai otomatis saat kuota Gemini penuh. Key Groq hanya tampil sekali saat dibuat di <a href="{{ $url }}" target="_blank" rel="noopener">{{ $urlLabel }}</a>; kalau lupa, buat key baru.
            @endif
        </p>
        @if ($masked)
            <div class="row small">
                <span>Key: <code>{{ $masked }}</code></span>
                <span class="muted">· {{ $models ? count($models).' model tersedia' : 'daftar model belum ada' }}</span>
                <button type="button" class="linkbtn" data-test>Tes ulang</button>
                <span data-result></span>
            </div>
        @endif
        <form method="post" action="{{ route('guru.keys.update') }}" class="stack">
            @csrf @method('put')
            <input type="hidden" name="provider" value="{{ $provider }}">
            <div class="field">
                <label for="{{ $provider }}_key">{{ $masked ? 'Ganti key' : 'Tempel key' }}</label>
                <input id="{{ $provider }}_key" type="password" name="key" autocomplete="off" placeholder="{{ $placeholder }}">
                @error("{$provider}_key")<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <button class="btn">Tes &amp; simpan</button>
                @if ($masked)
                    <button class="btn btn-danger" name="remove" value="1" onclick="return confirm('Hapus key {{ $title }}?')">Hapus key</button>
                @endif
            </div>
        </form>
    </section>
@endforeach
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-test]').forEach((btn) => btn.addEventListener('click', async () => {
    const box = btn.closest('[data-provider]');
    const out = box.querySelector('[data-result]');
    btn.disabled = true; out.textContent = 'Mengetes...'; out.style.color = '';
    try {
        const res = await fetch(@json(route('guru.keys.test')), {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
            body: JSON.stringify({provider: box.dataset.provider}),
        });
        const data = await res.json();
        out.style.color = data.ok ? 'var(--olive)' : 'var(--danger)';
        out.textContent = data.message || 'Gagal mengetes.';
    } catch { out.textContent = 'Gagal terhubung.'; out.style.color = 'var(--danger)'; }
    btn.disabled = false;
}));
</script>
@endpush
