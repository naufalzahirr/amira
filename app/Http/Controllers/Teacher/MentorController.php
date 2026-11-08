<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Mentor;
use App\Models\MentorMaterial;
use App\Services\MaterialExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MentorController extends Controller
{
    public function create(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($request, $classroom);

        $template = config('amira_mentor.templates.'.$request->query('template'));

        $mentor = new Mentor(array_merge([
            'hint_ladder' => true,
            'explain_back' => true,
            'is_active' => true,
            'color' => Mentor::COLORS[$classroom->mentors()->count() % count(Mentor::COLORS)],
            'icon' => 'code',
            'presets' => config('amira.default_presets'),
        ], $template ? collect($template)->except(['title', 'summary'])->all() : []));

        return view('teacher.mentor-form', [
            'classroom' => $classroom,
            'mentor' => $mentor,
            'templateKey' => $template ? $request->query('template') : null,
        ]);
    }

    public function store(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($request, $classroom);
        $mentor = $classroom->mentors()->create($this->validated($request));

        return redirect()->route('guru.mentor.edit', $mentor)
            ->with('status', 'Mentor dibuat. Tambahkan materi bila perlu, lalu coba sebagai murid.');
    }

    public function edit(Request $request, Mentor $mentor)
    {
        $this->authorizeClassroom($request, $mentor->classroom);
        $classroom = $mentor->classroom;
        $mentor->load('materials');

        return view('teacher.mentor-form', ['classroom' => $classroom, 'mentor' => $mentor, 'templateKey' => null]);
    }

    public function update(Request $request, Mentor $mentor)
    {
        $this->authorizeClassroom($request, $mentor->classroom);
        $mentor->update($this->validated($request));

        return back()->with('status', 'Mentor tersimpan.');
    }

    public function destroy(Request $request, Mentor $mentor)
    {
        $classroom = $mentor->classroom;
        $this->authorizeClassroom($request, $classroom);

        foreach ($mentor->materials as $material) {
            Storage::delete($material->path);
        }
        $mentor->delete();

        return redirect()->route('guru.kelas.show', $classroom)->with('status', 'Mentor dihapus.');
    }

    public function storeMaterial(Request $request, Mentor $mentor, MaterialExtractor $extractor)
    {
        $this->authorizeClassroom($request, $mentor->classroom);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:'.MaterialExtractor::MIMES, 'max:20480'],
        ], [
            'file.mimes' => 'Materi harus berupa PDF, TXT, atau MD.',
        ]);

        $file = $request->file('file');
        $text = $extractor->extract($file);

        if ($text === null) {
            return back()->withErrors(['file' => 'Teks di file ini tidak terbaca. Kalau PDF hasil scan, unggah versi yang teksnya bisa disalin.']);
        }

        $mentor->materials()->create([
            'title' => $data['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $file->getClientOriginalName(),
            'path' => $file->store('materi'),
            'extracted_text' => $text,
            'char_count' => mb_strlen($text),
        ]);

        return back()->with('status', 'Materi ditambahkan.');
    }

    public function destroyMaterial(Request $request, MentorMaterial $material)
    {
        $this->authorizeClassroom($request, $material->mentor->classroom);
        Storage::delete($material->path);
        $material->delete();

        return back()->with('status', 'Materi dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'goal' => ['nullable', 'string', 'max:3000'],
            'instructions' => ['nullable', 'string', 'max:6000'],
            'presets' => ['nullable', 'string', 'max:3000'],
            'color' => ['required', 'in:'.implode(',', Mentor::COLORS)],
            'icon' => ['nullable', 'in:'.implode(',', array_keys(config('amira_mentor.icons')))],
        ]);

        $data['presets'] = collect(preg_split('/\R/', $data['presets'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->take(8)
            ->values()
            ->all();
        $data['hint_ladder'] = $request->boolean('hint_ladder');
        $data['explain_back'] = $request->boolean('explain_back');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function authorizeClassroom(Request $request, Classroom $classroom): void
    {
        abort_unless($classroom->teacher_id === $request->user()->id, 403);
    }
}
