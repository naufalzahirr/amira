<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AMIRA') · AMIRA</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Nunito:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/amira.css') }}?v={{ filemtime(public_path('css/amira.css')) }}">
    @stack('head')
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('home') }}" aria-label="AMIRA beranda">
            <img src="{{ asset('img/amira-logo.png') }}" alt="AMIRA">
        </a>
        @auth
            <nav class="nav" aria-label="Menu utama">
                @if (auth()->user()->isTeacher())
                    <a href="{{ route('guru.index') }}" class="{{ request()->routeIs('guru.index', 'guru.kelas.*', 'guru.mentor.*') ? 'on' : '' }}">Kelas</a>
                    <a href="{{ route('guru.keys.edit') }}" class="{{ request()->routeIs('guru.keys.*') ? 'on' : '' }}">Pengaturan AI</a>
                    <a href="{{ route('akun.edit') }}" class="{{ request()->routeIs('akun.*') ? 'on' : '' }}">Akun</a>
                @else
                    <a href="{{ route('belajar.index') }}" class="{{ request()->routeIs('belajar.*', 'chat.*') ? 'on' : '' }}">Belajar</a>
                    <a href="{{ route('akun.edit') }}" class="{{ request()->routeIs('akun.*') ? 'on' : '' }}">Akun</a>
                @endif
            </nav>
            <div class="who">
                <span class="role-chip">{{ auth()->user()->isTeacher() ? 'Guru' : 'Murid' }}</span>
                <span class="name">{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm">Keluar</button></form>
            </div>
        @else
            <div class="grow"></div>
            <a class="btn btn-ghost btn-sm" href="{{ route('login') }}">Masuk</a>
            <a class="btn btn-sm" href="{{ route('register') }}">Daftar</a>
        @endauth
    </div>
</header>

@hasSection('full')
    @yield('full')
@else
    <main class="page @yield('page-class')">
        @include('partials.flash')
        @yield('content')
    </main>
@endif
@stack('scripts')
</body>
</html>
