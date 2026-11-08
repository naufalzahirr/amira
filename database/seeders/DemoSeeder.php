<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk mencoba AMIRA di komputer sendiri.
 * Akun:  guru@amira.test / amira-demo   dan   murid@amira.test / amira-demo
 * Tanpa API key: pasang key Gemini di Pengaturan AI agar mentor bisa menjawab.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::updateOrCreate(['email' => 'guru@amira.test'], [
            'name' => 'Pak Naufal', 'password' => 'amira-demo', 'role' => 'guru', 'adult_confirmed_at' => now(),
        ]);
        $student = User::updateOrCreate(['email' => 'murid@amira.test'], [
            'name' => 'Rina Pratiwi', 'password' => 'amira-demo', 'role' => 'murid', 'adult_confirmed_at' => now(),
        ]);

        $classroom = $teacher->taughtClassrooms()->firstOrCreate(['name' => 'Pemrograman Web Dasar'], [
            'description' => 'Membangun landing page pertama dengan HTML, CSS, dan sedikit JavaScript.',
        ]);
        $classroom->students()->syncWithoutDetaching([$student->id]);

        $html = $classroom->mentors()->firstOrCreate(['name' => 'Mentor HTML & CSS'], [
            'tagline' => 'Menemani membangun landing page pertamamu',
            'goal' => 'Peserta mampu menyusun struktur HTML semantik dan menautkan file CSS eksternal untuk landing page sederhana.',
            'instructions' => "Pakai contoh landing page toko roti. Jangan berikan kode utuh satu halaman; maksimal 10 baris contoh.\nKalau peserta bertanya soal JavaScript, arahkan dulu ke Mentor JavaScript.",
            'hint_ladder' => true, 'explain_back' => true, 'color' => 'marigold', 'icon' => 'palette', 'is_active' => true,
            'presets' => config('amira.default_presets'),
        ]);

        $classroom->mentors()->firstOrCreate(['name' => 'Mentor JavaScript'], [
            'tagline' => 'Satu interaksi sederhana untuk halamanmu',
            'goal' => 'Peserta mampu menambahkan satu interaksi sederhana (klik tombol, tampil/sembunyi) dengan JavaScript.',
            'hint_ladder' => true, 'explain_back' => true, 'color' => 'cobalt', 'icon' => 'code', 'is_active' => true,
            'presets' => ['Tombolku tidak bereaksi:', 'Apa beda let dan const?', 'Cek kode JavaScript-ku:'],
        ]);

        if ($student->conversations()->exists()) {
            return;
        }

        $conversation = $student->conversations()->create(['mentor_id' => $html->id, 'title' => 'CSS-ku tidak terbaca di halaman']);
        $conversation->messages()->create([
            'role' => 'user', 'topic' => 'linking file css',
            'content' => "CSS-ku tidak terbaca di halaman. Ini kodenya:\n<link rel=\"stylesheet\" href=\"css/style.css\">\nFile style.css ada di folder yang sama dengan index.html.",
        ]);
        $conversation->messages()->create([
            'role' => 'model', 'topic' => 'linking file css', 'provider' => 'gemini', 'model' => 'contoh', 'key_source' => 'guru',
            'content' => "**Petunjuk 1 dari 3**\n\nKamu sudah benar memakai tag `<link>` di dalam `<head>`. Sekarang coba bandingkan dua hal ini:\n\n- Di mana file `style.css` kamu simpan?\n- Ke mana atribut `href` menunjuk?\n\nMenurutmu, apakah alamat `css/style.css` cocok dengan letak file yang kamu ceritakan?",
        ]);
        $conversation->messages()->create([
            'role' => 'user', 'topic' => 'linking file css',
            'content' => 'Oh, href-nya mencari folder css padahal filenya di luar. Kuganti jadi href="style.css" dan sekarang warnanya muncul!',
        ]);
        $conversation->messages()->create([
            'role' => 'model', 'topic' => 'linking file css', 'provider' => 'gemini', 'model' => 'contoh', 'key_source' => 'guru',
            'content' => "Tepat sekali! Alamat di `href` dibaca **relatif** terhadap letak `index.html`.\n\nSebelum lanjut, coba jelaskan balik dengan kata-katamu sendiri: kalau nanti `style.css` dipindah ke folder `assets/css`, `href`-nya harus ditulis bagaimana, dan kenapa?\n\nJangan lupa tandai bantuan ini di kodemu, misalnya:\n\n```html\n<!-- Dibantu AMIRA: memperbaiki alamat file CSS -->\n<link rel=\"stylesheet\" href=\"style.css\">\n```",
        ]);
    }
}
