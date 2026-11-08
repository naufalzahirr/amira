<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;

/** Tautan gabung kelas: /gabung/KODE — bisa dibagikan lewat WhatsApp atau QR. */
class JoinController extends Controller
{
    public function show(Request $request, string $code)
    {
        $classroom = Classroom::with('teacher')->where('join_code', strtoupper($code))->first();

        if (! $classroom) {
            return response()->view('join', ['classroom' => null], 404);
        }

        $user = $request->user();

        if (! $user) {
            // Setelah daftar atau masuk, murid kembali ke tautan ini dan langsung bergabung.
            $request->session()->put('url.intended', $request->fullUrl());

            return view('join', compact('classroom'));
        }

        if ($user->isTeacher()) {
            return $classroom->teacher_id === $user->id
                ? redirect()->route('guru.kelas.show', $classroom)
                : redirect()->route('guru.index')->with('status', 'Tautan itu untuk murid. Akun guru tidak bisa bergabung sebagai murid.');
        }

        $classroom->students()->syncWithoutDetaching([$user->id]);

        return redirect()->route('belajar.kelas', $classroom)->with('status', "Kamu sudah bergabung di {$classroom->name}.");
    }
}
