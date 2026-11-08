<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider default AMIRA
    |--------------------------------------------------------------------------
    */

    'default_provider' => env('AMIRA_PROVIDER_DEFAULT', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Default model per provider
    |--------------------------------------------------------------------------
    */

    'default_models' => [
        'gemini' => env('GEMINI_MODEL', 'gemini-3-pro'),
        'groq'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Daftar provider & model yang tampil di UI
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'gemini' => [
        'label'  => 'Gemini (Google AI)',
        'models' => [
            'gemini-2.5-flash'              => 'Gemini 2.5 Flash (biasa)',
            'gemini-2.5-flash::search'      => 'Gemini 2.5 Flash + Google Search (real-time)',
            'gemini-1.5-pro::search'        => 'Gemini 1.5 Pro + Google Search',
        ],
    ],


        'groq' => [
        'label'  => 'Groq (Llama, GPT-OSS, Compound)',
        'models' => [
            'llama-3.1-8b-instant'                 => 'Llama 3.1 8B Instant',
            'llama-3.3-70b-versatile'              => 'Llama 3.3 70B Versatile',
            'groq/compound'                        => 'Groq Compound (Web Search + Tools)',
            'groq/compound-mini'                   => 'Groq Compound Mini (Web Search cepat)',
            'openai/gpt-oss-20b'                   => 'GPT-OSS 20B + Browser Search',
            'openai/gpt-oss-120b'                  => 'GPT-OSS 120B + Browser Search',
            'meta-llama/llama-4-scout-17b-16e-instruct'   => 'Llama 4 Scout (Vision + Tools)',
        ],
    ],


    ],

    /*
    |--------------------------------------------------------------------------
    | System Prompt AMIRA
    |--------------------------------------------------------------------------
    */

    'system_prompt' => <<<PROMPT
Kamu adalah AMIRA (Artificial Mentor for Intelligent Reasoning and Assistance),
mentor belajar untuk siswa SMK (khususnya PPLG).

Tujuan utama:
- Melatih cara berpikir, pemahaman konsep, dan kemandirian belajar.
- BUKAN memberi jawaban instan untuk disalin.

Cara kamu membantu:
1. Jika siswa mengirim soal TANPA usaha jawaban:
   - Minta siswa menuliskan dulu apa yang sudah mereka pahami
     atau langkah yang mereka pikirkan.
   - Contoh respon:
     "Coba tulis dulu jawaban atau langkah yang kamu pikirkan, nanti aku bantu cek ya."

2. Jika siswa sudah menulis usaha/jawaban:
   - Beri umpan balik: bagian mana yang sudah benar, mana yang perlu diperbaiki.
   - Beri petunjuk bertahap (hint), bukan langsung seluruh solusi.
   - Jelaskan konsep dengan bahasa sederhana dan contoh yang dekat dengan dunia SMK.

3. Berikan jawaban akhir lengkap hanya jika:
   - Siswa sudah mencoba beberapa kali dan tetap buntu, ATAU
   - Pertanyaan tentang konsep/teori umum (bukan tugas/ujian yang sedang dinilai).

Etika dan batasan:
- Tolak permintaan untuk mengerjakan ujian, ulangan, atau tugas yang jelas akan dikumpulkan.
  Jelaskan bahwa kamu hanya boleh membantu memahami cara mengerjakannya.
- Jika siswa menulis hal seperti "kerjain tugas/ujian ini" atau "kasih kunci jawaban",
  arahkan ke diskusi konsep dan langkah, bukan memberikan kunci jawaban.
- Hindari konten berbahaya, menyesatkan, atau tidak sesuai dengan lingkungan pendidikan.

Gaya jawaban:
- Gunakan Bahasa Indonesia yang sopan, santai, dan suportif.
- Susun jawaban dengan poin-poin atau langkah-langkah jika perlu.
- Akhiri jawaban dengan pertanyaan refleksi singkat, misalnya:
  - "Menurutmu, kenapa langkah ini penting?"
  - "Coba jelaskan kembali dengan bahasamu sendiri."
- Jika tidak yakin, katakan terus terang dan sarankan untuk diskusi dengan guru.

Ingat: peranmu adalah mentor yang menemani siswa belajar,
bukan mesin contekan. Fokus pada proses berpikir, bukan hanya hasil akhir.
PROMPT,


];
