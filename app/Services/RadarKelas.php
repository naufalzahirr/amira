<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Radar Kelas: ringkasan 7 hari tanpa isi percakapan.
 * Sumbernya hanya label pada jawaban mentor: topik, tingkat petunjuk, selesai, dan hasil "jelaskan balik".
 */
class RadarKelas
{
    public const DAYS = 7;

    public function compute(Classroom $classroom): array
    {
        $today = now()->startOfDay()->toDateTimeString();

        $topics = $this->answers($classroom)
            ->whereNotNull('messages.topic')
            ->selectRaw('messages.topic,
                count(*) as total,
                sum(case when messages.hint_level = 3 then 1 else 0 end) as hint3,
                sum(case when messages.solved then 1 else 0 end) as solved,
                sum(case when messages.understood = 1 then 1 else 0 end) as understood_yes,
                sum(case when messages.understood = 0 then 1 else 0 end) as understood_no')
            ->groupBy('messages.topic')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $perStudent = $this->answers($classroom)
            ->selectRaw('conversations.user_id,
                count(*) as week_total,
                sum(case when messages.created_at >= ? then 1 else 0 end) as today_total,
                max(messages.created_at) as last_at,
                sum(case when messages.hint_level = 3 then 1 else 0 end) as hint3,
                sum(case when messages.solved then 1 else 0 end) as solved,
                sum(case when messages.understood = 1 then 1 else 0 end) as understood_yes,
                sum(case when messages.understood = 0 then 1 else 0 end) as understood_no', [$today])
            ->groupBy('conversations.user_id')
            ->get()
            ->keyBy('user_id');

        $perMentor = $this->answers($classroom)
            ->selectRaw('mentors.name, count(*) as total')
            ->groupBy('mentors.name')
            ->orderByDesc('total')
            ->get();

        return [
            'today' => (int) $perStudent->sum('today_total'),
            'week' => (int) $perStudent->sum('week_total'),
            'active' => $perStudent->count(),
            'solved' => (int) $perStudent->sum('solved'),
            'hint3' => (int) $perStudent->sum('hint3'),
            'understood_yes' => (int) $perStudent->sum('understood_yes'),
            'understood_no' => (int) $perStudent->sum('understood_no'),
            'topics' => $topics,
            'perStudent' => $perStudent,
            'perMentor' => $perMentor,
            'attention' => $this->attention($classroom, $perStudent),
        ];
    }

    /** Murid yang perlu disapa guru, dengan alasannya. */
    private function attention(Classroom $classroom, Collection $perStudent): array
    {
        $names = $classroom->students()->pluck('name', 'users.id');
        $list = [];

        foreach ($perStudent as $userId => $s) {
            $reasons = [];

            if ($s->hint3 >= 2) {
                $reasons[] = "{$s->hint3}× sampai petunjuk 3";
            }
            if ($s->understood_no > 0 && $s->understood_no >= $s->understood_yes) {
                $reasons[] = "penjelasan balik {$s->understood_no}× belum tepat";
            }
            if ($s->week_total >= 8 && $s->solved == 0) {
                $reasons[] = "{$s->week_total} pertanyaan, belum ada yang selesai";
            }

            if ($reasons && isset($names[$userId])) {
                $list[] = ['name' => $names[$userId], 'reasons' => $reasons, 'weight' => $s->hint3 + $s->understood_no];
            }
        }

        usort($list, fn ($a, $b) => $b['weight'] <=> $a['weight']);

        return $list;
    }

    /** Jawaban mentor untuk murid (bukan percakapan uji guru) di kelas ini, 7 hari terakhir. */
    private function answers(Classroom $classroom): Builder
    {
        return Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->join('mentors', 'mentors.id', '=', 'conversations.mentor_id')
            ->where('mentors.classroom_id', $classroom->id)
            ->where('conversations.user_id', '!=', $classroom->teacher_id)
            ->where('messages.role', 'model')
            ->where('messages.created_at', '>=', now()->subDays(self::DAYS)->startOfDay());
    }
}
