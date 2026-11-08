<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\GroqClient;
use App\Services\Ai\ProviderException;
use Illuminate\Http\Request;

class KeyController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('teacher.keys', [
            'user' => $user,
            'geminiMasked' => User::maskKey($user->gemini_key),
            'groqMasked' => User::maskKey($user->groq_key),
        ]);
    }

    /** Key dites dulu sebelum disimpan; daftar modelnya ikut disimpan untuk pilihan di pengaturan kelas. */
    public function update(Request $request, GeminiClient $gemini, GroqClient $groq)
    {
        $data = $request->validate([
            'provider' => ['required', 'in:gemini,groq'],
            'key' => ['nullable', 'string', 'max:200'],
            'remove' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $provider = $data['provider'];
        $label = $provider === 'gemini' ? 'Gemini' : 'Groq';

        if ($request->boolean('remove')) {
            $user->forceFill(["{$provider}_key" => null, "{$provider}_models" => null])->save();

            return back()->with('status', "Key {$label} dihapus.");
        }

        $key = trim((string) ($data['key'] ?? ''));
        if ($key === '') {
            return back()->withErrors(["{$provider}_key" => 'Tempel key-nya dulu.']);
        }

        try {
            $models = $provider === 'gemini' ? $gemini->listModels($key) : $groq->listModels($key);
        } catch (ProviderException $e) {
            if (str_contains($e->getMessage(), 'tidak valid')) {
                return back()->withErrors(["{$provider}_key" => $e->getMessage().' Key tidak disimpan.']);
            }
            // Gangguan jaringan: tetap simpan, daftar model menyusul saat tes berikutnya.
            $user->forceFill(["{$provider}_key" => $key])->save();

            return back()->with('status', "Key {$label} disimpan, tetapi belum bisa dites: {$e->getMessage()}");
        }

        $user->forceFill(["{$provider}_key" => $key, "{$provider}_models" => $models])->save();

        return back()->with('status', "Key {$label} disimpan dan berfungsi. ".count($models).' model tersedia.');
    }

    /** Tes ulang key yang tersimpan dan perbarui daftar modelnya. */
    public function test(Request $request, GeminiClient $gemini, GroqClient $groq)
    {
        $data = $request->validate(['provider' => ['required', 'in:gemini,groq']]);
        $user = $request->user();
        $provider = $data['provider'];
        $key = $user->{"{$provider}_key"};

        if (! $key) {
            return response()->json(['ok' => false, 'message' => 'Belum ada key tersimpan.'], 422);
        }

        try {
            $models = $provider === 'gemini' ? $gemini->listModels($key) : $groq->listModels($key);
        } catch (ProviderException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $user->forceFill(["{$provider}_models" => $models])->save();

        return response()->json([
            'ok' => true,
            'message' => 'Key berfungsi. '.count($models).' model tersedia.',
            'models' => $models,
        ]);
    }
}
