<?php

/*
| Ikon dan template mentor. Ikon adalah isi <svg viewBox="0 0 24 24"> bergaya garis.
*/
return [

    'icons' => [
        'code' => ['Kode', '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>'],
        'bug' => ['Debug', '<rect x="8" y="6" width="8" height="14" rx="4"/><path d="M12 6V3M10 3h4M4 13h4M16 13h4M5 7.5l3 2M19 7.5l-3 2M5 19l3-2M19 19l-3-2"/>'],
        'lightbulb' => ['Konsep', '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.6 10.8c.7.5 1.1 1.3 1.1 2.1V16h5v-.1c0-.8.4-1.6 1.1-2.1A6 6 0 0 0 12 3z"/>'],
        'pencil' => ['Latihan', '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13 7 4 4"/>'],
        'checklist' => ['Periksa', '<path d="M10 6h10M10 12h10M10 18h10"/><path d="m3.5 6 1.5 1.5L7.5 5M3.5 12l1.5 1.5 2.5-2.5M3.5 18l1.5 1.5 2.5-2.5"/>'],
        'database' => ['Data', '<ellipse cx="12" cy="5.5" rx="7.5" ry="2.8"/><path d="M4.5 5.5v13c0 1.5 3.4 2.8 7.5 2.8s7.5-1.3 7.5-2.8v-13M4.5 12c0 1.5 3.4 2.8 7.5 2.8s7.5-1.3 7.5-2.8"/>'],
        'palette' => ['Desain', '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.8-.8 1.8-1.8 0-.5-.2-.9-.5-1.2-.3-.3-.5-.8-.5-1.2 0-1 .8-1.8 1.8-1.8H17a4 4 0 0 0 4-4C21 6.5 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7" r="1"/>'],
        'calculator' => ['Hitung', '<rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M8.5 7h7M8.5 12h.01M12 12h.01M15.5 12h.01M8.5 16h.01M12 16h.01M15.5 16h.01"/>'],
        'book' => ['Bacaan', '<path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H20v15H5.5A1.5 1.5 0 0 0 4 19.5z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H20"/>'],
        'globe' => ['Bahasa', '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/>'],
        'flask' => ['Sains', '<path d="M9 3h6M10 3v6.5L4.7 18.6A1.6 1.6 0 0 0 6.1 21h11.8a1.6 1.6 0 0 0 1.4-2.4L14 9.5V3"/><path d="M7.4 15h9.2"/>'],
        'chat' => ['Diskusi', '<path d="M20.5 12a8.5 8.5 0 0 1-12.3 7.6L3.5 21l1.4-4.5A8.5 8.5 0 1 1 20.5 12z"/>'],
    ],

    /*
    | Template mentor siap pakai. Guru memilih satu, lalu menyesuaikan isinya.
    */
    'templates' => [
        'debugging' => [
            'title' => 'Pelatih Debugging',
            'summary' => 'Menuntun murid membaca pesan error dan menemukan sendiri sumber masalahnya.',
            'name' => 'Pelatih Debugging',
            'tagline' => 'Teman membaca pesan error dan melacak sumber masalah',
            'icon' => 'bug',
            'color' => 'coral',
            'goal' => 'Peserta mampu membaca pesan error, menemukan baris atau bagian penyebabnya, dan menguji perbaikan kecil secara mandiri.',
            'instructions' => "Mulai dengan meminta peserta menempelkan pesan error lengkap dan potongan kode terkait (atau screenshot).\nAjarkan pola: baca jenis error → cari nama file dan nomor baris → tebak penyebab → uji satu perubahan kecil.\nJangan menulis ulang kode peserta. Setelah berhasil, tanyakan apa yang akan mereka cek lebih dulu bila error serupa muncul lagi.",
            'presets' => ['Ini pesan error-nya:', 'Kodeku jalan tapi hasilnya salah:', 'Aku sudah coba ini, tapi masih error:', 'Bagaimana cara membaca pesan error ini?'],
            'hint_ladder' => true,
            'explain_back' => true,
        ],
        'konsep' => [
            'title' => 'Penjelas Konsep',
            'summary' => 'Menjelaskan konsep dengan analogi sehari-hari, lalu mengecek pemahaman.',
            'name' => 'Penjelas Konsep',
            'tagline' => 'Konsep sulit jadi masuk akal, pakai contoh sehari-hari',
            'icon' => 'lightbulb',
            'color' => 'marigold',
            'goal' => 'Peserta memahami konsep inti materi dan mampu menjelaskannya dengan kata-kata sendiri.',
            'instructions' => "Tanyakan dulu apa yang sudah peserta ketahui tentang konsep itu.\nJelaskan bertahap: analogi sehari-hari → definisi singkat → contoh kecil yang relevan dengan materi.\nAkhiri dengan satu pertanyaan cek pemahaman atau minta peserta menjelaskan balik.",
            'presets' => ['Jelaskan konsep ini dengan contoh sederhana:', 'Apa bedanya ... dan ...?', 'Kenapa ... itu penting?', 'Aku coba jelaskan, apakah sudah benar?'],
            'hint_ladder' => false,
            'explain_back' => true,
        ],
        'latihan' => [
            'title' => 'Teman Latihan Soal',
            'summary' => 'Memberi soal latihan bertingkat dan umpan balik, tanpa membocorkan jawaban.',
            'name' => 'Teman Latihan',
            'tagline' => 'Latihan soal bertingkat, dari mudah ke menantang',
            'icon' => 'pencil',
            'color' => 'teal',
            'goal' => 'Peserta berlatih menerapkan materi lewat soal bertingkat dan belajar dari kesalahannya.',
            'instructions' => "Beri satu soal latihan sekaligus, mulai dari tingkat mudah. Naikkan kesulitan setelah peserta menjawab benar dua kali berturut-turut.\nJangan beri kunci jawaban sebelum peserta mencoba. Saat jawaban keliru, tunjukkan bagian yang perlu dipikirkan ulang.\nSoal harus berbeda dari tugas atau ujian yang sedang dinilai.",
            'presets' => ['Beri aku satu soal latihan', 'Soal yang lebih sulit, dong', 'Ini jawabanku:', 'Aku salah di mana?'],
            'hint_ladder' => true,
            'explain_back' => false,
        ],
        'review' => [
            'title' => 'Pemeriksa Kode',
            'summary' => 'Memberi umpan balik pada kode murid: kerapian, penamaan, dan praktik yang baik.',
            'name' => 'Pemeriksa Kode',
            'tagline' => 'Umpan balik jujur untuk kode yang sudah jalan',
            'icon' => 'checklist',
            'color' => 'cobalt',
            'goal' => 'Peserta mampu merapikan kode yang sudah berjalan: struktur, penamaan, keterbacaan, dan praktik yang baik.',
            'instructions' => "Minta peserta menempelkan kode yang SUDAH berjalan beserta tujuannya.\nBeri maksimal 3 masukan terpenting, urut dari yang paling berdampak. Untuk tiap masukan: apa masalahnya dan kenapa penting, tanpa menulis ulang seluruh kode.\nSebutkan juga satu hal yang sudah baik.",
            'presets' => ['Tolong periksa kodeku:', 'Apakah penamaan variabelku sudah baik?', 'Bagaimana supaya kodeku lebih rapi?', 'Sudah kuperbaiki, cek lagi:'],
            'hint_ladder' => false,
            'explain_back' => false,
        ],
    ],
];
