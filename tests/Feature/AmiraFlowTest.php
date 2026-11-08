<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Conversation;
use App\Models\Mentor;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\MentorChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AmiraFlowTest extends TestCase
{
    use RefreshDatabase;

    private const GEMINI = 'generativelanguage.googleapis.com/*';
    private const GROQ = 'api.groq.com/*';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function teacher(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'guru', 'adult_confirmed_at' => now(), 'gemini_key' => 'AIza-guru-key-0001',
        ], $attrs));
    }

    private function student(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'murid', 'adult_confirmed_at' => now(), 'privacy_ack_at' => now()], $attrs));
    }

    private function setupClass(array $classAttrs = [], array $teacherAttrs = [], array $mentorAttrs = []): array
    {
        $teacher = $this->teacher($teacherAttrs);
        $classroom = $teacher->taughtClassrooms()->create(array_merge(['name' => 'Web Dasar'], $classAttrs));
        $mentor = $classroom->mentors()->create(array_merge([
            'name' => 'Mentor HTML', 'goal' => 'Menautkan CSS eksternal.', 'instructions' => 'Pakai contoh toko roti.',
            'hint_ladder' => true, 'explain_back' => true, 'presets' => ['Kodeku error:'], 'color' => 'olive', 'is_active' => true,
        ], $mentorAttrs));
        $student = $this->student();
        $classroom->students()->attach($student->id);

        return [$teacher, $classroom, $mentor, $student];
    }

    private function geminiOk(string $text = "Coba cek atribut href-mu.\n[[topik: linking file CSS]]"): string
    {
        $half = intdiv(mb_strlen($text), 2);
        $chunk = fn (string $t, bool $last) => 'data: '.json_encode(array_filter([
            'candidates' => [['content' => ['parts' => [['text' => $t]], 'role' => 'model']]],
            'usageMetadata' => $last ? ['promptTokenCount' => 120, 'candidatesTokenCount' => 30] : null,
            'modelVersion' => 'gemini-flash-lite-latest',
        ]))."\r\n\r\n";

        return $chunk(mb_substr($text, 0, $half), false).$chunk(mb_substr($text, $half), true);
    }

    private function groqOk(string $text = "Dari Groq.\n[[topik: selector CSS]]"): string
    {
        $events = '';
        foreach (mb_str_split($text, 8) as $piece) {
            $events .= 'data: '.json_encode(['model' => 'openai/gpt-oss-120b', 'choices' => [['delta' => ['content' => $piece]]]])."\n\n";
        }
        $events .= 'data: '.json_encode(['choices' => [['delta' => [], 'finish_reason' => 'stop']], 'x_groq' => ['usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20]]])."\n\n";

        return $events."data: [DONE]\n\n";
    }

    private function geminiQuotaError(): array
    {
        return [['error' => ['code' => 429, 'message' => 'Resource exhausted', 'details' => [['retryDelay' => '17s']]]]];
    }

    /** Kirim pesan dan kembalikan event "done" dari stream (atau respons JSON bila bukan stream). */
    private function ask(User $user, Mentor $mentor, array $data): array
    {
        $res = $this->actingAs($user)->post(route('chat.send', $mentor), $data, ['Accept' => 'text/event-stream, application/json']);

        if (! str_contains((string) $res->headers->get('Content-Type'), 'text/event-stream')) {
            return ['status' => $res->status()] + (json_decode((string) $res->getContent(), true) ?: []);
        }

        $events = collect(explode("\n\n", $res->streamedContent()))
            ->filter(fn ($chunk) => str_starts_with($chunk, 'data: '))
            ->map(fn ($chunk) => json_decode(substr($chunk, 6), true))
            ->values();

        return ['status' => 200, 'deltas' => $events->where('type', 'delta')->values()->all()]
            + ($events->firstWhere('type', 'done') ?? $events->firstWhere('type', 'error') ?? []);
    }

    private function systemOf(Request $req): string
    {
        return $req['systemInstruction']['parts'][0]['text'];
    }

    /* ---------- Akun & kelas ---------- */

    public function test_register_requires_age_confirmation(): void
    {
        $this->post('/daftar', [
            'name' => 'Rina', 'email' => 'rina@example.com', 'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123', 'role' => 'murid',
        ])->assertSessionHasErrors('adult');

        $this->post('/daftar', [
            'name' => 'Rina', 'email' => 'rina@example.com', 'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123', 'role' => 'murid', 'adult' => '1',
        ])->assertRedirect('/beranda');

        $this->assertNotNull(User::where('email', 'rina@example.com')->value('adult_confirmed_at'));
    }

    public function test_student_joins_class_with_code(): void
    {
        $teacher = $this->teacher();
        $classroom = $teacher->taughtClassrooms()->create(['name' => 'Kelas A']);
        $student = $this->student();

        $this->actingAs($student)->post('/belajar/gabung', ['code' => strtolower($classroom->join_code)])
            ->assertRedirect(route('belajar.kelas', $classroom));

        $this->assertTrue($classroom->students()->whereKey($student->id)->exists());
        $this->actingAs($student)->get(route('belajar.kelas', $classroom))->assertOk()->assertSee('Kelas A');
    }

    public function test_join_link_for_guest_registers_then_joins(): void
    {
        $classroom = $this->teacher()->taughtClassrooms()->create(['name' => 'Kelas Tautan']);

        $this->get(route('gabung', strtolower($classroom->join_code)))->assertOk()->assertSee('Kelas Tautan');

        $this->post('/daftar', [
            'name' => 'Budi', 'email' => 'budi@example.com', 'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123', 'role' => 'murid', 'adult' => '1',
        ])->assertRedirect(route('gabung', strtolower($classroom->join_code)));

        $this->get(route('gabung', $classroom->join_code))->assertRedirect(route('belajar.kelas', $classroom));
        $this->assertSame(1, $classroom->students()->count());
    }

    public function test_join_link_for_logged_in_student_and_unknown_code(): void
    {
        [, $classroom, , ] = $this->setupClass();
        $newcomer = $this->student();

        $this->actingAs($newcomer)->get(route('gabung', $classroom->join_code))->assertRedirect(route('belajar.kelas', $classroom));
        $this->assertTrue($classroom->students()->whereKey($newcomer->id)->exists());
        $this->get(route('gabung', 'ZZZZZZ'))->assertNotFound();
    }

    public function test_qr_page_renders_svg(): void
    {
        [$teacher, $classroom] = $this->setupClass();

        $this->actingAs($teacher)->get(route('guru.kelas.qr', $classroom))
            ->assertOk()->assertSee('<svg', false)->assertSee($classroom->join_code);
    }

    /* ---------- Chat & cadangan ---------- */

    public function test_chat_streams_from_teacher_gemini_key_and_strips_topic(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $done = $this->ask($student, $mentor, ['message' => 'CSS-ku tidak kebaca']);

        $this->assertSame('done', $done['type']);
        $this->assertSame('linking file css', $done['topic']);
        $this->assertNull($done['via']);
        $this->assertSame(1, $done['used']);
        $this->assertNotEmpty($done['deltas']);
        $this->assertStringNotContainsString('[[', $done['reply_text']);
        foreach ($done['deltas'] as $delta) {
            $this->assertStringNotContainsString('[[', $delta['html']);
        }

        Http::assertSent(fn (Request $req) => $req->hasHeader('x-goog-api-key', 'AIza-guru-key-0001')
            && str_contains($req->url(), 'gemini-flash-lite-latest:streamGenerateContent?alt=sse')
            && str_contains($this->systemOf($req), 'Pakai contoh toko roti.')
            && $req['contents'][0]['parts'][0]['text'] === 'CSS-ku tidak kebaca');

        $this->assertSame(2, Message::count());
        $reply = Message::where('role', 'model')->first();
        $this->assertSame('guru', $reply->key_source);
        $this->assertSame(1, $reply->hint_level);
        $this->assertSame(120, $reply->prompt_tokens);
    }

    public function test_follow_up_sends_history(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $first = $this->ask($student, $mentor, ['message' => 'Halo']);
        $this->ask($student, $mentor, ['message' => 'Sudah kucoba', 'conversation_id' => $first['conversation_id']]);

        $last = Http::recorded()->last()[0];
        $this->assertCount(3, $last['contents']);
        $this->assertSame('model', $last['contents'][1]['role']);
        $this->assertSame(1, Conversation::count());
    }

    public function test_falls_back_to_groq_when_gemini_quota_is_full(): void
    {
        [$teacher, , $mentor, $student] = $this->setupClass([], ['groq_key' => 'gsk-guru']);
        Http::fake([
            self::GEMINI => fn () => Http::response($this->geminiQuotaError(), 429),
            self::GROQ => fn () => Http::response($this->groqOk()),
        ]);

        $done = $this->ask($student, $mentor, ['message' => 'Tolong']);
        $this->assertSame('selector css', $done['topic']);
        $this->assertNull($done['via'], 'Murid tidak perlu tahu penyedia mana yang menjawab.');

        Http::assertSent(fn (Request $req) => str_contains($req->url(), 'api.groq.com')
            && $req->hasHeader('Authorization', 'Bearer gsk-guru')
            && $req['stream'] === true
            && $req['reasoning_effort'] === 'low'
            && $req['messages'][0]['role'] === 'system');

        $reply = Message::where('role', 'model')->first();
        $this->assertSame('groq', $reply->provider);
        $this->assertSame(100, $reply->prompt_tokens);

        // Guru yang mencoba mentor melihat label penyedia.
        $this->assertSame('cadangan Groq', $this->ask($teacher, $mentor, ['message' => 'Tes'])['via']);
    }

    public function test_busy_response_when_everything_is_rate_limited(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiQuotaError(), 429)]);

        $res = $this->ask($student, $mentor, ['message' => 'Tolong']);
        $this->assertSame(429, $res['status']);
        $this->assertTrue($res['busy']);
        $this->assertSame(17, $res['retry_after']);
        $this->assertSame(0, Message::count());
    }

    public function test_per_minute_limit_moves_to_groq_then_queues(): void
    {
        [$teacher, , $mentor, ] = $this->setupClass(['rpm_limit' => 1], ['groq_key' => 'gsk-guru']);
        config(['amira.groq_rpm' => 1]);
        Http::fake([
            self::GEMINI => fn () => Http::response($this->geminiOk()),
            self::GROQ => fn () => Http::response($this->groqOk()),
        ]);

        $this->assertSame('Gemini', $this->ask($teacher, $mentor, ['message' => 'Satu'])['via']);
        $this->assertSame('cadangan Groq', $this->ask($teacher, $mentor, ['message' => 'Dua'])['via']);
        $res = $this->ask($teacher, $mentor, ['message' => 'Tiga']);
        $this->assertSame(429, $res['status']);
        $this->assertTrue($res['busy']);
    }

    public function test_student_key_is_used_first(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        $student->update(['gemini_key' => 'AIza-murid-9999']);
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $done = $this->ask($student, $mentor, ['message' => 'Halo']);
        $this->assertSame(0, $done['used']);
        Http::assertSent(fn (Request $req) => $req->hasHeader('x-goog-api-key', 'AIza-murid-9999'));
        $this->assertSame('murid', Message::where('role', 'model')->value('key_source'));
    }

    public function test_daily_limit_blocks_teacher_key_but_not_student_key(): void
    {
        [, , $mentor, $student] = $this->setupClass(['daily_limit_per_student' => 1]);
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $this->assertSame('done', $this->ask($student, $mentor, ['message' => 'Satu'])['type']);
        $blocked = $this->ask($student, $mentor, ['message' => 'Dua']);
        $this->assertSame(422, $blocked['status']);
        $this->assertStringContainsString('Jatah 1 pertanyaan hari ini sudah habis', $blocked['error']);

        $student->update(['gemini_key' => 'AIza-murid-9999']);
        $this->assertSame('done', $this->ask($student->fresh(), $mentor, ['message' => 'Tiga'])['type']);
    }

    public function test_student_key_ignored_when_teacher_disallows_it(): void
    {
        [, , $mentor, $student] = $this->setupClass(['allow_student_keys' => false]);
        $student->update(['gemini_key' => 'AIza-murid-9999']);
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $this->ask($student, $mentor, ['message' => 'Halo']);
        Http::assertSent(fn (Request $req) => $req->hasHeader('x-goog-api-key', 'AIza-guru-key-0001'));
        Http::assertNotSent(fn (Request $req) => $req->hasHeader('x-goog-api-key', 'AIza-murid-9999'));
    }

    public function test_no_key_gives_clear_error(): void
    {
        [, , $mentor, $student] = $this->setupClass([], ['gemini_key' => null]);
        Http::fake();

        $res = $this->ask($student, $mentor, ['message' => 'Halo']);
        $this->assertSame(422, $res['status']);
        $this->assertSame('Guru belum memasang API key untuk kelas ini.', $res['error']);
        Http::assertNothingSent();
    }

    public function test_empty_model_reply_is_not_saved(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk('[[topik: kosong]]'))]);

        $res = $this->ask($student, $mentor, ['message' => 'Halo']);
        $this->assertSame('error', $res['type']);
        $this->assertSame(0, Message::count());
    }

    /* ---------- Tangga petunjuk ---------- */

    public function test_hint_ladder_is_controlled_by_app(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $first = $this->ask($student, $mentor, ['message' => 'Tolong']);
        $this->assertSame(1, $first['hint_used']);
        $this->assertSame(1, $first['hint_level']);
        $this->assertStringContainsString('Petunjuk 1 dari 3 (Pertanyaan penuntun)', $this->systemOf(Http::recorded()->last()[0]));

        // Minta jawaban langsung tanpa tombol: tingkat tidak naik.
        $this->ask($student, $mentor, ['message' => 'Kasih jawabannya saja', 'conversation_id' => $first['conversation_id']]);
        $this->assertSame(1, Conversation::first()->hint_level);

        $second = $this->ask($student, $mentor, ['next_hint' => '1', 'conversation_id' => $first['conversation_id']]);
        $this->assertSame(2, $second['hint_used']);
        $req = Http::recorded()->last()[0];
        $this->assertStringContainsString('Petunjuk 2 dari 3 (Petunjuk konkret)', $this->systemOf($req));
        $this->assertSame(MentorChat::NEXT_HINT_TEXT, collect($req['contents'])->last()['parts'][0]['text']);

        $this->ask($student, $mentor, ['next_hint' => '1', 'conversation_id' => $first['conversation_id']]);
        $fourth = $this->ask($student, $mentor, ['next_hint' => '1', 'conversation_id' => $first['conversation_id']]);
        $this->assertSame(3, $fourth['hint_used'], 'Tingkat berhenti di 3.');
    }

    public function test_solved_tag_resets_ladder(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        $conversation = $student->conversations()->create(['mentor_id' => $mentor->id, 'title' => 'x', 'hint_level' => 3]);
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk("Hebat, berhasil! Jelaskan balik ya.\n[[topik: linking css]] [[selesai]]"))]);

        $done = $this->ask($student, $mentor, ['message' => 'Sudah jalan!', 'conversation_id' => $conversation->id]);

        $this->assertTrue($done['solved']);
        $this->assertSame(3, $done['hint_used']);
        $this->assertSame(1, $done['hint_level']);
        $this->assertSame('Hebat, berhasil! Jelaskan balik ya.', $done['reply_text']);
    }

    public function test_no_ladder_prompt_when_mentor_disables_it(): void
    {
        [, , $mentor, $student] = $this->setupClass([], [], ['hint_ladder' => false]);
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $done = $this->ask($student, $mentor, ['message' => 'Halo']);
        $this->assertNull($done['hint_used']);
        $this->assertStringNotContainsString('Tangga petunjuk', $this->systemOf(Http::recorded()->last()[0]));
    }

    public function test_visible_part_hides_partial_tags(): void
    {
        $this->assertSame('Halo', MentorChat::visiblePart("Halo\n[[top"));
        $this->assertSame('Halo', MentorChat::visiblePart("Halo\n["));
        $this->assertSame('Halo', MentorChat::visiblePart("Halo\n[[topik: css]]"));
        $this->assertSame('Pakai arr[0]', MentorChat::visiblePart('Pakai arr[0]'));
    }

    /* ---------- Gambar ---------- */

    public function test_image_is_sent_to_gemini_and_only_owner_can_view_it(): void
    {
        [$teacher, $classroom, $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $done = $this->ask($student, $mentor, [
            'message' => 'Ini error-nya',
            'image' => UploadedFile::fake()->image('error.png', 2400, 1200),
        ]);

        Http::assertSent(function (Request $req) {
            $parts = $req['contents'][0]['parts'];

            return isset($parts[0]['inline_data']['data']) && $parts[0]['inline_data']['mime_type'] === 'image/jpeg'
                && $parts[1]['text'] === 'Ini error-nya';
        });

        $message = Message::where('role', 'user')->first();
        Storage::assertExists($message->image_path);
        [$w] = getimagesizefromstring(Storage::get($message->image_path));
        $this->assertSame(1600, $w, 'Gambar besar diperkecil.');
        $this->assertNotNull($done['image_url']);

        $this->actingAs($student)->get(route('chat.image', $message))->assertOk();
        $this->actingAs($teacher)->get(route('chat.image', $message))->assertNotFound();

        // Pesan berikutnya tidak mengirim ulang gambar lama.
        $this->ask($student, $mentor, ['message' => 'Lanjut', 'conversation_id' => $done['conversation_id']]);
        $last = Http::recorded()->last()[0];
        $this->assertStringStartsWith('[Peserta melampirkan gambar]', $last['contents'][0]['parts'][0]['text']);
    }

    public function test_image_only_message_and_groq_gets_text_note(): void
    {
        [, , $mentor, $student] = $this->setupClass([], ['gemini_key' => null, 'groq_key' => 'gsk-guru']);
        Http::fake([self::GROQ => fn () => Http::response($this->groqOk())]);

        $this->ask($student, $mentor, ['image' => UploadedFile::fake()->image('e.png', 300, 200)]);

        Http::assertSent(fn (Request $req) => str_contains(collect($req['messages'])->last()['content'], 'tidak bisa melihat gambar'));
    }

    public function test_failed_send_removes_uploaded_image(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiQuotaError(), 429)]);

        $this->ask($student, $mentor, ['message' => 'x', 'image' => UploadedFile::fake()->image('e.png', 300, 200)]);
        $this->assertSame([], Storage::allFiles('lampiran'));
    }

    /* ---------- Akses ---------- */

    public function test_outsiders_cannot_reach_mentor_or_teacher_pages(): void
    {
        [, $classroom, $mentor, $student] = $this->setupClass();
        $outsider = $this->student();

        $this->actingAs($outsider)->get(route('chat.show', $mentor))->assertForbidden();
        $this->actingAs($outsider)->post(route('chat.send', $mentor), ['message' => 'x'])->assertForbidden();
        $this->actingAs($student)->get(route('guru.kelas.show', $classroom))->assertForbidden();
        $this->actingAs($this->teacher())->get(route('guru.kelas.show', $classroom))->assertForbidden();

        $mentor->update(['is_active' => false]);
        $this->actingAs($student)->get(route('chat.show', $mentor))->assertNotFound();
    }

    public function test_student_cannot_continue_someone_elses_conversation(): void
    {
        [, $classroom, $mentor, $student] = $this->setupClass();
        $other = $this->student();
        $classroom->students()->attach($other->id);
        $conversation = $other->conversations()->create(['mentor_id' => $mentor->id, 'title' => 'Punya teman']);
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);

        $this->assertSame(404, $this->ask($student, $mentor, ['message' => 'x', 'conversation_id' => $conversation->id])['status']);
        $this->actingAs($student)->get(route('chat.conversation', [$mentor, $conversation]))->assertNotFound();
    }

    /* ---------- Materi & keamanan jawaban ---------- */

    public function test_material_text_is_sent_to_model(): void
    {
        [$teacher, , $mentor, $student] = $this->setupClass();

        $this->actingAs($teacher)->post(route('guru.mentor.materi.store', $mentor), [
            'title' => 'Modul 2',
            'file' => UploadedFile::fake()->createWithContent('modul.txt', 'Gunakan <link rel="stylesheet" href="style.css"> di dalam head.'),
        ])->assertSessionHasNoErrors();

        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);
        $this->ask($student, $mentor, ['message' => 'Halo']);

        Http::assertSent(fn (Request $req) => str_contains($this->systemOf($req), '### Modul 2')
            && str_contains($this->systemOf($req), 'href="style.css"'));
    }

    public function test_reply_html_escapes_model_html(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk("Contoh <script>alert(1)</script> dan `kode`\n[[topik: xss]]"))]);

        $done = $this->ask($student, $mentor, ['message' => 'Halo']);
        $this->assertStringNotContainsString('<script>', $done['reply_html']);
        $this->assertStringContainsString('<code>kode</code>', $done['reply_html']);
        foreach ($done['deltas'] as $delta) {
            $this->assertStringNotContainsString('<script>', $delta['html']);
        }
    }

    /* ---------- Halaman guru ---------- */

    public function test_teacher_pages_and_radar_render(): void
    {
        [$teacher, $classroom, $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk())]);
        $this->ask($student, $mentor, ['message' => 'Halo']);

        $this->actingAs($teacher)->get(route('guru.index'))->assertOk()->assertSee($classroom->join_code);
        $this->actingAs($teacher)->get(route('guru.kelas.show', [$classroom, 'tab' => 'radar']))
            ->assertOk()->assertSee('linking file css')->assertSee($student->name);
        $this->actingAs($teacher)->get(route('guru.kelas.show', $classroom))->assertOk()->assertSee(route('gabung', $classroom->join_code));
        $this->actingAs($teacher)->get(route('guru.kelas.show', [$classroom, 'tab' => 'murid']))->assertOk();
        $this->actingAs($teacher)->get(route('guru.kelas.show', [$classroom, 'tab' => 'pengaturan']))->assertOk();
        $this->actingAs($teacher)->get(route('guru.mentor.edit', $mentor))->assertOk();
        $this->actingAs($teacher)->get(route('guru.keys.edit'))->assertOk()->assertDontSee('AIza-guru-key-0001');
        $this->actingAs($teacher)->get(route('chat.show', $mentor))->assertOk()->assertSee('Mode coba guru');
    }

    public function test_onboarding_checklist_tracks_progress(): void
    {
        $teacher = $this->teacher(['gemini_key' => null]);
        $this->actingAs($teacher)->get(route('guru.index'))->assertSee('0 dari 4 selesai');

        [$ready] = $this->setupClass();
        $this->actingAs($ready)->get(route('guru.index'))->assertDontSee('Siap mengajar dengan AMIRA');
    }

    public function test_teacher_creates_mentor_with_presets(): void
    {
        $teacher = $this->teacher();
        $classroom = $teacher->taughtClassrooms()->create(['name' => 'Kelas B']);

        $this->actingAs($teacher)->post(route('guru.mentor.store', $classroom), [
            'name' => 'Mentor JS', 'color' => 'cobalt', 'presets' => "Satu\n\n Dua \nTiga", 'hint_ladder' => '1', 'is_active' => '1',
        ])->assertRedirect();

        $mentor = Mentor::first();
        $this->assertSame(['Satu', 'Dua', 'Tiga'], $mentor->presets);
        $this->assertTrue($mentor->hint_ladder);
        $this->assertFalse($mentor->explain_back);
    }

    /* ---------- Key & model ---------- */

    public function test_keys_are_encrypted_at_rest(): void
    {
        $teacher = $this->teacher();
        $raw = DB::table('users')->where('id', $teacher->id)->value('gemini_key');

        $this->assertNotSame('AIza-guru-key-0001', $raw);
        $this->assertSame('AIza-guru-key-0001', $teacher->fresh()->gemini_key);
    }

    public function test_saving_key_tests_it_and_stores_models(): void
    {
        $teacher = $this->teacher(['gemini_key' => null]);
        Http::fake([self::GEMINI => fn () => Http::response(['models' => [
            ['name' => 'models/gemini-3.5-flash-lite', 'supportedGenerationMethods' => ['generateContent']],
            ['name' => 'models/gemini-flash-lite-latest', 'supportedGenerationMethods' => ['generateContent']],
            ['name' => 'models/gemini-3.8-flash-tts', 'supportedGenerationMethods' => ['generateContent']],
            ['name' => 'models/text-embedding-004', 'supportedGenerationMethods' => ['embedContent']],
        ]])]);

        $this->actingAs($teacher)->put(route('guru.keys.update'), ['provider' => 'gemini', 'key' => 'AIza-baru'])
            ->assertSessionHasNoErrors();

        $teacher->refresh();
        $this->assertSame('AIza-baru', $teacher->gemini_key);
        $this->assertSame(['gemini-3.5-flash-lite', 'gemini-flash-lite-latest'], $teacher->gemini_models);

        // Kelas baru otomatis memakai model yang tersedia.
        $this->actingAs($teacher)->post(route('guru.kelas.store'), ['name' => 'Kelas C']);
        $this->assertSame('gemini-flash-lite-latest', Classroom::first()->gemini_model);
    }

    public function test_invalid_key_is_rejected(): void
    {
        $teacher = $this->teacher(['groq_key' => null]);
        Http::fake([self::GROQ => fn () => Http::response(['error' => ['message' => 'Invalid API Key']], 401)]);

        $this->actingAs($teacher)->put(route('guru.keys.update'), ['provider' => 'groq', 'key' => 'gsk-salah'])
            ->assertSessionHasErrors('groq_key');
        $this->assertNull($teacher->fresh()->groq_key);
    }

    public function test_warning_when_class_model_is_unavailable(): void
    {
        [$teacher, $classroom] = $this->setupClass(['groq_model' => 'llama-3.3-70b-versatile'], [
            'groq_key' => 'gsk-guru', 'groq_models' => ['openai/gpt-oss-120b'], 'gemini_models' => ['gemini-flash-lite-latest'],
        ]);

        $this->actingAs($teacher)->get(route('guru.kelas.show', $classroom))
            ->assertSee('Model Groq &quot;llama-3.3-70b-versatile&quot; tidak tersedia', false);
        $this->actingAs($teacher)->get(route('guru.kelas.show', [$classroom, 'tab' => 'pengaturan']))
            ->assertSee('llama-3.3-70b-versatile (tidak tersedia)');
    }

    /* ---------- Gelombang 2: Radar ---------- */

    public function test_explain_back_result_is_stored_and_feeds_attention(): void
    {
        [$teacher, $classroom, $mentor, $student] = $this->setupClass();
        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk("Hampir tepat, tapi href relatif terhadap file HTML.\n[[topik: path relatif]] [[paham: belum]]"))]);

        $first = $this->ask($student, $mentor, ['message' => 'Aku coba jelaskan balik: href dibaca dari folder css']);
        $this->assertFalse(Message::where('role', 'model')->first()->understood);
        $this->assertStringNotContainsString('paham', $first['reply_text']);

        $conv = Conversation::first();
        $conv->messages()->create(['role' => 'model', 'content' => 'x', 'topic' => 'path relatif', 'hint_level' => 3]);
        $conv->messages()->create(['role' => 'model', 'content' => 'y', 'topic' => 'path relatif', 'hint_level' => 3]);

        $this->actingAs($teacher)->get(route('guru.kelas.show', [$classroom, 'tab' => 'radar']))
            ->assertOk()
            ->assertSee('Perlu disapa')
            ->assertSee('2× sampai petunjuk 3')
            ->assertSee('penjelasan balik 1× belum tepat')
            ->assertSee('0 paham · 1 belum');
    }

    public function test_mini_lesson_suggestion_uses_only_aggregates(): void
    {
        [$teacher, $classroom, $mentor, $student] = $this->setupClass();
        $conv = $student->conversations()->create(['mentor_id' => $mentor->id, 'title' => 'rahasia']);
        $conv->messages()->create(['role' => 'user', 'content' => 'PESAN PRIBADI MURID', 'topic' => 'flexbox']);
        $conv->messages()->create(['role' => 'model', 'content' => 'jawaban', 'topic' => 'flexbox', 'hint_level' => 3]);

        Http::fake([self::GEMINI => fn () => Http::response($this->geminiOk("### Flexbox\nMulai dari demo."))]);

        $this->actingAs($teacher)->post(route('guru.kelas.suggest', $classroom))
            ->assertRedirect(route('guru.kelas.show', [$classroom, 'tab' => 'radar']));

        Http::assertSent(function (Request $req) use ($student) {
            $brief = $req['contents'][0]['parts'][0]['text'];

            return str_contains($brief, 'flexbox: 1 | 1')
                && ! str_contains($brief, 'PESAN PRIBADI')
                && ! str_contains($brief, $student->name);
        });

        $this->assertStringContainsString('### Flexbox', $classroom->fresh()->radar_suggestion);
        $this->actingAs($teacher)->get(route('guru.kelas.show', [$classroom, 'tab' => 'radar']))->assertSee('Mulai dari demo.');
    }

    public function test_mini_lesson_needs_data(): void
    {
        [$teacher, $classroom] = $this->setupClass();
        Http::fake();

        $this->actingAs($teacher)->post(route('guru.kelas.suggest', $classroom))->assertSessionHas('status');
        Http::assertNothingSent();
    }

    /* ---------- Gelombang 2: template & ikon ---------- */

    public function test_template_prefills_mentor_form_and_icon_is_saved(): void
    {
        $teacher = $this->teacher();
        $classroom = $teacher->taughtClassrooms()->create(['name' => 'Kelas T']);

        $this->actingAs($teacher)->get(route('guru.mentor.create', [$classroom, 'template' => 'debugging']))
            ->assertOk()->assertSee('Pelatih Debugging')->assertSee('Ini pesan error-nya:');

        $this->actingAs($teacher)->post(route('guru.mentor.store', $classroom), [
            'name' => 'Pelatih Debugging', 'color' => 'coral', 'icon' => 'bug', 'is_active' => '1',
        ])->assertRedirect();
        $this->assertSame('bug', Mentor::first()->icon);

        $this->actingAs($teacher)->post(route('guru.mentor.store', $classroom), [
            'name' => 'Salah', 'color' => 'coral', 'icon' => '<script>',
        ])->assertSessionHasErrors('icon');
    }

    /* ---------- Gelombang 2: privasi ---------- */

    public function test_student_must_acknowledge_privacy_before_chatting(): void
    {
        [, , $mentor, $student] = $this->setupClass();
        $student->update(['privacy_ack_at' => null]);
        Http::fake();

        $this->actingAs($student)->get(route('chat.show', $mentor))->assertSee('Sebelum mulai, baca ini dulu');
        $blocked = $this->ask($student, $mentor, ['message' => 'Halo']);
        $this->assertSame(422, $blocked['status']);
        Http::assertNothingSent();

        $this->actingAs($student)->postJson(route('privasi.setuju'))->assertOk();
        $this->assertNotNull($student->fresh()->privacy_ack_at);
        $this->actingAs($student->fresh())->get(route('chat.show', $mentor))->assertDontSee('Sebelum mulai, baca ini dulu');
        $this->get(route('privasi'))->assertOk();
    }

    /* ---------- Gelombang 2: akun ---------- */

    public function test_forgot_password_flow(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $student = $this->student(['email' => 'rina@example.com']);

        $this->post(route('password.email'), ['email' => 'rina@example.com'])->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => 'tidak-ada@example.com'])->assertSessionHas('status');

        $token = null;
        \Illuminate\Support\Facades\Notification::assertSentTo($student, \Illuminate\Auth\Notifications\ResetPassword::class, function ($n) use (&$token, $student) {
            $token = $n->token;

            return str_contains($n->toMail($student)->subject, 'Atur ulang kata sandi');
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => 'rina@example.com']))->assertOk();
        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'rina@example.com', 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123',
        ])->assertRedirect(route('login'));

        $this->post('/masuk', ['email' => 'rina@example.com', 'password' => 'sandibaru123'])->assertRedirect();
        $this->assertAuthenticatedAs($student->fresh());
    }

    public function test_change_password_and_teacher_account_page(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->get(route('akun.edit'))->assertOk()->assertSee('Pengaturan AI')->assertDontSee('Key Gemini milikmu');

        $this->actingAs($teacher)->put(route('akun.password'), [
            'current_password' => 'salah', 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($teacher)->put(route('akun.password'), [
            'current_password' => 'password', 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('sandibaru123', $teacher->fresh()->password));

        // Key guru tidak bisa diganti lewat halaman Akun (harus lewat Pengaturan AI yang mengetes key).
        $this->actingAs($teacher)->put(route('akun.update'), ['name' => 'Pak Guru', 'gemini_key' => 'AIza-tanpa-tes']);
        $this->assertSame('AIza-guru-key-0001', $teacher->fresh()->gemini_key);
        $this->assertSame('Pak Guru', $teacher->fresh()->name);
    }

    public function test_chat_page_config_is_valid_json(): void
    {
        [, , $mentor, $student] = $this->setupClass([], [], ['icon' => 'bug']);

        $html = $this->actingAs($student)->get(route('chat.show', $mentor))->assertOk()->getContent();
        preg_match('#<script id="chat-config" type="application/json">(.*?)</script>#s', $html, $m);
        $config = json_decode($m[1] ?? '', true);

        $this->assertIsArray($config, 'Konfigurasi chat untuk JavaScript harus JSON yang sah.');
        $this->assertStringContainsString('<svg', $config['avatarHtml']);
        $this->assertSame(1, $config['hintLevel']);
    }
}
