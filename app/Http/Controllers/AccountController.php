<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\ProviderException;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('account.edit', [
            'user' => $user,
            'maskedKey' => User::maskKey($user->gemini_key),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'gemini_key' => ['nullable', 'string', 'max:200'],
            'remove_key' => ['nullable', 'boolean'],
        ]);

        $user->name = $data['name'];

        // Key guru diatur di Pengaturan AI (dites dulu sebelum disimpan).
        if ($user->isTeacher()) {
            $user->save();

            return back()->with('status', 'Akun tersimpan.');
        }

        if ($request->boolean('remove_key')) {
            $user->gemini_key = null;
        } elseif (! empty($data['gemini_key'])) {
            $user->gemini_key = trim($data['gemini_key']);
        }

        $user->save();

        return back()->with('status', 'Akun tersimpan.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
        ], [
            'current_password.current_password' => 'Kata sandi lama tidak cocok.',
        ]);

        $request->user()->forceFill(['password' => $request->input('password')])->save();

        return back()->with('status', 'Kata sandi diganti.');
    }

    public function ackPrivacy(Request $request)
    {
        $request->user()->forceFill(['privacy_ack_at' => now()])->save();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function testKey(Request $request, GeminiClient $gemini)
    {
        $key = trim((string) $request->input('key')) ?: $request->user()->gemini_key;

        if (! $key) {
            return response()->json(['ok' => false, 'message' => 'Belum ada key untuk dites.'], 422);
        }

        try {
            $models = $gemini->listModels($key);
        } catch (ProviderException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Key bisa dipakai. '.count($models).' model tersedia.']);
    }
}
