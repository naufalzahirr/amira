<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GroqClient
{
    /**
     * Model cadangan dianggap tidak bisa melihat gambar; gambar diganti catatan teks.
     *
     * @param  array<int, array{role: string, content: string, image?: array}>  $messages
     */
    public function stream(string $apiKey, string $model, string $system, array $messages): StreamHandle
    {
        $payload = [
            'model' => $model,
            'temperature' => 0.7,
            'max_completion_tokens' => 4096,
            'stream' => true,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $system]],
                array_map(fn ($m) => [
                    'role' => $m['role'] === 'model' ? 'assistant' : 'user',
                    'content' => empty($m['image']) ? $m['content']
                        : "[Peserta melampirkan gambar, tetapi mentor saat ini tidak bisa melihat gambar. Minta peserta menyalin teks error atau kodenya.]\n\n".$m['content'],
                ], $messages),
            ),
        ];

        // Model GPT-OSS berpikir dulu; token berpikir ikut dihitung, jadi dibuat ringan.
        if (str_starts_with($model, 'openai/gpt-oss')) {
            $payload['reasoning_effort'] = 'low';
        }

        try {
            $response = Http::withOptions(['stream' => true])
                ->connectTimeout(10)
                ->timeout(config('amira.groq.timeout'))
                ->withToken($apiKey)
                ->post(config('amira.groq.base_url').'/chat/completions', $payload);
        } catch (ConnectionException $e) {
            throw new ProviderException('Groq tidak bisa dihubungi.');
        }

        $this->throwIfFailed($response);

        return new StreamHandle(
            $response->toPsrResponse()->getBody(),
            function (array $event, StreamHandle $handle): ?string {
                if (isset($event['error'])) {
                    throw new ProviderException('Groq menghentikan jawaban: '.($event['error']['message'] ?? 'galat'));
                }
                $usage = $event['x_groq']['usage'] ?? $event['usage'] ?? null;
                if ($usage) {
                    $handle->promptTokens = $usage['prompt_tokens'] ?? $handle->promptTokens;
                    $handle->outputTokens = $usage['completion_tokens'] ?? $handle->outputTokens;
                }
                if (isset($event['model'])) {
                    $handle->model = $event['model'];
                }

                return $event['choices'][0]['delta']['content'] ?? null;
            },
            $model,
        );
    }

    public function listModels(string $apiKey): array
    {
        try {
            $response = Http::timeout(20)->withToken($apiKey)->get(config('amira.groq.base_url').'/models');
        } catch (ConnectionException $e) {
            throw new ProviderException('Groq tidak bisa dihubungi.');
        }

        $this->throwIfFailed($response);

        return collect($response->json('data') ?? [])
            ->filter(fn ($m) => ($m['active'] ?? true) !== false)
            ->pluck('id')
            ->reject(fn ($id) => preg_match('/whisper|tts|guard|playai|orpheus/i', $id))
            ->sort()
            ->values()
            ->all();
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $message = (string) (json_decode($response->body(), true)['error']['message'] ?? '');

        if ($status === 429) {
            $retry = (int) ($response->header('retry-after') ?: 20);
            throw new ProviderException('Kuota Groq sedang penuh.', rateLimited: true, retryAfter: max(1, $retry));
        }

        if ($status === 401) {
            throw new ProviderException('API key Groq tidak valid.');
        }

        if ($status === 404 || ($status === 400 && str_contains($message, 'model'))) {
            throw new ProviderException('Model Groq tidak ditemukan. Periksa pilihan model di pengaturan kelas.');
        }

        if ($status >= 500) {
            throw new ProviderException('Server Groq sedang bermasalah.', rateLimited: true, retryAfter: 15);
        }

        throw new ProviderException("Groq menolak permintaan ({$status}).");
    }
}
