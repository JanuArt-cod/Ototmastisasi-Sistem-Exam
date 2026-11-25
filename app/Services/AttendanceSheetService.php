<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\SeatingAssignment;
use App\Models\Student;
use Illuminate\Support\Collection;

class AttendanceSheetService
{
    public const PAPER_PRESETS = [
        'f4_landscape' => [
            'label' => 'F4 / Legal Landscape (14 × 8,5 inci — Sesuai Referensi)',
            'width_in' => 14.0,
            'height_in' => 8.5,
            'width_pt' => 1008.0,
            'height_pt' => 612.0,
            'orientation' => 'landscape',
        ],
        'a4_landscape' => [
            'label' => 'A4 Landscape (297 × 210 mm)',
            'width_in' => 11.69,
            'height_in' => 8.27,
            'width_pt' => 841.89,
            'height_pt' => 595.28,
            'orientation' => 'landscape',
        ],
    ];

    /**
     * Mengembalikan konfigurasi default yang mengikuti tata letak referensi absen peserta.pdf.
     */
    public function defaultOptions(ExamSession $examSession): array
    {
        $defaultYear = $examSession->exam_date?->format('Y') ?? '';

        return [
            'room_filter' => 'all',
            'sort_by' => 'participant_number', // participant_number | seating_seat
            'paper_size' => 'f4_landscape',
            'first_page_rows' => 18,
            'continuation_page_rows' => 16,
            'target_room_capacity' => 34,
            'fill_empty_rows' => true,
            'issue_city' => trim((string) ($examSession->issue_city ?? '')),
            'issue_date_text' => '',
            'issue_year' => $defaultYear,
            'chair_title' => 'Ketua Panitia',
            'chair_name' => '',
            'secretary_title' => 'Sekretaris',
            'secretary_name' => '',
        ];
    }

    /**
     * Menggabungkan opsi pengguna dengan nilai default dan memvalidasi batas aman.
     */
    public function normalizeOptions(ExamSession $examSession, array $input = []): array
    {
        $defaults = $this->defaultOptions($examSession);

        $paperSize = array_key_exists((string) ($input['paper_size'] ?? ''), self::PAPER_PRESETS)
            ? (string) $input['paper_size']
            : $defaults['paper_size'];

        $sortBy = in_array(($input['sort_by'] ?? ''), ['participant_number', 'seating_seat'], true)
            ? (string) $input['sort_by']
            : $defaults['sort_by'];

        $firstPageRows = max(5, min(60, (int) ($input['first_page_rows'] ?? $defaults['first_page_rows'])));
        $continuationPageRows = max(5, min(60, (int) ($input['continuation_page_rows'] ?? $defaults['continuation_page_rows'])));
        $targetRoomCapacity = max(0, min(200, (int) ($input['target_room_capacity'] ?? $defaults['target_room_capacity'])));

        $fillEmptyRows = array_key_exists('fill_empty_rows', $input)
            ? filter_var($input['fill_empty_rows'], FILTER_VALIDATE_BOOLEAN)
            : $defaults['fill_empty_rows'];

        $roomFilter = trim((string) ($input['room_filter'] ?? 'all'));
        if ($roomFilter === '') {
            $roomFilter = 'all';
        }

        return [
            'room_filter' => $roomFilter,
            'sort_by' => $sortBy,
            'paper_size' => $paperSize,
            'paper_preset' => self::PAPER_PRESETS[$paperSize],
            'first_page_rows' => $firstPageRows,
            'continuation_page_rows' => $continuationPageRows,
            'target_room_capacity' => $targetRoomCapacity,
            'fill_empty_rows' => $fillEmptyRows,
            'issue_city' => trim((string) ($input['issue_city'] ?? $defaults['issue_city'])),
            'issue_date_text' => trim((string) ($input['issue_date_text'] ?? $defaults['issue_date_text'])),
            'issue_year' => trim((string) ($input['issue_year'] ?? $defaults['issue_year'])),
            'chair_title' => trim((string) ($input['chair_title'] ?? $defaults['chair_title'])) ?: 'Ketua Panitia',
            'chair_name' => trim((string) ($input['chair_name'] ?? $defaults['chair_name'])),
            'secretary_title' => trim((string) ($input['secretary_title'] ?? $defaults['secretary_title'])) ?: 'Sekretaris',
            'secretary_name' => trim((string) ($input['secretary_name'] ?? $defaults['secretary_name'])),
        ];
    }

