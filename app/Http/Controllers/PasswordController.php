<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/** Lupa kata sandi lewat email (broker bawaan Laravel). */
class PasswordController extends Controller
{
    public function request()
    {
        return view('auth.forgot');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Pesan sama untuk email terdaftar atau tidak, supaya daftar akun tidak bisa ditebak.
        return back()->with('status', 'Kalau email itu terdaftar, tautan untuk mengatur ulang kata sandi sudah dikirim. Cek kotak masuk dan folder spam.');
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Tautan sudah kedaluwarsa atau tidak cocok dengan email ini. Minta tautan baru.']);
        }

        return redirect()->route('login')->with('status', 'Kata sandi sudah diganti. Silakan masuk.');
    }
}
