@extends('admin.layout')

@section('content')
    <div class="card">
        <h2>Edit Resource File</h2>

        <form action="{{ route('admin.resources.update', $resource) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title">Judul</label>
                <input type="text" name="title" id="title" value="{{ old('title', $resource->title) }}" required>
                @error('title')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Deskripsi (opsional)</label>
                <textarea name="description" id="description">{{ old('description', $resource->description) }}</textarea>
                @error('description')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>File saat ini</label>
                <p>
                    <a href="{{ asset('storage/'.$resource->file_path) }}" target="_blank">
                        {{ $resource->original_name }}
                    </a>
                </p>
            </div>

            <div class="form-group">
                <label for="file">Ganti File (opsional)</label>
                <input type="file" name="file" id="file">
                @error('file')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Mapel yang Terkait</label>
                <select name="subjects[]" multiple size="5">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}"
                            @if(in_array($subject->id, $selected)) selected @endif>
                            {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button class="btn btn-primary" type="submit">Update</button>
            <a href="{{ route('admin.resources.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
@endsection
