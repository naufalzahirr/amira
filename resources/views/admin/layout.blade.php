<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>AMIRA Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            margin: 0;
        }
        .topbar {
            background: #020617;
            border-bottom: 1px solid #1f2937;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .topbar h1 {
            font-size: 18px;
            margin: 0;
        }
        .nav a {
            color: #e5e7eb;
            margin-left: 12px;
            font-size: 14px;
            text-decoration: none;
        }
        .nav a:hover {
            text-decoration: underline;
        }
        .container {
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 16px;
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            border-bottom: 1px solid #1f2937;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #030712;
        }
        .btn {
            border-radius: 9999px;
            padding: 6px 12px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-secondary { background: #4b5563; color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        input[type="text"], textarea, select {
            width: 100%;
            background: #020617;
            border-radius: 8px;
            border: 1px solid #374151;
            color: #e5e7eb;
            padding: 6px 8px;
            font-size: 14px;
        }
        textarea { min-height: 150px; }
        label { font-size: 13px; display:block; margin-bottom:4px; }
        .form-group { margin-bottom: 10px; }
        .alert {
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .alert-success { background: #065f46; color: #bbf7d0; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>AMIRA Admin</h1>
    <div class="nav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.system-prompt.edit') }}">System Prompt</a>
        <a href="{{ route('admin.subjects.index') }}">Mapel</a>
        <a href="{{ route('admin.resources.index') }}">Resource File</a>
        <a href="{{ route('amira.chat.index') }}">↩ Kembali ke Chat</a>
    </div>
</div>

<div class="container">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @yield('content')
</div>
</body>
</html>
