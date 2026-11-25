<?php

namespace App\Services;

use App\Models\ExamSchedule;
use App\Models\ExamSession;
use Illuminate\Support\Facades\Storage;

class ScheduleShareService
{
    public const PAPER_PRESETS = [
        'a4_portrait' => [
            'label' => 'A4 Portrait (210 × 297 mm)',
        ],
        'f4_portrait' => [
            'label' => 'F4 / Folio Portrait (215 × 330 mm)',
        ],
    ];

    public function loadSavedConfig(ExamSession $session): array
    {
        $path = $this->storagePath($session);
        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        $raw = Storage::disk('local')->get($path);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function saveConfig(ExamSession $session, array $input): array
    {
        $existing = $this->loadSavedConfig($session);
        $merged = array_merge($existing, $input);

        if (isset($input['proctors']) && is_array($input['proctors'])) {
            $merged['proctors'] = array_replace_recursive($existing['proctors'] ?? [], $input['proctors']);
        }

        if (isset($input['committees']) && is_array($input['committees'])) {
            $merged['committees'] = array_replace_recursive($existing['committees'] ?? [], $input['committees']);
        }

        $normalized = $this->normalizeConfig($session, $merged);
        $toPersist = $normalized;
        unset($toPersist['detected_rooms']);

        Storage::disk('local')->put(
            $this->storagePath($session),
            json_encode($toPersist, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $normalized;
    }

    public function resetConfig(ExamSession $session): void
    {
        $path = $this->storagePath($session);
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    public function detectRooms(ExamSession $session): array
    {
        $rawRooms = $session->students()
            ->whereNotNull('room_name')
            ->where('room_name', '!=', '')
            ->distinct()
            ->pluck('room_name')
            ->map(fn ($r) => trim((string) $r))
            ->filter(fn ($r) => $r !== '')
            ->values()
            ->all();

        if (empty($rawRooms)) {
            return ['1', '2', '3', '4', '5', '6'];
        }

        usort($rawRooms, fn (string $a, string $b) => strnatcasecmp($a, $b));

        $labels = [];
        foreach ($rawRooms as $room) {
            $labels[] = $this->formatShortRoomLabel($room);
        }

        return array_values(array_unique($labels));
    }

    public function formatShortRoomLabel(string $roomName): string
    {
        $trimmed = trim($roomName);
        if (preg_match('/^ruang\s*0*(\d+)$/i', $trimmed, $matches)) {
            return (string) ((int) $matches[1]);
        }

        return $trimmed;
    }

    public function normalizeConfig(ExamSession $session, array $input = []): array
    {
        $detectedRooms = $this->detectRooms($session);

        $paperSize = (string) ($input['paper_size'] ?? 'f4_portrait');
        if (! array_key_exists($paperSize, self::PAPER_PRESETS)) {
            $paperSize = 'f4_portrait';
        }

        $customRooms = [];
        if (! empty($input['rooms']) && is_array($input['rooms'])) {
            foreach ($input['rooms'] as $r) {
                $val = trim((string) $r);
                if ($val !== '') {
                    $customRooms[] = $val;
                }
            }
        }
        $rooms = ! empty($customRooms) ? array_values(array_unique($customRooms)) : $detectedRooms;

        $examTitle = trim((string) ($session->exam_title ?: $session->session_name));
        $academicYear = trim((string) ($session->academic_year ?? ''));

        $defaultHeaderTitle = 'JADWAL PENGAWAS RUANG ' . strtoupper($examTitle);
        $headerTitle = array_key_exists('header_title', $input)
            ? trim((string) $input['header_title'])
            : $defaultHeaderTitle;

        $defaultFooterSignatory = 'Panitia Ujian' . ($academicYear !== '' ? ' Tahun ' . $academicYear : '');
        $footerSignatory = array_key_exists('footer_signatory', $input)
            ? trim((string) $input['footer_signatory'])
            : $defaultFooterSignatory;

        $teacherPoolText = trim((string) ($input['teacher_pool_text'] ?? ''));

        $teacherRules = [];
        if (! empty($input['teacher_rules']) && is_array($input['teacher_rules'])) {
            foreach ($input['teacher_rules'] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $maxDays = isset($row['max_days']) && $row['max_days'] !== '' && (int) $row['max_days'] > 0
                    ? (int) $row['max_days']
                    : null;
                $allowedDays = array_values(array_unique(array_filter(
                    array_map('intval', (array) ($row['allowed_days'] ?? [])),
                    fn (int $d) => $d > 0
                )));
                $forbiddenRaw = trim((string) ($row['forbidden_subjects_text'] ?? ''));
                $teacherRules[] = [
                    'name' => $name,
                    'max_days' => $maxDays,
                    'allowed_days' => $allowedDays,
                    'forbidden_subjects_text' => $forbiddenRaw,
                ];
            }
        }

        if (empty($teacherRules) && $teacherPoolText !== '') {
            $parsedFallback = $this->parseTeacherPool($teacherPoolText, null);
            foreach ($parsedFallback as $pf) {
                $teacherRules[] = [
                    'name' => $pf['name'],
                    'max_days' => $pf['max_days'],
                    'allowed_days' => $pf['allowed_days'],
                    'forbidden_subjects_text' => implode(', ', $pf['forbidden_subjects']),
                ];
            }
        }

        $maxDaysRaw = $input['max_days_per_teacher'] ?? null;
        $maxDaysPerTeacher = ($maxDaysRaw !== null && $maxDaysRaw !== '' && (int) $maxDaysRaw > 0)
            ? (int) $maxDaysRaw
            : null;

        $autoAssignWarnings = [];
        if (! empty($input['auto_assign_warnings']) && is_array($input['auto_assign_warnings'])) {
            $autoAssignWarnings = array_values(array_map('strval', $input['auto_assign_warnings']));
        }

        $proctors = [];
        if (isset($input['proctors']) && is_array($input['proctors'])) {
            foreach ($input['proctors'] as $schedId => $roomMap) {
                if (! is_array($roomMap)) {
                    continue;
                }
                foreach ($roomMap as $roomLabel => $teacherName) {
                    $proctors[(string) $schedId][(string) $roomLabel] = trim((string) $teacherName);
                }
            }
        }

        $committees = [];
        if (isset($input['committees']) && is_array($input['committees'])) {
            foreach ($input['committees'] as $dayNum => $lines) {
                if (is_array($lines)) {
                    $cleaned = array_values(array_filter(
                        array_map(fn ($l) => trim((string) $l), $lines),
                        fn ($l) => $l !== ''
                    ));
                    $committees[(string) $dayNum] = $cleaned;
                } elseif (is_string($lines)) {
                    $split = preg_split('/\r\n|\r|\n/', $lines) ?: [];
                    $cleaned = array_values(array_filter(
                        array_map('trim', $split),
                        fn ($l) => $l !== ''
                    ));
                    $committees[(string) $dayNum] = $cleaned;
                }
            }
        }

        $showProctorColumns = array_key_exists('show_proctor_columns', $input)
            ? (bool) $input['show_proctor_columns']
            : true;

        return [
            'paper_size' => $paperSize,
            'header_title' => $headerTitle !== '' ? $headerTitle : $defaultHeaderTitle,
            'footer_signatory' => $footerSignatory,
            'show_proctor_columns' => $showProctorColumns,
            'rooms' => $rooms,
            'detected_rooms' => $detectedRooms,
            'teacher_pool_text' => $teacherPoolText,
            'teacher_rules' => $teacherRules,
            'max_days_per_teacher' => $maxDaysPerTeacher,
            'auto_assign_warnings' => $autoAssignWarnings,
            'proctors' => $proctors,
            'committees' => $committees,
        ];
    }

    /**
     * Format baris teks guru yang didukung:
     * - "Nama Guru"
     * - "Nama Guru | 2"
     * - "Nama Guru | 2 | Hari 1, Hari 3 | Matematika, IPA"
     * - "Nama Guru | Rabu, Sabtu | Matematika"
     *
     * @param array<int, array{day_number: int, day_date_label: string}> $dayCatalog
     * @return array<int, array{name: string, max_days: ?int, allowed_days: array<int, int>, forbidden_subjects: array<int, string>}>
     */
    public function parseTeacherPool(string $teacherPoolText, ?int $defaultMaxDays = null, array $dayCatalog = []): array
    {
        $rawLines = preg_split('/\r\n|\r|\n/', $teacherPoolText) ?: [];
        $parsedByName = [];

        foreach ($rawLines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $trimmed));
            $name = $parts[0] ?? '';
            if ($name === '') {
                continue;
            }

            $teacherMaxDays = $defaultMaxDays;
            $allowedDays = [];
            $forbidden = [];

            for ($i = 1; $i < count($parts); $i++) {
                $seg = $parts[$i];
                if ($seg === '') {
                    continue;
                }

                // Segmen angka tunggal = kuota hari (mis. "2" atau "3 hari")
                if (preg_match('/^(\d+)\s*(hari)?$/i', $seg, $m)) {
                    $val = (int) $m[1];
                    if ($val > 0) {
                        $teacherMaxDays = $val;
                    }
                    continue;
                }

                // Cek apakah segmen berisi spesifikasi hari (mis. "Hari 1, Hari 3" atau "Rabu, Kamis")
                $detectedDayNums = $this->parseAllowedDaysSegment($seg, $dayCatalog);
                if (! empty($detectedDayNums)) {
                    $allowedDays = array_values(array_unique(array_merge($allowedDays, $detectedDayNums)));
                    continue;
                }

                // Selain itu dianggap daftar mapel yang diampu (dilarang diawasi)
                $subjParts = preg_split('/[,;]+/', $seg) ?: [];
                foreach ($subjParts as $sp) {
                    $norm = $this->normalizeSubjectKey($sp);
                    if ($norm !== '') {
                        $forbidden[] = $norm;
                    }
                }
            }

            $nameKey = mb_strtolower($name);
            if (! isset($parsedByName[$nameKey])) {
                $parsedByName[$nameKey] = [
                    'name' => $name,
                    'max_days' => $teacherMaxDays,
                    'allowed_days' => $allowedDays,
                    'forbidden_subjects' => array_values(array_unique($forbidden)),
                ];
            } else {
                if ($teacherMaxDays !== null) {
                    $parsedByName[$nameKey]['max_days'] = $teacherMaxDays;
                }
                $parsedByName[$nameKey]['allowed_days'] = array_values(array_unique(
                    array_merge($parsedByName[$nameKey]['allowed_days'], $allowedDays)
                ));
                $parsedByName[$nameKey]['forbidden_subjects'] = array_values(array_unique(
                    array_merge($parsedByName[$nameKey]['forbidden_subjects'], $forbidden)
                ));
            }
        }

        return array_values($parsedByName);
    }

    /**
     * @param array<int, array<string, mixed>> $structuredRules
     */
    public function autoAssignProctors(
        ExamSession $session,
        string $teacherPoolText = '',
        ?int $maxDaysPerTeacher = null,
        array $structuredRules = []
    ): array {
        $saved = $this->loadSavedConfig($session);
        $effectiveDefaultMaxDays = ($maxDaysPerTeacher !== null && $maxDaysPerTeacher > 0)
            ? $maxDaysPerTeacher
            : null;

        $schedules = $session->schedules()->get();
        $groupedDays = $schedules->groupBy(function (ExamSchedule $item) {
            return $item->day_number . '||' . trim((string) $item->day_date_label);
        });

        $dayCatalog = [];
        foreach ($groupedDays as $items) {
            /** @var ExamSchedule $first */
            $first = $items->first();
            $dayCatalog[] = [
                'day_number' => (int) $first->day_number,
                'day_date_label' => trim((string) $first->day_date_label),
            ];
        }

        $teachers = [];
        $normalizedRulesForSave = [];

        if (! empty($structuredRules)) {
            foreach ($structuredRules as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $rowMaxDays = (isset($row['max_days']) && $row['max_days'] !== '' && (int) $row['max_days'] > 0)
                    ? (int) $row['max_days']
                    : $effectiveDefaultMaxDays;

                $allowedDays = array_values(array_unique(array_filter(
                    array_map('intval', (array) ($row['allowed_days'] ?? [])),
                    fn (int $d) => $d > 0
                )));

                $forbiddenRaw = trim((string) ($row['forbidden_subjects_text'] ?? ''));
                $forbiddenNormalized = [];
                if ($forbiddenRaw !== '') {
                    foreach (preg_split('/[,;]+/', $forbiddenRaw) ?: [] as $sp) {
                        $norm = $this->normalizeSubjectKey($sp);
                        if ($norm !== '') {
                            $forbiddenNormalized[] = $norm;
                        }
                    }
                }

                $teachers[] = [
                    'name' => $name,
                    'max_days' => $rowMaxDays,
                    'allowed_days' => $allowedDays,
                    'forbidden_subjects' => array_values(array_unique($forbiddenNormalized)),
                ];

                $normalizedRulesForSave[] = [
                    'name' => $name,
                    'max_days' => $rowMaxDays,
                    'allowed_days' => $allowedDays,
                    'forbidden_subjects_text' => $forbiddenRaw,
                ];
            }
        } else {
            $teachers = $this->parseTeacherPool($teacherPoolText, $effectiveDefaultMaxDays, $dayCatalog);
            foreach ($teachers as $t) {
                $normalizedRulesForSave[] = [
                    'name' => $t['name'],
                    'max_days' => $t['max_days'],
                    'allowed_days' => $t['allowed_days'],
                    'forbidden_subjects_text' => implode(', ', $t['forbidden_subjects']),
                ];
            }
        }

        $config = $this->normalizeConfig($session, array_merge($saved, [
            'teacher_pool_text' => trim($teacherPoolText),
            'teacher_rules' => $normalizedRulesForSave,
            'max_days_per_teacher' => $effectiveDefaultMaxDays,
        ]));

        if (empty($teachers)) {
            return $this->saveConfig($session, [
                'teacher_pool_text' => '',
                'teacher_rules' => [],
                'max_days_per_teacher' => $effectiveDefaultMaxDays,
                'auto_assign_warnings' => [],
                'proctors' => [],
            ]);
        }

        $rooms = $config['rooms'];
        $roomCount = count($rooms);

        $daysWorkedCount = array_fill(0, count($teachers), 0);
        $visitedRoomsByTeacher = array_fill(0, count($teachers), []);

        $assignments = [];
        $warnings = [];

        foreach ($groupedDays as $dayItems) {
            /** @var ExamSchedule $firstItem */
            $firstItem = $dayItems->first();
            $dayNumber = (int) $firstItem->day_number;

            $daySubjects = [];
            foreach ($dayItems as $sched) {
                $daySubjects[] = [
                    'id' => (string) $sched->id,
                    'name' => (string) $sched->subject_name,
                    'key' => $this->normalizeSubjectKey((string) $sched->subject_name),
                ];
            }

            // 1. Pilih tim pengawas untuk 1 hari penuh (sebanyak jumlah ruang)
            $eligibleForDay = [];
            foreach ($teachers as $tIdx => $teacher) {
                // Cek apakah guru hanya bisa mengawas di hari tertentu
                if (! empty($teacher['allowed_days']) && ! in_array($dayNumber, $teacher['allowed_days'], true)) {
                    continue;
                }

                // Cek kuota maksimal hari mengawas guru tersebut
                if ($teacher['max_days'] !== null && $daysWorkedCount[$tIdx] >= $teacher['max_days']) {
                    continue;
                }

                // Cek larangan mengawas mata pelajaran yang diampu
                $teachesToday = false;
                foreach ($daySubjects as $ds) {
                    if ($this->isSubjectForbidden($ds['key'], $teacher['forbidden_subjects'])) {
                        $teachesToday = true;
                        break;
                    }
                }
                if ($teachesToday) {
                    continue;
                }

                // Prioritaskan guru yang jadwal hari ketersediaannya lebih terbatas agar tidak terbuang
                $restrictedDayWeight = ! empty($teacher['allowed_days']) ? count($teacher['allowed_days']) : 99;
                $remainingQuota = $teacher['max_days'] !== null
                    ? ($teacher['max_days'] - $daysWorkedCount[$tIdx])
                    : 999;

                $newRoomsAvailable = 0;
                foreach ($rooms as $rLabel) {
                    if (! isset($visitedRoomsByTeacher[$tIdx][$rLabel])) {
                        $newRoomsAvailable++;
                    }
                }

                $eligibleForDay[] = [
                    'tIdx' => $tIdx,
                    'days_worked' => $daysWorkedCount[$tIdx],
                    'restricted_weight' => $restrictedDayWeight,
                    'new_rooms_available' => $newRoomsAvailable,
                    'remaining_quota' => $remainingQuota,
                    'tie' => ($tIdx + $dayNumber) % count($teachers),
                ];
            }

            usort($eligibleForDay, function (array $a, array $b) {
                return [$a['days_worked'], $a['restricted_weight'], -$a['new_rooms_available'], -$a['remaining_quota'], $a['tie']]
                    <=> [$b['days_worked'], $b['restricted_weight'], -$b['new_rooms_available'], -$b['remaining_quota'], $b['tie']];
            });

            $dayTeam = array_slice(array_column($eligibleForDay, 'tIdx'), 0, $roomCount);

            if (count($dayTeam) < $roomCount) {
                $warnings[] = 'Hari ke-' . $dayNumber . ': hanya tersedia ' . count($dayTeam) . ' dari ' . $roomCount . ' pengawas sesuai hari ketersediaan, kuota hari & larangan mapel.';
            }

            // 2. Rolling posisi ruang antar jam dalam 1 hari & hindari ruang yang sudah diawasi di hari sebelumnya
            $slotCountInDay = count($daySubjects);
            $usedRoomInTodaySlots = [];
            foreach ($dayTeam as $tIdx) {
                $usedRoomInTodaySlots[$tIdx] = [];
            }

            $teamAssignmentsPerSlot = [];
            for ($sIdx = 0; $sIdx < $slotCountInDay; $sIdx++) {
                $teamAssignmentsPerSlot[$sIdx] = array_fill(0, $roomCount, '');
            }

            if (! empty($dayTeam)) {
                $roomOrderPerSlot = [];
                for ($sIdx = 0; $sIdx < $slotCountInDay; $sIdx++) {
                    if ($slotCountInDay > 1 && $sIdx === $slotCountInDay - 1) {
                        $roomOrderPerSlot[$sIdx] = array_reverse(array_keys($rooms));
                    } else {
                        $rotated = [];
                        for ($k = 0; $k < $roomCount; $k++) {
                            $rotated[] = ($k + $sIdx) % $roomCount;
                        }
                        $roomOrderPerSlot[$sIdx] = $rotated;
                    }
                }

                for ($sIdx = 0; $sIdx < $slotCountInDay; $sIdx++) {
                    $takenRoomIndices = [];

                    foreach ($dayTeam as $memberPos => $tIdx) {
                        $preferredRoomIdx = $roomOrderPerSlot[$sIdx][$memberPos] ?? ($memberPos % $roomCount);
                        $bestRoomIdx = null;
                        $bestScore = PHP_INT_MAX;

                        for ($rIdx = 0; $rIdx < $roomCount; $rIdx++) {
                            if (in_array($rIdx, $takenRoomIndices, true)) {
                                continue;
                            }

                            $rLabel = $rooms[$rIdx];
                            $visitedBefore = isset($visitedRoomsByTeacher[$tIdx][$rLabel]) ? 200 : 0;
                            $visitedToday = isset($usedRoomInTodaySlots[$tIdx][$rLabel]) ? 80 : 0;
                            $distanceFromRolling = abs($rIdx - $preferredRoomIdx);

                            $score = $visitedBefore + $visitedToday + $distanceFromRolling;
                            if ($score < $bestScore) {
                                $bestScore = $score;
                                $bestRoomIdx = $rIdx;
                            }
                        }

                        if ($bestRoomIdx !== null) {
                            $takenRoomIndices[] = $bestRoomIdx;
                            $chosenRoomLabel = $rooms[$bestRoomIdx];
                            $usedRoomInTodaySlots[$tIdx][$chosenRoomLabel] = true;
                            $teamAssignmentsPerSlot[$sIdx][$bestRoomIdx] = $teachers[$tIdx]['name'];
                        }
                    }
                }

                foreach ($dayTeam as $tIdx) {
                    $daysWorkedCount[$tIdx]++;
                    foreach (array_keys($usedRoomInTodaySlots[$tIdx]) as $visitedLabel) {
                        $visitedRoomsByTeacher[$tIdx][$visitedLabel] = true;
                    }
                }
            }

            foreach ($daySubjects as $sIdx => $ds) {
                $schedKey = $ds['id'];
                $assignments[$schedKey] = [];
                foreach ($rooms as $rIdx => $rLabel) {
                    $assignments[$schedKey][$rLabel] = $teamAssignmentsPerSlot[$sIdx][$rIdx] ?? '';
                }
            }
        }

        return $this->saveConfig($session, [
            'teacher_pool_text' => trim($teacherPoolText),
            'teacher_rules' => $normalizedRulesForSave,
            'max_days_per_teacher' => $effectiveDefaultMaxDays,
            'auto_assign_warnings' => array_slice($warnings, 0, 10),
            'proctors' => $assignments,
        ]);
    }

    public function buildScheduleData(ExamSession $session, array $overrideInput = []): array
    {
        $saved = $this->loadSavedConfig($session);
        $merged = array_merge($saved, $overrideInput);
        $options = $this->normalizeConfig($session, $merged);

        $schedules = $session->schedules()->get();
        $rooms = $options['rooms'];
        $roomCount = max(1, count($rooms));

        $grouped = $schedules->groupBy(function (ExamSchedule $item) {
            return $item->day_number . '||' . trim((string) $item->day_date_label);
        });

        $days = [];
        foreach ($grouped as $items) {
            /** @var ExamSchedule $first */
            $first = $items->first();
            $dayNumber = (int) $first->day_number;
            $dayKey = (string) $dayNumber;

            $slotList = [];
            $jamKe = 1;

            foreach ($items as $row) {
                $schedKey = (string) $row->id;
                $roomRows = [];

                foreach ($rooms as $roomLabel) {
                    $roomRows[] = [
                        'room_label' => $roomLabel,
                        'proctor_name' => $options['proctors'][$schedKey][$roomLabel] ?? '',
                    ];
                }

                $slotList[] = [
                    'schedule_id' => (int) $row->id,
                    'jam_ke' => $jamKe,
                    'time_slot' => trim((string) $row->time_slot),
                    'subject_name' => trim((string) $row->subject_name),
                    'slot_rowspan' => $roomCount,
                    'room_rows' => $roomRows,
                ];

                $jamKe++;
            }

            $days[] = [
                'day_number' => $dayNumber,
                'day_date_label' => trim((string) $first->day_date_label),
                'day_rowspan' => count($slotList) * $roomCount,
                'committee_lines' => $options['committees'][$dayKey] ?? [],
                'slots' => $slotList,
            ];
        }

        $schoolName = trim((string) ($session->school_name ?: 'Nama Sekolah Belum Diisi'));
        $examTitle = trim((string) ($session->exam_title ?: $session->session_name));
        $academicYear = trim((string) ($session->academic_year ?? ''));

        $sessionInfo = [
            'id' => (int) $session->id,
            'session_name' => trim((string) $session->session_name),
            'school_name' => $schoolName,
            'exam_title' => $examTitle,
            'academic_year' => $academicYear,
        ];

        return [
            'session' => $sessionInfo,
            'options' => $options,
            'days' => $days,
            'summary' => [
                'total_days' => count($days),
                'total_subjects' => $schedules->count(),
                'total_rooms' => count($rooms),
            ],
            'whatsapp_text' => $this->formatWhatsAppText($sessionInfo, $days, $options),
        ];
    }

    public function formatWhatsAppText(array $sessionInfo, array $days, array $options): string
    {
        $lines = [];
        $lines[] = '*' . strtoupper($options['header_title']) . '*';
        $lines[] = '*' . strtoupper($sessionInfo['school_name']) . '*';

        if ($sessionInfo['academic_year'] !== '') {
            $lines[] = '*TAHUN PELAJARAN ' . strtoupper($sessionInfo['academic_year']) . '*';
        }

        $lines[] = '----------------------------------------';

        if (empty($days)) {
            $lines[] = '_Belum ada jadwal mata pelajaran yang diinput._';
        } else {
            foreach ($days as $day) {
                $lines[] = '';
                $lines[] = '*' . $day['day_number'] . '. ' . $day['day_date_label'] . '*';

                foreach ($day['slots'] as $slot) {
                    $lines[] = '   Jam ke-' . $slot['jam_ke'] . ' (`' . $slot['time_slot'] . '`) : *' . $slot['subject_name'] . '*';

                    if ($options['show_proctor_columns']) {
                        $assigned = array_filter($slot['room_rows'], fn ($r) => $r['proctor_name'] !== '');
                        if (! empty($assigned)) {
                            foreach ($assigned as $rRow) {
                                $lines[] = '      • Ruang ' . $rRow['room_label'] . ': ' . $rRow['proctor_name'];
                            }
                        }
                    }
                }

                if ($options['show_proctor_columns'] && ! empty($day['committee_lines'])) {
                    $lines[] = '   _Panitia:_ ' . implode(', ', $day['committee_lines']);
                }
            }
        }

        if ($options['footer_signatory'] !== '') {
            $lines[] = '';
            $lines[] = '_' . $options['footer_signatory'] . '_';
        }

        return implode("\n", $lines);
    }

    /**
     * Mengenali token hari seperti "Hari 1, Hari 3", "1, 3", atau "Rabu, Sabtu".
     *
     * @param array<int, array{day_number: int, day_date_label: string}> $dayCatalog
     * @return array<int, int>
     */
    private function parseAllowedDaysSegment(string $segment, array $dayCatalog): array
    {
        $tokens = preg_split('/[,;]+/', $segment) ?: [];
        $matchedDays = [];
        $validTokenCount = 0;

        foreach ($tokens as $tok) {
            $clean = mb_strtolower(trim($tok));
            if ($clean === '') {
                continue;
            }
            $validTokenCount++;

            if (preg_match('/^(?:hari\s*(?:ke[-\s]*)?)?(\d+)$/i', $clean, $m)) {
                $matchedDays[] = (int) $m[1];
                continue;
            }

            if (preg_match('/^(senin|selasa|rabu|kamis|jumat|jum\'at|sabtu|minggu|ahad)$/i', $clean)) {
                $needle = str_replace("'", '', $clean);
                foreach ($dayCatalog as $dc) {
                    $labelNorm = str_replace("'", '', mb_strtolower($dc['day_date_label']));
                    if (str_contains($labelNorm, $needle)) {
                        $matchedDays[] = (int) $dc['day_number'];
                    }
                }
            }
        }

        // Hanya dianggap segmen hari jika semua token di segmen ini memang merujuk ke hari
        if ($validTokenCount > 0 && count($matchedDays) >= $validTokenCount) {
            return array_values(array_unique($matchedDays));
        }

        return [];
    }

    private function normalizeSubjectKey(string $subject): string
    {
        $clean = mb_strtolower(trim($subject));
        $clean = preg_replace('/[^a-z0-9\s]/u', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        return trim($clean);
    }

    private function isSubjectForbidden(string $scheduleSubjectKey, array $forbiddenSubjects): bool
    {
        if ($scheduleSubjectKey === '' || empty($forbiddenSubjects)) {
            return false;
        }

        foreach ($forbiddenSubjects as $forbidden) {
            if ($forbidden === '') {
                continue;
            }
            if ($scheduleSubjectKey === $forbidden) {
                return true;
            }
            if (mb_strlen($forbidden) >= 4 && (str_contains($scheduleSubjectKey, $forbidden) || str_contains($forbidden, $scheduleSubjectKey))) {
                return true;
            }
        }

        return false;
    }

    private function storagePath(ExamSession $session): string
    {
        return 'schedule-share/session-' . $session->id . '.json';
    }
}