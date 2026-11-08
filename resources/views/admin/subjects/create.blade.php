@extends('admin.layout')

@section('content')
    <div class="card">
        <h2>Tambah Mapel</h2>

        <form action="{{ route('admin.subjects.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Nama Mapel</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required>
                @error('name')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="code">Kode (opsional)</label>
                <input type="text" name="code" id="code" value="{{ old('code') }}">
                @error('code')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary" type="submit">Simpan</button>
            <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
@endsection
