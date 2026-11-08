<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeminiClient
{
    /**
     * Membuka jawaban streaming. Gagal sebelum teks pertama (kuota, key, model) melempar ProviderException,
     * sehingga MentorChat bisa pindah ke key berikutnya.
     *
     * @param  array<int, array{role: string, content: string, image?: array{mime: string, data: string}}>  $messages
     */
    public function stream(string $apiKey, string $model, string $system, array $messages): StreamHandle
    {
        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => array_map(function ($m) {
                $parts = [];
                if (! empty($m['image'])) {
                    $parts[] = ['inline_data' => ['mime_type' => $m['image']['mime'], 'data' => $m['image']['data']]];
                }
                $parts[] = ['text' => $m['content']];

                return ['role' => $m['role'] === 'model' ? 'model' : 'user', 'parts' => $parts];
            }, $messages),
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            ],
        ];

        try {
            $response = Http::withOptions(['stream' => true])
                ->connectTimeout(10)
                ->timeout(config('amira.gemini.timeout'))
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post(config('amira.gemini.base_url')."/models/{$model}:streamGenerateContent?alt=sse", $body);
        } catch (ConnectionException $e) {
            throw new ProviderException('Gemini tidak bisa dihubungi.');
        }

        $this->throwIfFailed($response);

        return new StreamHandle(
            $response->toPsrResponse()->getBody(),
            function (array $event, StreamHandle $handle): ?string {
                if (isset($event['error'])) {
                    throw new ProviderException('Gemini menghentikan jawaban: '.($event['error']['message'] ?? 'galat'));
                }
                if (isset($event['usageMetadata'])) {
                    $handle->promptTokens = $event['usageMetadata']['promptTokenCount'] ?? $handle->promptTokens;
                    $handle->outputTokens = $event['usageMetadata']['candidatesTokenCount'] ?? $handle->outputTokens;
                }
                if (isset($event['modelVersion'])) {
                    $handle->model = $event['modelVersion'];
                }

                return collect($event['candidates'][0]['content']['parts'] ?? [])
                    ->reject(fn ($p) => ($p['thought'] ?? false) === true)
                    ->pluck('text')
                    ->filter()
                    ->implode('');
            },
            $model,
        );
    }

    /** Daftar model yang bisa dipakai key ini untuk membuat teks. */
    public function listModels(string $apiKey): array
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->get(config('amira.gemini.base_url').'/models', ['pageSize' => 200]);
        } catch (ConnectionException $e) {
            throw new ProviderException('Gemini tidak bisa dihubungi.');
        }

        $this->throwIfFailed($response);

        return collect($response->json('models') ?? [])
            ->filter(fn ($m) => in_array('generateContent', $m['supportedGenerationMethods'] ?? [], true))
            ->map(fn ($m) => str_replace('models/', '', $m['name']))
            ->filter(fn ($name) => str_starts_with($name, 'gemini') && ! preg_match('/tts|image|embedding|audio|live|banana|transcribe|robotics|computer-use|customtools|omni/i', $name))
            ->values()
            ->all();
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $json = json_decode($response->body(), true);
        $error = is_array($json) ? ($json['error'] ?? $json[0]['error'] ?? []) : [];
        $detail = strtolower($error['message'] ?? '');

        if ($status === 429) {
            throw new ProviderException('Kuota Gemini sedang penuh.', rateLimited: true, retryAfter: $this->retryDelay($error));
        }

        if (in_array($status, [400, 401, 403], true) && str_contains($detail, 'api key')) {
            throw new ProviderException('API key Gemini tidak valid.');
        }

        if ($status === 404) {
            throw new ProviderException('Model Gemini tidak ditemukan. Periksa pilihan model di pengaturan kelas.');
        }

        if ($status >= 500) {
            throw new ProviderException('Server Gemini sedang bermasalah.', rateLimited: true, retryAfter: 15);
        }

        throw new ProviderException("Gemini menolak permintaan ({$status}).");
    }

    /** Gemini menaruh waktu tunggu di error.details[].retryDelay, misal "17s". */
    private function retryDelay(array $error): ?int
    {
        foreach ($error['details'] ?? [] as $detail) {
            if (isset($detail['retryDelay']) && preg_match('/(\d+)/', $detail['retryDelay'], $m)) {
                return (int) $m[1];
            }
        }

        return null;
    }
}
