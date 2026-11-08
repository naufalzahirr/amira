@extends('admin.layout')

@section('content')
    <div class="card">
        <h2>System Prompt AMIRA</h2>

        <form action="{{ route('admin.system-prompt.update') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="system_prompt">Teks System Prompt</label>
                <textarea name="system_prompt" id="system_prompt" required>{{ old('system_prompt', $setting->system_prompt) }}</textarea>
                @error('system_prompt')
                    <div style="color:#fecaca;font-size:12px;">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary" type="submit">Simpan</button>
        </form>
    </div>
@endsection
