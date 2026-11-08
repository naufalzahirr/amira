@extends('layouts.app')
@section('title', $mentor->name)
@section('full')
<div class="chat-shell">
    <aside class="chat-side" id="chat-side">
        <a href="{{ route('belajar.kelas', $classroom) }}" class="small">← {{ $classroom->name }}</a>
        <div class="row" style="gap:12px;margin-top:16px">
            <x-mentor-avatar :mentor="$mentor" />
            <div>
                <b style="font-family:var(--font-head)">{{ $mentor->name }}</b>
                <div class="small muted">{{ $mentor->tagline }}</div>
            </div>
        </div>
        <a href="{{ route('chat.show', $mentor) }}" class="btn btn-yellow btn-sm" style="width:100%;margin-top:16px">+ Percakapan baru</a>

        <h3>Riwayat</h3>
        @if ($conversations->isEmpty())
            <p class="small muted">Belum ada percakapan.</p>
        @else
            <ul class="conv-list">
                @foreach ($conversations as $c)
                    <li><a href="{{ route('chat.conversation', [$mentor, $c]) }}" class="{{ $conversation?->id === $c->id ? 'on' : '' }}" title="{{ $c->title }}">{{ $c->title }}</a></li>
                @endforeach
            </ul>
        @endif

        @if ($conversation)
            <form method="post" action="{{ route('chat.destroy', $conversation) }}" style="margin-top:14px" onsubmit="return confirm('Hapus percakapan ini?')">
                @csrf @method('delete')
                <button class="linkbtn small" style="color:var(--danger)">Hapus percakapan ini</button>
            </form>
        @endif

        <h3>Etika &amp; privasi</h3>
        <p class="small muted">Coba dulu, baru minta petunjuk. Kalau memakai kode atau teks dari AMIRA, tandai dengan tombol <b>Salin sitasi</b> dan pastikan kamu bisa menjelaskannya. Jangan tulis data pribadi. <a href="{{ route('privasi') }}" target="_blank">Baca privasi</a></p>
    </aside>

    <section class="chat-main">
        <header class="chat-head">
            <button type="button" class="btn btn-ghost btn-sm only-mobile" data-toggle-side aria-controls="chat-side">Menu</button>
            <x-mentor-avatar :mentor="$mentor" />
            <div style="min-width:0">
                <h2>{{ $mentor->name }}</h2>
                <p>{{ $mentor->hint_ladder ? 'Memberi petunjuk bertahap' : 'Mentor dari gurumu' }}</p>
            </div>
        </header>

        <div class="chat-log" id="chat-log">
            <div class="chat-log-inner" id="chat-items">
                @php $needsAck = ! auth()->user()->isTeacher() && ! auth()->user()->privacy_ack_at; @endphp
                @if ($needsAck)
                    <div class="card privacy-card" id="privacy-card">
                        <div class="row" style="gap:12px;flex-wrap:nowrap">
                            <img src="{{ asset('img/amira-mascot.png') }}" alt="" style="width:64px">
                            <div>
                                <h2 style="margin:0">Sebelum mulai, baca ini dulu</h2>
                                <p class="small muted" style="margin:2px 0 0">Cukup sekali. Bisa dibaca lagi kapan saja di halaman <a href="{{ route('privasi') }}" target="_blank">Privasi</a>.</p>
                            </div>
                        </div>
                        @include('partials.privacy-points')
                        <button type="button" class="btn btn-yellow" id="privacy-ok" data-url="{{ route('privasi.setuju') }}">Saya mengerti, mulai belajar</button>
                    </div>
                @endif
                @if ($messages->isEmpty())
                    <div class="chat-welcome {{ $needsAck ? 'hidden' : '' }}" id="chat-welcome">
                        <x-mentor-avatar :mentor="$mentor" size="xl" />
                        <h2>Halo! Aku {{ $mentor->name }}.</h2>
                        @if ($mentor->goal)
                            <p class="muted" style="max-width:34em;margin:0 auto"><b>Tujuan belajar:</b> {{ \Illuminate\Support\Str::limit($mentor->goal, 220) }}</p>
                        @endif
                        <p class="muted" style="max-width:34em;margin:8px auto 0">Ceritakan apa yang sedang kamu kerjakan dan bagian mana yang membuatmu buntu. Screenshot error juga boleh ditempel.</p>
                        <div class="starters">
                            @foreach (array_slice($presets, 0, 3) as $preset)
                                <button type="button" class="starter" data-preset="{{ $preset }}">{{ $preset }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif
                @foreach ($messages as $message)
                    @include('chat._message', ['message' => $message])
                @endforeach
            </div>
        </div>

        <footer class="chat-foot">
            <div class="chat-foot-inner">
                <div class="wait-banner hidden" id="wait-banner" role="status"></div>
                @if ($mentor->hint_ladder)
                    <div class="hint-bar" id="hint-bar" data-level="{{ $hintLevel }}">
                        <div class="hint-steps" aria-hidden="true">
                            @foreach (config('amira.hint_levels') as $n => $info)
                                <span class="hint-step" data-step="{{ $n }}">{{ $n }}</span>
                            @endforeach
                        </div>
                        <div class="hint-text">
                            <b id="hint-label">Petunjuk {{ $hintLevel }} · {{ config("amira.hint_levels.{$hintLevel}.label") }}</b>
                            <span class="small muted">Tingkat bantuan mentor saat ini</span>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm" id="next-hint" @disabled(! $conversation || $hintLevel >= 3)>
                            {{ $hintLevel >= 3 ? 'Petunjuk terakhir' : 'Petunjuk berikutnya' }}
                        </button>
                    </div>
                @endif
                <div class="presets" aria-label="Prompt Preset">
                    @foreach ($presets as $preset)
                        <button type="button" class="preset" data-preset="{{ $preset }}">{{ $preset }}</button>
                    @endforeach
                </div>
                <div class="attach-preview hidden" id="attach-preview">
                    <img alt="Pratinjau gambar">
                    <span class="small" id="attach-name"></span>
                    <button type="button" class="linkbtn small" id="attach-remove">Hapus</button>
                </div>
                <form class="composer" id="composer">
                    <label class="attach-btn" title="Lampirkan gambar atau screenshot (bisa juga tempel dengan Ctrl/Cmd+V)">
                        <input type="file" id="attach-input" accept="image/png,image/jpeg,image/webp" hidden>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.8"/><path d="m21 15-5-5L5 21"/></svg>
                        <span class="sr-only">Lampirkan gambar</span>
                    </label>
                    <textarea id="composer-input" rows="1" placeholder="Tulis pertanyaan atau usahamu..." aria-label="Pesan" maxlength="6000"></textarea>
                    <button class="btn" id="send-btn" type="submit">Kirim</button>
                </form>
                <div class="quota">
                    <span>Enter untuk kirim · Shift+Enter baris baru · tempel screenshot dengan Ctrl/Cmd+V</span>
                    @if (! is_null($used))
                        <span id="quota-text">Jatah hari ini: <b id="quota-used">{{ $used }}</b> / {{ $classroom->daily_limit_per_student }}</span>
                    @else
                        <span>Mode coba guru, tanpa batas jatah</span>
                    @endif
                </div>
            </div>
        </footer>
    </section>
</div>
<script id="chat-config" type="application/json">{!! json_encode([
    'sendUrl' => route('chat.send', $mentor),
    'conversationId' => $conversation?->id,
    'mentorName' => $mentor->name,
    'avatarHtml' => $avatarHtml,
    'csrf' => csrf_token(),
    'hintLevel' => $mentor->hint_ladder ? $hintLevel : null,
    'hintLabels' => collect(config('amira.hint_levels'))->map(fn ($l) => $l['label']),
    'isTeacher' => auth()->user()->isTeacher(),
    'needsAck' => ! auth()->user()->isTeacher() && ! auth()->user()->privacy_ack_at,
], JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endsection
@push('scripts')
<script src="{{ asset('js/chat.js') }}?v={{ filemtime(public_path('js/chat.js')) }}"></script>
@endpush
