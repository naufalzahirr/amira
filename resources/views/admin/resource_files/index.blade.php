@extends('admin.layout')

@section('content')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2>Daftar Resource File</h2>
            <a href="{{ route('admin.resources.create') }}" class="btn btn-primary btn-sm">+ Tambah Resource</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Judul</th>
                <th>Mapel</th>
                <th>File</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($resources as $resource)
                <tr>
                    <td>{{ $resource->id }}</td>
                    <td>{{ $resource->title }}</td>
                    <td>
                        @foreach ($resource->subjects as $sub)
                            <span>{{ $sub->name }}</span>@if(!$loop->last), @endif
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ asset('storage/'.$resource->file_path) }}" target="_blank">Lihat</a>
                    </td>
                    <td>
                        <a href="{{ route('admin.resources.edit', $resource) }}" class="btn btn-secondary btn-sm">Edit</a>

                        <form action="{{ route('admin.resources.destroy', $resource) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus resource ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada resource.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
