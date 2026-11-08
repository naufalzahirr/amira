<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR {{ $classroom->name }} · AMIRA</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Nunito:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/amira.css') }}?v={{ filemtime(public_path('css/amira.css')) }}">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; }
        .qr-wrap { text-align: center; max-width: 900px; width: 100%; }
        .qr-box { background: #fff; display: inline-block; padding: 18px; border-radius: 24px; box-shadow: var(--shadow); }
        .qr-box svg { width: min(62vh, 80vw); height: auto; display: block; }
        .qr-code { font: 800 clamp(2rem, 6vw, 3.4rem) var(--font-code); letter-spacing: .25em; margin: 18px 0 4px; }
        .qr-url { font-size: clamp(1rem, 2.4vw, 1.4rem); color: var(--ink-2); word-break: break-all; }
    </style>
</head>
<body>
<div class="qr-wrap">
    <img src="{{ asset('img/amira-logo.png') }}" alt="AMIRA" style="height:40px">
    <h1 style="margin:14px 0 18px">{{ $classroom->name }}</h1>
    <div class="qr-box">{!! $svg !!}</div>
    <div class="qr-code">{{ $classroom->join_code }}</div>
    <div class="qr-url">Pindai QR, atau buka <b>{{ $url }}</b></div>
    <p style="margin-top:22px"><a href="{{ route('guru.kelas.show', $classroom) }}">← Kembali ke kelas</a></p>
</div>
</body>
</html>
