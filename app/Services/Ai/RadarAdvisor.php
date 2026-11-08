<?php

namespace App\Services\Ai;

use App\Models\Classroom;

/**
 * Menyusun usulan mini-lesson dari ringkasan Radar. Yang dikirim ke AI hanya angka per topik
 * dan tujuan mentor — tanpa nama murid dan tanpa isi percakapan.
 */
class RadarAdvisor
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly GroqClient $groq,
    ) {}

    /** @throws ProviderException */
    public function suggest(Classroom $classroom, array $radar): string
    {
        $teacher = $classroom->teacher;
        $errors = [];

        $attempts = array_filter([
            $teacher->gemini_key ? [$this->gemini, $teacher->gemini_key, $classroom->gemini_model] : null,
            $teacher->groq_key ? [$this->groq, $teacher->groq_key, $classroom->groq_model] : null,
        ]);

        foreach ($attempts as [$client, $key, $model]) {
            try {
                $handle = $client->stream($key, $model, $this->system(), [['role' => 'user', 'content' => $this->brief($classroom, $radar)]]);
                $text = '';
                foreach ($handle->text() as $piece) {
                    $text .= $piece;
                }
                if (trim($text) !== '') {
                    return trim($text);
                }
            } catch (ProviderException $e) {
                $errors[] = $e->getMessage();
            }
        }

        throw new ProviderException($errors ? implode(' ', array_unique($errors)) : 'Belum ada API key.');
    }

    private function system(): string
    {
        return <<<'PROMPT'
Kamu membantu guru merencanakan mini-lesson 10–15 menit untuk pertemuan berikutnya, berdasarkan ringkasan kesulitan kelas.
Tulis dalam Bahasa Indonesia, format Markdown, ringkas dan praktis:
- Pilih 2 topik paling mendesak. Utamakan topik yang sering sampai petunjuk 3, sering belum dipahami saat jelaskan balik, atau jarang selesai.
- Untuk tiap topik: judul "### ...", satu kalimat alasan berdasarkan angka, lalu rencana 3 langkah (pembuka, demo atau contoh, latihan singkat) dan satu pertanyaan cek pemahaman.
- Jangan mengarang data di luar ringkasan. Bila data sedikit, katakan dan beri usulan yang aman.
PROMPT;
    }

    private function brief(Classroom $classroom, array $radar): string
    {
        $lines = ["Kelas: {$classroom->name}"];

        foreach ($classroom->mentors()->where('is_active', true)->get() as $mentor) {
            $lines[] = "Mentor \"{$mentor->name}\": ".($mentor->goal ?: 'tanpa tujuan tertulis');
        }

        $lines[] = "\nRingkasan ".\App\Services\RadarKelas::DAYS." hari: {$radar['week']} pertanyaan dari {$radar['active']} murid, "
            ."{$radar['solved']} masalah selesai, {$radar['hint3']} kali sampai petunjuk 3, "
            ."jelaskan balik: {$radar['understood_yes']} paham / {$radar['understood_no']} belum.";
        $lines[] = "\nPer topik (total | sampai petunjuk 3 | selesai | paham | belum paham):";

        foreach ($radar['topics'] as $t) {
            $lines[] = "- {$t->topic}: {$t->total} | {$t->hint3} | {$t->solved} | {$t->understood_yes} | {$t->understood_no}";
        }

        return implode("\n", $lines);
    }
}