    /**
     * Membangun seluruh struktur data Daftar Hadir Peserta untuk pratinjau UI maupun cetak PDF.
     */
    public function buildAttendanceData(ExamSession $examSession, array $rawOptions = []): array
    {
        $options = $this->normalizeOptions($examSession, $rawOptions);

        // 1. Ambil dan kelompokkan jadwal ujian berdasarkan hari & tanggal
        $scheduleGroups = $this->buildScheduleGroups($examSession);
        $totalScheduleDays = count($scheduleGroups);
        $totalSubjects = array_sum(array_map(static fn (array $g): int => count($g['subjects']), $scheduleGroups));

        // 2. Ambil seluruh siswa pada sesi ujian
        $students = $examSession->students()
            ->orderBy('id')
            ->get();

        $unassignedStudents = [];
        $groupedByRoom = [];

        foreach ($students as $student) {
            $roomName = trim((string) ($student->room_name ?? ''));
            if ($roomName === '') {
                $unassignedStudents[] = [
                    'id' => $student->id,
                    'participant_number' => trim((string) ($student->participant_number ?? '')),
                    'student_name' => trim((string) ($student->student_name ?? '')),
                    'class_name' => trim((string) ($student->class_name ?? '')),
                ];
                continue;
            }

            $roomKey = mb_strtolower($roomName);
            if (! isset($groupedByRoom[$roomKey])) {
                $groupedByRoom[$roomKey] = [
                    'room_name' => $roomName,
                    'students' => collect(),
                ];
            }
            $groupedByRoom[$roomKey]['students']->push($student);
        }

        // Urutkan daftar ruangan secara natural (Ruang 01, Ruang 02, ..., Ruang 10)
        uasort($groupedByRoom, static fn (array $a, array $b): int => strnatcasecmp($a['room_name'], $b['room_name']));

        $availableRooms = array_values(array_map(static fn (array $room): array => [
            'room_name' => $room['room_name'],
            'student_count' => $room['students']->count(),
        ], $groupedByRoom));

        // Ambil peta urutan kursi dari SeatingPlan yang sudah difinalisasi (opsional, read-only)
        $finalizedPlan = $examSession->seatingPlans()
            ->where('status', 'finalized')
            ->first();

        $seatOrderByStudentId = [];
        if ($finalizedPlan && $options['sort_by'] === 'seating_seat') {
            $seatOrderByStudentId = SeatingAssignment::query()
                ->where('seating_plan_id', $finalizedPlan->id)
                ->pluck('seat_number', 'student_id')
                ->all();
        }

        // 3. Susun data halaman per ruang
        $roomsOutput = [];
        $totalExportedPages = 0;

        foreach ($groupedByRoom as $roomEntry) {
            $roomName = $roomEntry['room_name'];

            if ($options['room_filter'] !== 'all' && strcasecmp($roomName, $options['room_filter']) !== 0) {
                continue;
            }

            $sortedStudents = $this->sortRoomStudents(
                $roomEntry['students'],
                $options['sort_by'],
                $seatOrderByStudentId
            );

            $pages = $this->paginateRoomStudents($sortedStudents, $options);
            $totalExportedPages += count($pages);

            $roomsOutput[] = [
                'room_name' => $roomName,
                'student_count' => $sortedStudents->count(),
                'page_count' => count($pages),
                'pages' => $pages,
            ];
        }

        $examTitle = trim((string) ($examSession->exam_title ?: $examSession->session_name));
        $schoolName = trim((string) ($examSession->school_name ?: 'SEKOLAH'));
        $academicYear = trim((string) ($examSession->academic_year ?? ''));

        return [
            'session' => [
                'id' => $examSession->id,
                'session_name' => $examSession->session_name,
                'exam_title' => $examTitle,
                'school_name' => $schoolName,
                'foundation_name' => trim((string) ($examSession->foundation_name ?? '')),
                'academic_year' => $academicYear,
                'term' => trim((string) ($examSession->term ?? '')),
            ],
            'options' => $options,
            'has_finalized_seating' => $finalizedPlan !== null,
            'summary' => [
                'total_students' => $students->count(),
                'assigned_students' => $students->count() - count($unassignedStudents),
                'unassigned_students' => count($unassignedStudents),
                'total_rooms' => count($availableRooms),
                'selected_rooms' => count($roomsOutput),
                'total_schedule_days' => $totalScheduleDays,
                'total_subjects' => $totalSubjects,
                'total_pages' => $totalExportedPages,
            ],
            'available_rooms' => $availableRooms,
            'unassigned_students' => $unassignedStudents,
            'schedule_groups' => $scheduleGroups,
            'rooms' => $roomsOutput,
        ];
    }

