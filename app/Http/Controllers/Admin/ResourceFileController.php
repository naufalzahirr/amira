<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResourceFile;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResourceFileController extends Controller
{
    public function index()
    {
        $resources = ResourceFile::with('subjects')->latest()->get();

        return view('admin.resource_files.index', compact('resources'));
    }

    public function create()
    {
        $subjects = Subject::orderBy('name')->get();

        return view('admin.resource_files.create', compact('subjects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'file'        => 'required|file|mimes:pdf,doc,docx,ppt,pptx,txt|max:20480',
            'subjects'    => 'nullable|array',
            'subjects.*'  => 'exists:subjects,id',
        ]);

        $file = $request->file('file');

        $path = $file->store('amira_resources', 'public');

        $resource = ResourceFile::create([
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getClientMimeType(),
            'size'          => $file->getSize(),
        ]);

        $resource->subjects()->sync($data['subjects'] ?? []);

        return redirect()->route('admin.resources.index')
            ->with('success', 'File resource berhasil ditambahkan.');
    }

    public function edit(ResourceFile $resource)
    {
        $subjects = Subject::orderBy('name')->get();
        $selected = $resource->subjects->pluck('id')->all();

        return view('admin.resource_files.edit', compact('resource', 'subjects', 'selected'));
    }

    public function update(Request $request, ResourceFile $resource)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'file'        => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt|max:20480',
            'subjects'    => 'nullable|array',
            'subjects.*'  => 'exists:subjects,id',
        ]);

        if ($request->hasFile('file')) {
            // hapus file lama
            if ($resource->file_path && Storage::disk('public')->exists($resource->file_path)) {
                Storage::disk('public')->delete($resource->file_path);
            }

            $file = $request->file('file');
            $path = $file->store('amira_resources', 'public');

            $resource->update([
                'title'         => $data['title'],
                'description'   => $data['description'] ?? null,
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        } else {
            $resource->update([
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
            ]);
        }

        $resource->subjects()->sync($data['subjects'] ?? []);

        return redirect()->route('admin.resources.index')
            ->with('success', 'File resource berhasil diperbarui.');
    }

    public function destroy(ResourceFile $resource)
    {
        if ($resource->file_path && Storage::disk('public')->exists($resource->file_path)) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $resource->subjects()->detach();
        $resource->delete();

        return redirect()->route('admin.resources.index')
            ->with('success', 'File resource berhasil dihapus.');
    }
}
