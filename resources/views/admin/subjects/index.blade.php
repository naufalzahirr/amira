@extends('admin.layout')

@section('content')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2>Daftar Mapel</h2>
            <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary btn-sm">+ Tambah Mapel</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nama Mapel</th>
                <th>Kode</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($subjects as $subject)
                <tr>
                    <td>{{ $subject->id }}</td>
                    <td>{{ $subject->name }}</td>
                    <td>{{ $subject->code }}</td>
                    <td>
                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-secondary btn-sm">Edit</a>

                        <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus mapel ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada mapel.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
