<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin AMIRA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            min-height: 100vh;
            background: #0f172a;
            color: #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: stretch;
        }

        .page {
            width: 100%;
            max-width: 1100px;
            margin: 16px;
            background: #020617;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.7);
            border: 1px solid #1f2937;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            padding: 12px 18px;
            background: radial-gradient(circle at top left, #0ea5e9, transparent 55%);
            border-bottom: 1px solid #1f2937;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .topbar-title {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .topbar-title h1 {
            font-size: 18px;
            margin: 0;
        }

        .topbar-title small {
            font-size: 12px;
            color: #d1d5db;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
        }

        .user-pill {
            padding: 4px 10px;
            border-radius: 9999px;
            background: rgba(15,23,42,0.7);
            border: 1px solid #1f2937;
        }

        .logout-btn {
            border-radius: 9999px;
            border: none;
            padding: 5px 12px;
            background: #ef4444;
            color: #f9fafb;
            font-size: 12px;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #b91c1c;
        }

        .content {
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .breadcrumbs {
            font-size: 12px;
            color: #9ca3af;
        }

        .breadcrumbs a {
            color: #93c5fd;
            text-decoration: none;
        }

        .breadcrumbs a:hover {
            text-decoration: underline;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .card {
            background: #020617;
            border-radius: 14px;
            border: 1px solid #1f2937;
            padding: 14px 14px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
        }

        .card-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 9999px;
            background: #0f172a;
            border: 1px solid #1d4ed8;
            color: #bfdbfe;
        }

        .card-body {
            font-size: 12px;
            color: #9ca3af;
        }

        .card-actions {
            margin-top: 8px;
            display: flex;
            justify-content: flex-end;
        }

        .card-link {
            font-size: 12px;
            border-radius: 9999px;
            border: 1px solid #4b5563;
            padding: 5px 12px;
            color: #e5e7eb;
            text-decoration: none;
        }

        .card-link:hover {
            background: #111827;
        }

        .footer {
            padding: 10px 18px 14px;
            border-top: 1px solid #1f2937;
            font-size: 11px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .footer a {
            color: #93c5fd;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .page {
                margin: 0;
                border-radius: 0;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar-right {
                align-self: stretch;
                justify-content: space-between;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <header class="topbar">
        <div class="topbar-title">
            <h1>Dashboard Admin AMIRA</h1>
            <small>Pengaturan sistem prompt, mapel, dan resource PDF</small>
        </div>

        <div class="topbar-right">
            <div class="user-pill">
                👤 {{ auth()->user()->name ?? 'Admin' }}
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="logout-btn">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="content">
        <div class="breadcrumbs">
            <a href="{{ route('amira.chat.index') }}">← Kembali ke AMIRA Web (siswa)</a>
        </div>

        <section class="cards">
            {{-- Kartu System Prompt --}}
            <article class="card">
                <div class="card-header">
                    <div class="card-title">System Prompt AMIRA</div>
                    <div class="card-badge">Peran & Gaya Jawaban</div>
                </div>
                <div class="card-body">
                    Atur cara bicara AMIRA, batasan etika, dan fokus pembelajaran.
                    Perubahan di sini akan mempengaruhi semua model (Gemini &amp; Groq).
                </div>
                <div class="card-actions">
                    <a href="{{ route('admin.system-prompt.edit') }}" class="card-link">
                        Kelola System Prompt
                    </a>
                </div>
            </article>

            {{-- Kartu Mapel --}}
            <article class="card">
                <div class="card-header">
                    <div class="card-title">Daftar Mapel</div>
                    <div class="card-badge">Klasifikasi</div>
                </div>
                <div class="card-body">
                    Tambah, ubah, dan hapus mata pelajaran yang digunakan untuk
                    mengelompokkan resource dan riwayat chat siswa di AMIRA.
                </div>
                <div class="card-actions">
                    <a href="{{ route('admin.subjects.index') }}" class="card-link">
                        Kelola Mapel
                    </a>
                </div>
            </article>

            {{-- Kartu Resource PDF --}}
            <article class="card">
                <div class="card-header">
                    <div class="card-title">Resource / File PDF</div>
                    <div class="card-badge">Bahan Ajar</div>
                </div>
                <div class="card-body">
                    Unggah file PDF atau dokumen lain, lalu hubungkan dengan mapel tertentu.
                    Siswa dapat memilih resource ini sebagai konteks ketika bertanya ke AMIRA.
                </div>
                <div class="card-actions">
                    <a href="{{ route('admin.resources.index') }}" class="card-link">
                        Kelola Resource
                    </a>
                </div>
            </article>
        </section>
    </main>

    <footer class="footer">
        <div>
            AMIRA (Artificial Mentor for Intelligent Reasoning and Assistance) &mdash; Panel Admin
        </div>
        <div>
            SMKN 4 Tanjungpinang
        </div>
    </footer>
</div>
</body>
</html>
