<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('name')->get();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('admin.subjects.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        Subject::create($data);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Mapel berhasil ditambahkan.');
    }

    public function edit(Subject $subject)
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        $subject->update($data);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Mapel berhasil diperbarui.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Mapel berhasil dihapus.');
    }
}
