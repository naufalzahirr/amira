<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\Ai\MentorChat;
use Illuminate\Http\Request;

class LearnController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isTeacher()) {
            return redirect()->route('guru.index');
        }

        $classrooms = $user->classrooms()->with('teacher')->withCount(['mentors' => fn ($q) => $q->where('is_active', true)])->get();

        return view('learn.index', compact('classrooms'));
    }

    public function join(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:12']]);
        $code = strtoupper(preg_replace('/\s+/', '', $data['code']));
        $classroom = Classroom::where('join_code', $code)->first();

        if (! $classroom) {
            return back()->withErrors(['code' => 'Kode kelas tidak ditemukan. Cek lagi ke guru.'])->withInput();
        }

        if ($request->user()->isTeacher()) {
            return back()->withErrors(['code' => 'Akun guru tidak bisa bergabung sebagai murid.']);
        }

        $classroom->students()->syncWithoutDetaching([$request->user()->id]);

        return redirect()->route('belajar.kelas', $classroom)->with('status', "Kamu sudah bergabung di {$classroom->name}.");
    }

    public function classroom(Request $request, Classroom $classroom, MentorChat $chat)
    {
        $user = $request->user();
        abort_unless($classroom->hasMember($user), 403);

        $mentors = $classroom->mentors()->where('is_active', true)->withCount('materials')->orderBy('name')->get();
        $used = $user->isTeacher() ? null : $chat->usedToday($user, $classroom);

        return view('learn.classroom', compact('classroom', 'mentors', 'used'));
    }
}
