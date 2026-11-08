@if ($message->role === 'user')
    <div class="msg msg-user">
        <div class="bubble">
            @if ($message->image_path)
                <a href="{{ route('chat.image', $message) }}" target="_blank" rel="noopener" class="bubble-img"><img src="{{ route('chat.image', $message) }}" alt="Gambar lampiran" loading="lazy"></a>
            @endif
            <div class="bubble-text">{{ $message->content }}</div>
        </div>
    </div>
@else
    <div class="msg msg-model" data-topic="{{ $message->topic }}">
        <x-mentor-avatar :mentor="$mentor" />
        <div style="min-width:0">
            @if ($message->hint_level || $message->solved)
                <div class="msg-meta">
                    @if ($message->hint_level)<span class="hint-tag hint-{{ $message->hint_level }}">Petunjuk {{ $message->hint_level }} · {{ config("amira.hint_levels.{$message->hint_level}.label") }}</span>@endif
                    @if ($message->solved)<span class="solved-tag">Masalah selesai</span>@endif
                </div>
            @endif
            <div class="bubble">{!! $message->html() !!}</div>
            <div class="msg-tools">
                <button type="button" class="tool" data-copy-answer>Salin</button>
                <button type="button" class="tool" data-copy-cite>Salin sitasi</button>
                <button type="button" class="tool" data-explain-back>Jelaskan balik</button>
                @if (auth()->user()->isTeacher() && $message->provider)
                    <span class="via">via {{ $message->provider === 'groq' ? 'cadangan Groq' : ($message->key_source === 'murid' ? 'key murid' : 'Gemini') }}</span>
                @endif
            </div>
            <template data-raw>{{ $message->content }}</template>
        </div>
    </div>
@endif
