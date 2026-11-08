<?php

namespace App\Services;

use App\Models\AmiraSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmiraChatService
{
    /**
     * Kirim pesan ke AMIRA dan dapatkan balasan.
     *
     * @param  string       $userMessage   Teks pertanyaan user (boleh kosong kalau cuma image)
     * @param  string|null  $provider      'gemini' atau 'groq'
     * @param  string|null  $model         ID model
     * @param  string|null  $contextText   Tambahan konteks (ringkasan resource/mapel/dokumen)
     * @param  string|null  $imageBase64   Data gambar base64 (jika ada)
     * @param  string|null  $imageMimeType Mime type gambar, mis: image/jpeg
     * @param  array|null   $fileData      Data file Gemini: ['uri' => ..., 'mimeType' => ...]
     * @return string
     */
    public function chat(
        string $userMessage,
        ?string $provider = null,
        ?string $model = null,
        ?string $contextText = null,
        ?string $imageBase64 = null,
        ?string $imageMimeType = null,
        ?array  $fileData = null
    ): string {
        $provider = $provider ?: config('amira.default_provider', 'gemini');
        $model    = $model ?: $this->getDefaultModelFor($provider);

        try {
            // Normalisasi model Gemini (misal: "gemini-2.5-flash::search")
            $useSearch = false;
            if ($provider === 'gemini') {
                [$normalizedModel, $useSearch] = $this->normalizeGeminiModel($model);
                $model = $normalizedModel;
            }

            return match ($provider) {
                'groq'   => $this->callGroqChat(
                    $userMessage,
                    $model,
                    $contextText,
                    $imageBase64,
                    $imageMimeType
                ),
                'gemini' => $this->callGemini(
                    $userMessage,
                    $model,
                    $contextText,
                    $imageBase64,
                    $imageMimeType,
                    $useSearch,
                    $fileData
                ),
                default  => $this->callGemini(
                    $userMessage,
                    $model,
                    $contextText,
                    $imageBase64,
                    $imageMimeType,
                    $useSearch,
                    $fileData
                ),
            };

        } catch (\Throwable $e) {
            Log::error('AMIRA chat error', [
                'provider' => $provider,
                'model'    => $model,
                'error'    => $e->getMessage(),
            ]);

            return 'Maaf, AMIRA sedang mengalami gangguan. Coba beberapa saat lagi ya.';
        }
    }

    /**
     * Ambil default model per provider dari config.
     */
    protected function getDefaultModelFor(string $provider): string
    {
        $defaultModels = config('amira.default_models', []);
        if (isset($defaultModels[$provider])) {
            return $defaultModels[$provider];
        }

        $providers = config('amira.providers', []);
        if (isset($providers[$provider]['models']) && is_array($providers[$provider]['models'])) {
            $keys = array_keys($providers[$provider]['models']);
            if (count($keys) > 0) {
                return $keys[0];
            }
        }

        return 'gemini-1.5-pro';
    }

    /**
     * Ambil system prompt dari DB (kalau ada), kalau tidak pakai config.
     */
    protected function getSystemPrompt(): string
    {
        $setting = AmiraSetting::first();

        if ($setting && $setting->system_prompt) {
            return $setting->system_prompt;
        }

        return config('amira.system_prompt');
    }

    /**
     * Bangun prompt final untuk dikirim ke model.
     */
    protected function buildPrompt(string $userMessage, ?string $contextText = null): string
    {
        $system = $this->getSystemPrompt();
        $parts  = [$system];

        if ($contextText) {
            $parts[] = "Konteks bahan ajar / dokumen:\n" . $contextText;
        }

        if (trim($userMessage) !== '') {
            $parts[] = "Pertanyaan / instruksi siswa:\n" . $userMessage;
        } else {
            $parts[] = "Jelaskan isi gambar/dokumen yang dikirim siswa dengan bahasa yang mudah dipahami.";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Upload file ke Gemini File API.
     * Mengembalikan:
     *   ['uri' => file_uri, 'name' => name, 'mimeType' => ..., 'displayName' => ...]
     * atau null kalau gagal (tidak melempar exception).
     */
    public function uploadGeminiFile(UploadedFile $file): ?array
    {
        $apiKey = config('services.gemini.key');

        if (!$apiKey) {
            Log::error('GEMINI_API_KEY belum diset di .env');
            return null;
        }

        $baseUrl     = 'https://generativelanguage.googleapis.com';
        $mimeType    = $file->getClientMimeType();
        $numBytes    = $file->getSize();
        $displayName = $file->getClientOriginalName() ?: 'Dokumen Siswa';

        // === STEP 1: mulai upload resumable ===
        $start = Http::timeout(60)
            ->withHeaders([
                'X-Goog-Upload-Protocol'             => 'resumable',
                'X-Goog-Upload-Command'              => 'start',
                'X-Goog-Upload-Header-Content-Length'=> $numBytes,
                'X-Goog-Upload-Header-Content-Type'  => $mimeType,
                'Content-Type'                       => 'application/json',
            ])
            ->post("{$baseUrl}/upload/v1beta/files?key={$apiKey}", [
                'file' => [
                    'display_name' => $displayName,
                ],
            ]);

        if ($start->failed()) {
            Log::error('Gemini file upload start error', [
                'status' => $start->status(),
                'body'   => $start->body(),
            ]);
            return null;
        }

        $uploadUrl = $start->header('X-Goog-Upload-Url')
            ?? $start->header('x-goog-upload-url');

        if (!$uploadUrl) {
            Log::error('Gemini file upload: missing X-Goog-Upload-Url header', [
                'headers' => $start->headers(),
            ]);
            return null;
        }

        // === STEP 2: upload bytes & finalize ===
        $bytes = file_get_contents($file->getRealPath());

        $upload = Http::timeout(300)
            ->withHeaders([
                'Content-Length'        => strlen($bytes),
                'X-Goog-Upload-Offset'  => 0,
                'X-Goog-Upload-Command' => 'upload, finalize',
            ])
            ->withBody($bytes, $mimeType)
            ->post($uploadUrl);

        if ($upload->failed()) {
            Log::error('Gemini file upload finalize error', [
                'status' => $upload->status(),
                'body'   => $upload->body(),
            ]);
            return null;
        }

        $data    = $upload->json() ?? [];
        $fileObj = $data['file'] ?? $data;

        $fileUri  = $fileObj['uri']  ?? null;
        $fileName = $fileObj['name'] ?? null;

        if (!$fileUri) {
            Log::error('Gemini file upload: missing uri in response', ['data' => $data]);
            return null;
        }

        return [
            'uri'         => $fileUri,
            'name'        => $fileName,
            'mimeType'    => $mimeType,
            'displayName' => $displayName,
        ];
    }

    /**
     * Panggil Gemini (teks + opsional gambar / file).
     *
     * Catatan:
     * - Untuk analisis gambar, gunakan model yang mendukung multimodal
     *   (misalnya gemini-1.5-pro atau gemini-2.5-flash sesuai akunmu).
     */
    protected function callGemini(
        string $userMessage,
        string $model,
        ?string $contextText,
        ?string $imageBase64,
        ?string $imageMimeType,
        bool $useSearch = false,
        ?array $fileData = null
    ): string {
        $apiKey = config('services.gemini.key');

        if (!$apiKey) {
            return 'Maaf, konfigurasi Gemini belum lengkap (API key belum diset).';
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $prompt = $this->buildPrompt($userMessage, $contextText);

        $parts = [
            ['text' => $prompt],
        ];

        // Jika ada file dari File API (pdf/dokumen)
        if ($fileData && isset($fileData['uri'], $fileData['mimeType'])) {
            $parts[] = [
                'file_data' => [
                    'mime_type' => $fileData['mimeType'],
                    'file_uri'  => $fileData['uri'],
                ],
            ];
        }

        // Jika ada gambar yang diunggah user
        if ($imageBase64 && $imageMimeType) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $imageMimeType,
                    'data'     => $imageBase64,
                ],
            ];
        }

        $body = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => $parts,
                ],
            ],
        ];

        // Aktifkan Google Search kalau model pakai "::search"
        if ($useSearch) {
            $body['tools'] = [
                [
                    'google_search' => new \stdClass(),
                ],
            ];
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
            ->post($endpoint, $body);

        if ($response->failed()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return 'Maaf, AMIRA (Gemini) tidak bisa menjawab saat ini. Coba ganti pertanyaan atau ulangi beberapa saat lagi.';
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text']
            ?? 'Maaf, AMIRA (Gemini) tidak bisa menjawab saat ini.';
    }

    /**
     * Panggil Groq (chat completions, teks + opsional gambar).
     *
     * - Untuk vision gunakan model Llama 4 Scout / Maverick di Groq.
     */
    protected function callGroqChat(
        string $userMessage,
        string $model,
        ?string $contextText,
        ?string $imageBase64,
        ?string $imageMimeType
    ): string {
        $apiKey = config('services.groq.key');

        if (!$apiKey) {
            return 'Maaf, konfigurasi Groq belum lengkap (API key belum diset).';
        }

        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

        $prompt = $this->buildPrompt($userMessage, $contextText);

        // Jika ada gambar → content harus array (text + image_url)
        if ($imageBase64 && $imageMimeType) {
            $content = [
                [
                    'type' => 'text',
                    'text' => $prompt,
                ],
                [
                    'type'      => 'image_url',
                    'image_url' => [
                        'url' => "data:{$imageMimeType};base64,{$imageBase64}",
                    ],
                ],
            ];
        } else {
            $content = $prompt;
        }

        $response = Http::timeout(60)
            ->withToken($apiKey)
            ->post($endpoint, [
                'model'       => $model,
                'temperature' => 0.6,
                'max_tokens'  => 512,
                'messages'    => [
                    [
                        'role'    => 'user',
                        'content' => $content,
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Groq API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return 'Maaf, AMIRA (Groq) tidak bisa menjawab saat ini. Coba beberapa saat lagi ya.';
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content']
            ?? 'Maaf, AMIRA (Groq) tidak bisa menjawab saat ini.';
    }

    /**
     * Normalisasi nama model Gemini:
     * - "gemini-2.5-flash::search" => ["gemini-2.5-flash", true]
     * - "gemini-2.5-flash"         => ["gemini-2.5-flash", false]
     */
    protected function normalizeGeminiModel(?string $model): array
    {
        $useSearch = false;
        $baseModel = $model ?: config('services.gemini.model', 'gemini-2.5-flash');

        if (str_contains($baseModel, '::search')) {
            [$baseModel] = explode('::', $baseModel, 2);
            $useSearch = true;
        }

        return [$baseModel, $useSearch];
    }
}
