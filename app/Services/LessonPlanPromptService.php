<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use App\Models\MainTarget;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Target;
use App\Models\User;
use Carbon\Carbon;
use RuntimeException;

class LessonPlanPromptService
{
    private static array $sectionMap = [
        'topic' => ['topik', 'topic', 'judul'],
        'learning_objectives' => ['tujuan pembelajaran', 'tujuan', 'learning objectives', 'objectives', 'capai'],
        'activities' => ['kegiatan pembelajaran', 'kegiatan', 'pembelajaran', 'activities', 'learning activities', 'langkah pembelajaran'],
        'materials' => ['materi ajar', 'materi', 'bahan ajar', 'materials', 'teaching materials'],
        'assessment' => ['penilaian', 'evaluasi', 'asesmen', 'assessment', 'evaluation'],
    ];

    private static array $dayMap = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu',
    ];

    public function collectContext(
        string $userId,
        ?string $subjectId,
        ?string $gradeId,
        ?string $targetId,
        ?string $mainTargetId,
        ?string $topic,
        ?string $plannedDate,
    ): array {
        $context = [
            'user_name' => null,
            'academic_year' => null,
            'subject' => null,
            'grade' => null,
            'schedule_days' => null,
            'planned_date' => $plannedDate,
            'topic' => $topic,
            'main_target' => null,
            'target' => null,
            'calendar_events' => [],
            'calendar_month' => null,
        ];

        $user = User::find($userId);
        if ($user) {
            $context['user_name'] = $user->name;
        }

        $activeYear = AcademicYear::active()->first();
        if ($activeYear) {
            $context['academic_year'] = $activeYear->year.' - '.($activeYear->semester?->getLabel() ?? '');
        }

        if ($subjectId) {
            $subject = Subject::with('grade')->find($subjectId);
            if ($subject) {
                $context['subject'] = $subject->name.' ('.($subject->code ?? '').')';
                if ($subject->grade) {
                    $context['grade'] = $subject->grade->name;
                }

                $schedule = Schedule::where('subject_id', $subjectId)->first();
                if ($schedule && $schedule->days) {
                    $days = array_map(fn ($day) => $this->dayToIndonesian($day), $schedule->days);
                    $context['schedule_days'] = implode(', ', $days);
                }
            }
        }

        if ($targetId) {
            $target = Target::with('mainTarget')->find($targetId);
            if ($target) {
                $context['target'] = $target->target;
                if ($target->mainTarget) {
                    $context['main_target'] = $target->mainTarget->main_target;
                }
            }
        } elseif ($mainTargetId) {
            $mainTarget = MainTarget::find($mainTargetId);
            if ($mainTarget) {
                $context['main_target'] = $mainTarget->main_target;
            }
        }

        if ($plannedDate) {
            try {
                $date = Carbon::parse($plannedDate);
                $month = $date->month;
                $year = $date->year;

                $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                $context['calendar_month'] = $monthNames[$month].' '.$year;

                $events = AcademicCalendar::where(function ($q) use ($month, $year) {
                    $q->whereMonth('start_date', $month)
                        ->whereYear('start_date', $year);
                })->orWhere(function ($q) use ($month, $year) {
                    $q->whereMonth('end_date', $month)
                        ->whereYear('end_date', $year);
                })->orderBy('start_date')->get();

                foreach ($events as $event) {
                    $start = $event->start_date->format('d/m');
                    $end = $event->end_date ? $event->end_date->format('d/m/Y') : '';
                    $dateRange = $start.($end && $event->end_date != $event->start_date ? '-'.$event->end_date->format('d/m') : '');
                    $context['calendar_events'][] = "{$dateRange}: {$event->title} ({$event->status?->getLabel()})";
                }
            } catch (\Exception $e) {
            }
        }

        return $context;
    }

    public function generatePrompt(array $context): string
    {
        $prompt = "Anda adalah asisten guru Indonesia yang membantu membuat Rencana Pelaksanaan Pembelajaran (RPP).\n";
        $prompt .= "Buat RPP berdasarkan konteks berikut:\n\n";
        $prompt .= "## Konteks\n";

        if ($context['user_name']) {
            $prompt .= "- **Guru:** {$context['user_name']}\n";
        }
        if ($context['academic_year']) {
            $prompt .= "- **Tahun Ajaran:** {$context['academic_year']}\n";
        }
        if ($context['subject']) {
            $prompt .= "- **Mata Pelajaran:** {$context['subject']}\n";
        }
        if ($context['grade']) {
            $prompt .= "- **Kelas:** {$context['grade']}\n";
        }
        if ($context['schedule_days']) {
            $prompt .= "- **Jadwal:** {$context['schedule_days']}\n";
        }
        if ($context['planned_date']) {
            $prompt .= "- **Tanggal:** {$context['planned_date']}\n";
        }
        if ($context['topic']) {
            $prompt .= "- **Topik:** {$context['topic']}\n";
        } else {
            $prompt .= "- **Topik:** (belum ditentukan, sarankan topik yang sesuai)\n";
        }
        if ($context['main_target']) {
            $prompt .= "- **Tujuan Utama:** {$context['main_target']}\n";
        }
        if ($context['target']) {
            $prompt .= "- **Target Pembelajaran:** {$context['target']}\n";
        }

        if ($context['calendar_events']) {
            $prompt .= "\n## Kalender Akademik Bulan ".($context['calendar_month'] ?? 'ini')."\n";
            foreach ($context['calendar_events'] as $event) {
                $prompt .= "- {$event}\n";
            }
        }

        $prompt .= "\nBerdasarkan konteks di atas, buat RPP dengan ketentuan:\n";
        $prompt .= "1. **Topik**: tulis topik dalam teks biasa\n";
        $prompt .= "2. **Tujuan Pembelajaran**: tulis dalam bentuk poin-poin (gunakan - di awal baris)\n";
        $prompt .= "3. **Kegiatan Pembelajaran**: tulis langkah-langkah (pendahuluan, inti, penutup) dengan struktur yang jelas\n";
        $prompt .= "4. **Materi Ajar**: tulis materi yang relevan\n";
        $prompt .= "5. **Penilaian**: tulis teknik dan instrumen penilaian\n\n";
        $prompt .= "Gunakan format berikut untuk respons:\n";
        $prompt .= "## Topik\n...\n\n## Tujuan Pembelajaran\n...\n\n## Kegiatan Pembelajaran\n...\n\n## Materi Ajar\n...\n\n## Penilaian\n...\n";

        return $prompt;
    }

    public function parseResponse(string $markdown): array
    {
        if (empty(trim($markdown))) {
            throw new RuntimeException('Respons AI masih kosong.');
        }

        $sections = $this->extractSections($markdown);

        if (empty($sections)) {
            throw new RuntimeException(
                'Format tidak dikenali. Pastikan respons mengandung heading: '.
                '## Topik, ## Tujuan Pembelajaran, ## Kegiatan Pembelajaran, ## Materi Ajar, dan ## Penilaian.'
            );
        }

        $parsedData = [];
        $missingSections = [];
        $warnings = [];
        $foundSections = [];

        $expectedSections = ['topic', 'learning_objectives', 'activities', 'materials', 'assessment'];

        foreach ($sections as $section) {
            $field = $section['matched_section'];
            $content = $section['content'];

            if ($field === null) {
                $warnings[] = "Heading \"{$section['original_heading']}\" tidak dikenali dan akan diabaikan.";

                continue;
            }

            $foundSections[] = $field;

            if (empty(trim($content))) {
                $warnings[] = 'Bagian "'.$section['original_heading'].'" ditemukan tetapi kosong.';
                $parsedData[$field] = '';

                continue;
            }

            if ($section['original_heading'] !== $this->getExpectedHeading($field)) {
                $warnings[] = 'Heading "'.$section['original_heading'].'" dikenali sebagai "'.$this->getExpectedHeading($field).'".';
            }

            if ($field === 'topic') {
                $parsedData[$field] = trim($content);
            } else {
                $parsedData[$field] = $this->markdownToHtml($content);
            }
        }

        foreach ($expectedSections as $expected) {
            if (! in_array($expected, $foundSections)) {
                $missingSections[] = $this->getExpectedHeading($expected);
            }
        }

        return [
            'parsedData' => $parsedData,
            'missingSections' => $missingSections,
            'warnings' => $warnings,
        ];
    }

    private function extractSections(string $markdown): array
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $markdown = trim($markdown);

        $parts = preg_split('/^##\s+/m', $markdown);

        if (count($parts) <= 1) {
            return [];
        }

        $sections = [];

        for ($i = 1; $i < count($parts); $i++) {
            $part = $parts[$i];
            $firstNewline = strpos($part, "\n");

            if ($firstNewline === false) {
                $heading = trim($part);
                $content = '';
            } else {
                $heading = trim(substr($part, 0, $firstNewline));
                $content = trim(substr($part, $firstNewline + 1));
            }

            $matchedSection = $this->matchSection($heading);

            $sections[] = [
                'original_heading' => $heading,
                'matched_section' => $matchedSection,
                'content' => $content,
            ];
        }

        return $sections;
    }

    private function matchSection(string $heading): ?string
    {
        $normalized = mb_strtolower(trim($heading));

        foreach (self::$sectionMap as $section => $keywords) {
            foreach ($keywords as $keyword) {
                if ($normalized === $keyword) {
                    return $section;
                }
            }
        }

        foreach (self::$sectionMap as $section => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $section;
                }
            }
        }

        return null;
    }

    private function getExpectedHeading(string $section): string
    {
        return match ($section) {
            'topic' => 'Topik',
            'learning_objectives' => 'Tujuan Pembelajaran',
            'activities' => 'Kegiatan Pembelajaran',
            'materials' => 'Materi Ajar',
            'assessment' => 'Penilaian',
            default => $section,
        };
    }

    private function dayToIndonesian(string $day): string
    {
        return self::$dayMap[strtolower($day)] ?? ucfirst($day);
    }

    private function markdownToHtml(string $text): string
    {
        $text = trim($text);

        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

        $text = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $text);

        $text = preg_replace('/^[-*]\s+(.+)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/((?:<li>.*<\/li>\n?)+)/', "<ul>\n$1</ul>", $text);

        $text = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/((?:<li>.*<\/li>\n?)+)/', "<ol>\n$1</ol>", $text);

        $lines = explode("\n", $text);
        $result = [];
        $buffer = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                if (! empty($buffer)) {
                    $para = implode("\n", $buffer);
                    if (! preg_match('/^<(ul|ol|h[34]|table|blockquote)/', $para)) {
                        $result[] = '<p>'.$para.'</p>';
                    } else {
                        $result[] = $para;
                    }
                    $buffer = [];
                }

                continue;
            }

            if (preg_match('/^<(ul|ol|h[34]|li|table|blockquote)/', $line)) {
                if (! empty($buffer)) {
                    $para = implode("\n", $buffer);
                    $result[] = '<p>'.$para.'</p>';
                    $buffer = [];
                }
                $result[] = $line;

                continue;
            }

            $buffer[] = $line;
        }

        if (! empty($buffer)) {
            $para = implode("\n", $buffer);
            if (! preg_match('/^<(ul|ol|h[34]|table|blockquote)/', $para)) {
                $result[] = '<p>'.$para.'</p>';
            } else {
                $result[] = $para;
            }
        }

        return implode("\n", $result);
    }
}
