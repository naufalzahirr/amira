<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Mentor;
use App\Models\Message;
use App\Services\Ai\MentorChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ChatController extends Controller
{
    public function show(Request $request, MentorChat $chat, Mentor $mentor, ?Conversation $conversation = null)
    {
        $user = $request->user();
        $classroom = $mentor->classroom;
        abort_unless($classroom->hasMember($user), 403);
        abort_unless($mentor->is_active || $user->id === $classroom->teacher_id, 404);

        if ($conversation) {
            abort_unless($conversation->user_id === $user->id && $conversation->mentor_id === $mentor->id, 404);
        }

        $conversations = $user->conversations()->where('mentor_id', $mentor->id)->latest('updated_at')->take(30)->get();
        $messages = $conversation?->messages()->orderBy('id')->get() ?? collect();
        $used = $user->isTeacher() ? null : $chat->usedToday($user, $classroom);
        $presets = $mentor->presets ?: config('amira.default_presets');
        $hintLevel = $conversation?->hint_level ?? 1;
        // Avatar yang sama dipakai JavaScript untuk gelembung jawaban baru.
        $avatarHtml = Blade::render('<x-mentor-avatar :mentor="$mentor" />', ['mentor' => $mentor]);

        return view('chat.show', compact('mentor', 'classroom', 'conversation', 'conversations', 'messages', 'used', 'presets', 'hintLevel', 'avatarHtml'));
    }

    public function send(Request $request, Mentor $mentor, MentorChat $chat)
    {
        $user = $request->user();
        abort_unless($mentor->classroom->hasMember($user), 403);
        abort_unless($mentor->is_active || $user->id === $mentor->classroom->teacher_id, 404);

        if (! $user->isTeacher() && ! $user->privacy_ack_at) {
            return response()->json(['error' => 'Baca dan setujui pemberitahuan privasi di halaman chat dulu.'], 422);
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:6000', 'required_without_all:image,next_hint'],
            'conversation_id' => ['nullable', 'integer'],
            'next_hint' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'message.required_without_all' => 'Tulis pesan atau lampirkan gambar.',
            'image.mimes' => 'Gambar harus JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 8 MB.',
        ]);

        $conversation = null;
        if (! empty($data['conversation_id'])) {
            $conversation = Conversation::where('id', $data['conversation_id'])
                ->where('user_id', $user->id)
                ->where('mentor_id', $mentor->id)
                ->firstOrFail();
        }

        $result = $chat->start(
            $user, $mentor, $conversation, (string) ($data['message'] ?? ''),
            $request->file('image'), $request->boolean('next_hint'),
        );

        if ($result->status === 'busy') {
            return response()->json([
                'busy' => true,
                'retry_after' => $result->retryAfter,
                'message' => 'AMIRA sedang ramai dipakai teman sekelasmu.',
            ], 429);
        }

        if ($result->status === 'failed') {
            return response()->json(['error' => $result->error], 422);
        }

        $stream = $result->stream;
        $isTeacher = $user->isTeacher();

        return response()->stream(function () use ($stream, $mentor, $user, $chat, $isTeacher) {
            // Lepas buffer PHP supaya tiap potongan langsung terkirim (tes menangkap output lewat buffer sendiri).
            if (! app()->runningUnitTests()) {
                @ini_set('zlib.output_compression', '0');
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
            }

            $send = function (array $event) {
                echo 'data: '.json_encode($event, JSON_UNESCAPED_UNICODE)."\n\n";
                flush();
            };

            try {
                $reply = $stream->run(fn (string $visible) => $send([
                    'type' => 'delta',
                    'html' => Message::renderMarkdown($visible),
                ]));
            } catch (Throwable $e) {
                report($e);
                $send(['type' => 'error', 'message' => 'Jawaban terputus. Kirim ulang pesanmu.']);

                return;
            }

            $conversation = $reply->conversation;
            $userMessage = $conversation->messages()->where('role', 'user')->latest('id')->first();

            $send([
                'type' => 'done',
                'conversation_id' => $conversation->id,
                'conversation_url' => route('chat.conversation', [$mentor, $conversation]),
                'reply_html' => $reply->html(),
                'reply_text' => $reply->content,
                'topic' => $reply->topic,
                'solved' => $reply->solved,
                'hint_used' => $reply->hint_level,
                'hint_level' => $conversation->hint_level,
                'image_url' => $userMessage?->image_path ? route('chat.image', $userMessage) : null,
                'via' => $isTeacher ? $this->via($stream->attempt) : null,
                'used' => $isTeacher ? null : $chat->usedToday($user, $mentor->classroom),
            ]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /** Lampiran gambar hanya bisa dibuka pemilik percakapan. */
    public function image(Request $request, Message $message)
    {
        abort_unless($message->image_path && $message->conversation->user_id === $request->user()->id, 404);

        return Storage::response($message->image_path, null, [
            'Content-Type' => $message->image_mime,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function destroy(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
        $mentor = $conversation->mentor;

        foreach ($conversation->messages()->whereNotNull('image_path')->pluck('image_path') as $path) {
            Storage::delete($path);
        }
        $conversation->delete();

        return redirect()->route('chat.show', $mentor)->with('status', 'Percakapan dihapus.');
    }

    private function via(array $attempt): string
    {
        return match (true) {
            $attempt['provider'] === 'groq' => 'cadangan Groq',
            $attempt['source'] === 'murid' => 'key murid',
            default => 'Gemini',
        };
    }
}
