<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\ProviderException;
use App\Services\Ai\RadarAdvisor;
use App\Services\RadarKelas;
use App\Support\Qr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $classrooms = $user->taughtClassrooms()->withCount(['students', 'mentors'])->latest()->get();

        $steps = [
            ['done' => (bool) $user->gemini_key, 'title' => 'Pasang API key Gemini', 'hint' => 'Gratis dari Google AI Studio. Key Groq sebagai cadangan bisa menyusul.', 'url' => route('guru.keys.edit')],
            ['done' => $classrooms->isNotEmpty(), 'title' => 'Buat kelas', 'hint' => 'Isi formulir "Buat kelas baru" di bawah.', 'url' => '#buat-kelas'],
            ['done' => $classrooms->sum('mentors_count') > 0, 'title' => 'Buat mentor pertama', 'hint' => 'Satu mentor untuk satu topik, lalu coba sebagai murid.', 'url' => $classrooms->first() ? route('guru.mentor.create', $classrooms->first()) : '#buat-kelas'],
            ['done' => $classrooms->sum('students_count') > 0, 'title' => 'Bagikan tautan kelas ke murid', 'hint' => 'Kirim tautan ke grup kelas atau tampilkan QR di proyektor.', 'url' => $classrooms->first() ? route('guru.kelas.show', $classrooms->first()) : '#buat-kelas'],
        ];

        return view('teacher.index', [
            'classrooms' => $classrooms,
            'usage' => $this->teacherUsageToday($user),
            'steps' => $steps,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $teacher = $request->user();
        $classroom = $teacher->taughtClassrooms()->make($data);
        $classroom->gemini_model = Classroom::defaultGeminiModel($teacher->gemini_models);
        $classroom->groq_model = Classroom::defaultGroqModel($teacher->groq_models);
        $classroom->save();

        return redirect()->route('guru.kelas.show', $classroom)
            ->with('status', "Kelas dibuat. Bagikan kode {$classroom->join_code} ke murid.");
    }

    public function show(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($request, $classroom);

        $tab = in_array($request->query('tab'), ['mentor', 'radar', 'murid', 'pengaturan'], true)
            ? $request->query('tab') : 'mentor';

        $mentors = $classroom->mentors()->withCount('materials')->orderBy('name')->get();
        $students = $classroom->students()->orderBy('name')->get();

        return view('teacher.classroom', [
            'classroom' => $classroom,
            'tab' => $tab,
            'mentors' => $mentors,
            'students' => $students,
            'radar' => app(RadarKelas::class)->compute($classroom),
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($request, $classroom);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'gemini_model' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9.\-]+$/'],
            'groq_model' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9.\-\/_]+$/'],
            'daily_limit_per_student' => ['required', 'integer', 'min:1', 'max:500'],
            'rpm_limit' => ['required', 'integer', 'min:1', 'max:1000'],
        ], [
            'gemini_model.regex' => 'Nama model hanya huruf kecil, angka, titik, dan strip.',
        ]);

        $data['allow_student_keys'] = $request->boolean('allow_student_keys');
        $data['use_groq_fallback'] = $request->boolean('use_groq_fallback');

        $classroom->update($data);

        return redirect()->route('guru.kelas.show', [$classroom, 'tab' => 'pengaturan'])->with('status', 'Pengaturan kelas tersimpan.');
    }

    /** Halaman QR layar penuh untuk ditampilkan di proyektor. */
    public function qr(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($request, $classroom);
        $url = route('gabung', $classroom->join_code);

        return view('teacher.qr', [
            'classroom' => $classroom,
            'url' => $url,
            'svg' => Qr::svg($url, 520),
        ]);
    }

    public function regenerateCode(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($request, $classroom);
        $classroom->join_code = Classroom::freshJoinCode();
        $classroom->save();

        return back()->with('status', "Kode baru: {$classroom->join_code}. Kode lama tidak berlaku lagi.");
    }

    public function removeStudent(Request $request, Classroom $classroom, User $user)
    {
        $this->authorizeClassroom($request, $classroom);
        $classroom->students()->detach($user->id);

        return back()->with('status', "{$user->name} dikeluarkan dari kelas.");
    }

    /** Usulan mini-lesson dari ringkasan Radar (tanpa nama murid dan isi chat). */
    public function suggest(Request $request, Classroom $classroom, RadarKelas $radar, RadarAdvisor $advisor)
    {
        $this->authorizeClassroom($request, $classroom);
        $data = $radar->compute($classroom);
        $back = redirect()->route('guru.kelas.show', [$classroom, 'tab' => 'radar']);

        if ($data['topics']->isEmpty()) {
            return $back->with('status', 'Belum ada topik di Radar 7 hari terakhir. Usulan bisa disusun setelah murid mulai bertanya.');
        }

        try {
            $text = $advisor->suggest($classroom, $data);
        } catch (ProviderException $e) {
            return $back->with('status', 'Usulan belum bisa disusun: '.$e->getMessage());
        }

        $classroom->update(['radar_suggestion' => $text, 'radar_suggestion_at' => now()]);

        return $back->with('status', 'Usulan mini-lesson sudah disusun.');
    }

    /** Pemakaian key guru hari ini di semua kelasnya (kuota Gemini dihitung per project, bukan per kelas). */
    private function teacherUsageToday(User $teacher): array
    {
        $rows = Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->join('mentors', 'mentors.id', '=', 'conversations.mentor_id')
            ->join('classrooms', 'classrooms.id', '=', 'mentors.classroom_id')
            ->where('classrooms.teacher_id', $teacher->id)
            ->where('messages.role', 'model')
            ->where('messages.created_at', '>=', now()->startOfDay())
            ->select('messages.provider', 'messages.key_source', DB::raw('count(*) as total'),
                DB::raw('sum(coalesce(messages.prompt_tokens,0) + coalesce(messages.output_tokens,0)) as tokens'))
            ->groupBy('messages.provider', 'messages.key_source')
            ->get();

        $pick = fn ($provider, $source) => $rows->first(fn ($r) => $r->provider === $provider && $r->key_source === $source);

        return [
            'gemini' => (int) ($pick('gemini', 'guru')->total ?? 0),
            'groq' => (int) ($pick('groq', 'guru')->total ?? 0),
            'student' => (int) $rows->where('key_source', 'murid')->sum('total'),
            'tokens' => (int) $rows->where('key_source', 'guru')->sum('tokens'),
        ];
    }

    private function authorizeClassroom(Request $request, Classroom $classroom): void
    {
        abort_unless($classroom->teacher_id === $request->user()->id, 403);
    }
}
