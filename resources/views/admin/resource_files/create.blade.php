@extends('admin.layout')

@section('content')
    <div class="card">
        <h2>Tambah Resource File</h2>

        <form action="{{ route('admin.resources.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="title">Judul</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required>
                @error('title')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Deskripsi (opsional)</label>
                <textarea name="description" id="description">{{ old('description') }}</textarea>
                @error('description')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="file">File (PDF/dll)</label>
                <input type="file" name="file" id="file" required>
                @error('file')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Mapel yang Terkait (boleh lebih dari satu)</label>
                <select name="subjects[]" multiple size="5">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
                @error('subjects')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary" type="submit">Simpan</button>
            <a href="{{ route('admin.resources.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
@endsection
