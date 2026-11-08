<?php

namespace App\Services\Ai;

use App\Models\Classroom;
use App\Models\Conversation;
use App\Models\Mentor;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyiapkan pesan peserta untuk mentor dan membuka jawaban streaming, dengan urutan key:
 *   1. key Gemini milik murid (bila dipasang dan diizinkan guru) — menghemat kuota guru
 *   2. key Gemini milik guru  (selama jatah harian murid belum habis)
 *   3. key Groq milik guru     (cadangan, selama jatah harian belum habis)
 * Setiap key dibatasi per menit supaya kuota gratis tidak langsung jebol saat kelas ramai.
 */
class MentorChat
{
    public const NEXT_HINT_TEXT = 'Aku masih buntu. Boleh minta petunjuk berikutnya?';

    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly GroqClient $groq,
    ) {}

    public function start(User $user, Mentor $mentor, ?Conversation $conversation, string $text,
        ?UploadedFile $image = null, bool $nextHint = false): ChatResult
    {
        $classroom = $mentor->classroom;
        $attempts = $this->candidates($user, $classroom);

        if ($attempts === []) {
            return ChatResult::failed($this->noCandidateReason($user, $classroom));
        }

        $text = trim($text) !== '' ? trim($text) : ($nextHint ? self::NEXT_HINT_TEXT : 'Tolong lihat gambar ini.');
        $level = $mentor->hint_ladder ? $this->levelFor($conversation, $nextHint) : null;

        $stored = $image ? ImageStore::put($image) : null;

        $history = $conversation ? $this->history($conversation) : [];
        $current = ['role' => 'user', 'content' => $text];
        if ($stored) {
            $current['image'] = ['mime' => $stored['mime'], 'data' => base64_encode(Storage::get($stored['path']))];
        }
        $history[] = $current;

        $system = $this->systemPrompt($mentor, $level, $nextHint);
        $waits = [];
        $errors = [];

        foreach ($attempts as $attempt) {
            $limiterKey = 'amira-rpm:'.sha1($attempt['provider'].$attempt['key']);

            if (RateLimiter::tooManyAttempts($limiterKey, $attempt['rpm'])) {
                $waits[] = RateLimiter::availableIn($limiterKey);
                continue;
            }

            RateLimiter::hit($limiterKey, 60);

            try {
                $client = $attempt['provider'] === 'gemini' ? $this->gemini : $this->groq;
                $handle = $client->stream($attempt['key'], $attempt['model'], $system, $history);
            } catch (ProviderException $e) {
                if ($e->rateLimited) {
                    $waits[] = $e->retryAfter ?? 20;
                }
                $errors[] = $e->getMessage();
                continue;
            }

            return ChatResult::streaming(new ChatStream(
                handle: $handle,
                attempt: $attempt,
                save: fn (string $raw, StreamHandle $h) => $this->store(
                    $user, $mentor, $conversation, $text, $stored, $level, $raw, $attempt, $h,
                ),
                discard: fn () => $stored ? Storage::delete($stored['path']) : null,
            ));
        }

        if ($stored) {
            Storage::delete($stored['path']);
        }

        if ($waits !== []) {
            return ChatResult::busy(max(5, min(60, min($waits))));
        }

        return ChatResult::failed(implode(' ', array_unique($errors)));
    }

    /** Berapa jawaban dari key guru yang sudah dipakai murid ini hari ini di kelas ini. */
    public function usedToday(User $user, Classroom $classroom): int
    {
        return Message::query()
            ->where('role', 'model')
            ->where('key_source', 'guru')
            ->where('messages.created_at', '>=', now()->startOfDay())
            ->whereHas('conversation', fn ($q) => $q
                ->where('user_id', $user->id)
                ->whereHas('mentor', fn ($q) => $q->where('classroom_id', $classroom->id)))
            ->count();
    }

    public function systemPrompt(Mentor $mentor, ?int $level = null, bool $nextHint = false): string
    {
        $parts = [config('amira.base_prompt')];

        $parts[] = "Nama mentormu di kelas ini: {$mentor->name}."
            .($mentor->tagline ? " Peranmu: {$mentor->tagline}." : '');

        if ($mentor->goal) {
            $parts[] = "Tujuan belajar yang ditetapkan guru:\n{$mentor->goal}";
        }

        if ($mentor->instructions) {
            $parts[] = "Instruksi khusus dari guru (utamakan ini):\n{$mentor->instructions}";
        }

        if ($level) {
            $info = config("amira.hint_levels.{$level}");
            $parts[] = strtr(config('amira.hint_ladder_prompt'), [
                '{level}' => $level, '{label}' => $info['label'], '{instruction}' => $info['prompt'],
            ]);
            if ($nextHint) {
                $parts[] = str_replace('{level}', (string) $level, config('amira.next_hint_prompt'));
            }
        }

        if ($mentor->explain_back) {
            $parts[] = config('amira.explain_back_prompt');
        }

        $materials = $this->materialsText($mentor);
        if ($materials !== '') {
            $parts[] = "Materi dari guru. Jadikan rujukan utama; bila jawabannya tidak ada di materi, katakan begitu:\n\n{$materials}";
        }

        $parts[] = config('amira.topic_prompt');

        return implode("\n\n", $parts);
    }

    /**
     * Pisahkan label tersembunyi dari jawaban model.
     *
     * @return array{text: string, topic: ?string, solved: bool, understood: ?bool}
     */
    public static function extractTags(string $raw): array
    {
        $topic = null;
        $solved = false;
        $understood = null;

        $pattern = '/\[\[\s*(?:topik\s*:\s*(?<topic>[^\]]+?)|(?<solved>selesai)|paham\s*:\s*(?<paham>ya|belum))\s*\]\]/iu';

        $text = preg_replace_callback($pattern, function ($m) use (&$topic, &$solved, &$understood) {
            if (($m['topic'] ?? '') !== '') {
                $topic = mb_strtolower(Str::limit(trim($m['topic']), 60, ''));
            } elseif (($m['solved'] ?? '') !== '') {
                $solved = true;
            } elseif (($m['paham'] ?? '') !== '') {
                $understood = mb_strtolower($m['paham']) === 'ya';
            }

            return '';
        }, $raw);

        return ['text' => trim($text), 'topic' => $topic, 'solved' => $solved, 'understood' => $understood];
    }

    /** Teks yang aman ditampilkan selagi jawaban masih mengalir: label lengkap dibuang, label setengah jadi ditahan. */
    public static function visiblePart(string $raw): string
    {
        $text = self::extractTags($raw)['text'];
        $open = strrpos($text, '[[');

        if ($open !== false && ! str_contains(substr($text, $open), ']]')) {
            $text = substr($text, 0, $open);
        } elseif (str_ends_with($text, '[')) {
            $text = substr($text, 0, -1);
        }

        return rtrim($text);
    }

    private function levelFor(?Conversation $conversation, bool $nextHint): int
    {
        $current = $conversation?->hint_level ?: 1;

        return $nextHint ? min(3, $current + 1) : $current;
    }

    /** @return array<int, array{provider: string, key: string, model: string, rpm: int, source: string}> */
    private function candidates(User $user, Classroom $classroom): array
    {
        $teacher = $classroom->teacher;
        $isTeacher = $user->id === $teacher->id;
        $withinQuota = $isTeacher || $this->usedToday($user, $classroom) < $classroom->daily_limit_per_student;

        $list = [];

        if (! $isTeacher && $classroom->allow_student_keys && $user->gemini_key) {
            $list[] = ['provider' => 'gemini', 'key' => $user->gemini_key, 'model' => $classroom->gemini_model,
                'rpm' => config('amira.student_rpm'), 'source' => 'murid'];
        }

        if ($withinQuota && $teacher->gemini_key) {
            $list[] = ['provider' => 'gemini', 'key' => $teacher->gemini_key, 'model' => $classroom->gemini_model,
                'rpm' => $classroom->rpm_limit, 'source' => 'guru'];
        }

        if ($withinQuota && $classroom->use_groq_fallback && $teacher->groq_key) {
            $list[] = ['provider' => 'groq', 'key' => $teacher->groq_key, 'model' => $classroom->groq_model,
                'rpm' => config('amira.groq_rpm'), 'source' => 'guru'];
        }

        return $list;
    }

    private function noCandidateReason(User $user, Classroom $classroom): string
    {
        $teacher = $classroom->teacher;

        if (! $teacher->gemini_key && ! $teacher->groq_key) {
            return 'Guru belum memasang API key untuk kelas ini.';
        }

        $limit = $classroom->daily_limit_per_student;

        return "Jatah {$limit} pertanyaan hari ini sudah habis. Jatah terisi lagi besok"
            .($classroom->allow_student_keys ? ', atau pasang key Gemini milikmu sendiri di halaman Akun.' : '.');
    }

    private function history(Conversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->take(config('amira.history_messages'))
            ->get(['role', 'content', 'image_path'])
            ->reverse()
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->image_path ? "[Peserta melampirkan gambar]\n{$m->content}" : $m->content,
            ])
            ->values()
            ->all();
    }

    private function materialsText(Mentor $mentor): string
    {
        $budget = config('amira.material_char_budget');
        $chunks = [];

        foreach ($mentor->materials()->orderBy('id')->get() as $material) {
            if ($budget <= 0 || ! $material->extracted_text) {
                continue;
            }

            $text = Str::limit($material->extracted_text, $budget, ' [...dipotong]');
            $budget -= mb_strlen($text);
            $chunks[] = "### {$material->title}\n{$text}";
        }

        return implode("\n\n", $chunks);
    }

    private function store(User $user, Mentor $mentor, ?Conversation $conversation, string $text, ?array $image,
        ?int $level, string $raw, array $attempt, StreamHandle $handle): Message
    {
        $tags = self::extractTags($raw);

        $conversation ??= $user->conversations()->create([
            'mentor_id' => $mentor->id,
            'title' => Str::limit(preg_replace('/\s+/', ' ', $text), 60),
            'hint_level' => 1,
        ]);

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $text,
            'topic' => $tags['topic'],
            'image_path' => $image['path'] ?? null,
            'image_mime' => $image['mime'] ?? null,
        ]);

        $reply = $conversation->messages()->create([
            'role' => 'model',
            'content' => $tags['text'] !== '' ? $tags['text'] : '…',
            'topic' => $tags['topic'],
            'hint_level' => $level,
            'solved' => $tags['solved'],
            'understood' => $mentor->explain_back ? $tags['understood'] : null,
            'provider' => $attempt['provider'],
            'model' => $handle->model,
            'key_source' => $attempt['source'],
            'prompt_tokens' => $handle->promptTokens,
            'output_tokens' => $handle->outputTokens,
        ]);

        // Masalah selesai: tangga kembali ke awal untuk masalah berikutnya.
        if ($level) {
            $conversation->hint_level = $tags['solved'] ? 1 : $level;
        }
        $conversation->save();
        $conversation->touch();

        $reply->setRelation('conversation', $conversation);

        return $reply;
    }
}