    /**
     * Mengelompokkan jadwal ujian berdasarkan hari/tanggal ujian.
     */
    private function buildScheduleGroups(ExamSession $examSession): array
    {
        $schedules = $examSession->schedules()->get();
        if ($schedules->isEmpty()) {
            return [];
        }

        $groups = [];
        foreach ($schedules as $schedule) {
            $dayNumber = (int) ($schedule->day_number ?: 1);
            $dayLabel = trim((string) ($schedule->day_date_label ?? ''));
            if ($dayLabel === '') {
                $dayLabel = 'Hari ke-' . $dayNumber;
            }

            $groupKey = $dayNumber . '::' . mb_strtolower($dayLabel);
            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'day_number' => $dayNumber,
                    'day_date_label' => $dayLabel,
                    'subjects' => [],
                ];
            }

            $groups[$groupKey]['subjects'][] = [
                'id' => $schedule->id,
                'subject_name' => trim((string) ($schedule->subject_name ?: '-')),
                'time_slot' => trim((string) ($schedule->time_slot ?? '')),
            ];
        }

        return array_values(array_map(static function (array $group): array {
            $group['colspan'] = count($group['subjects']);

            return $group;
        }, $groups));
    }

    /**
     * Mengurutkan siswa di dalam satu ruang tanpa mengubah nomor peserta asli.
     */
    private function sortRoomStudents(Collection $students, string $sortBy, array $seatOrderByStudentId): Collection
    {
        return $students->sort(function (Student $a, Student $b) use ($sortBy, $seatOrderByStudentId): int {
            if ($sortBy === 'seating_seat') {
                $seatA = $seatOrderByStudentId[$a->id] ?? PHP_INT_MAX;
                $seatB = $seatOrderByStudentId[$b->id] ?? PHP_INT_MAX;
                if ($seatA !== $seatB) {
                    return $seatA <=> $seatB;
                }
            }

            $numA = trim((string) ($a->participant_number ?? ''));
            $numB = trim((string) ($b->participant_number ?? ''));

            if ($numA !== '' && $numB !== '') {
                $cmp = strnatcasecmp($numA, $numB);
                if ($cmp !== 0) {
                    return $cmp;
                }
            } elseif ($numA !== '' || $numB !== '') {
                // Siswa yang memiliki nomor peserta ditempatkan lebih dulu dibanding yang kosong
                return $numA !== '' ? -1 : 1;
            }

            $nameCmp = strnatcasecmp((string) $a->student_name, (string) $b->student_name);
            if ($nameCmp !== 0) {
                return $nameCmp;
            }

            return $a->id <=> $b->id;
        })->values();
    }

    /**
     * Memecah daftar baris siswa dalam satu ruang menjadi halaman-halaman cetak.
     */
    private function paginateRoomStudents(Collection $sortedStudents, array $options): array
    {
        $rows = [];
        $rowNumber = 1;

        foreach ($sortedStudents as $student) {
            $rows[] = [
                'row_number' => $rowNumber++,
                'student_id' => $student->id,
                'participant_number' => trim((string) ($student->participant_number ?? '')),
                'student_name' => trim((string) ($student->student_name ?? '')),
                'class_name' => trim((string) ($student->class_name ?? '')),
                'is_empty' => false,
            ];
        }

        if ($options['fill_empty_rows'] && $options['target_room_capacity'] > count($rows)) {
            while (count($rows) < $options['target_room_capacity']) {
                $rows[] = [
                    'row_number' => $rowNumber++,
                    'student_id' => null,
                    'participant_number' => '',
                    'student_name' => '',
                    'class_name' => '',
                    'is_empty' => true,
                ];
            }
        }

        if (empty($rows)) {
            $rows[] = [
                'row_number' => 1,
                'student_id' => null,
                'participant_number' => '',
                'student_name' => '',
                'class_name' => '',
                'is_empty' => true,
            ];
        }

        $firstPageRows = $options['first_page_rows'];
        $continuationPageRows = $options['continuation_page_rows'];

        $pageChunks = [];
        $pageChunks[] = array_slice($rows, 0, $firstPageRows);

        $remainingRows = array_slice($rows, $firstPageRows);
        if (! empty($remainingRows)) {
            foreach (array_chunk($remainingRows, $continuationPageRows) as $chunk) {
                $pageChunks[] = $chunk;
            }
        }

        $totalPages = count($pageChunks);
        $pages = [];

        foreach ($pageChunks as $index => $chunk) {
            $pageNumber = $index + 1;
            $pages[] = [
                'page_number' => $pageNumber,
                'total_room_pages' => $totalPages,
                'is_first_page' => $pageNumber === 1,
                'is_last_page' => $pageNumber === $totalPages,
                'start_row' => $chunk[0]['row_number'],
                'end_row' => $chunk[count($chunk) - 1]['row_number'],
                'rows' => $chunk,
            ];
        }

        return $pages;
    }
}