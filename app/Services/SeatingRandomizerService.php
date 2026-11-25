<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\SeatingAssignment;
use App\Models\SeatingPlan;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SeatingRandomizerService
{
    /**
     * Mengekstrak tingkat kelas secara eksplisit (misal: "7A" & "7B" -> "7", "Kelas 8 - 8A" -> "8", "IX-B" -> "9").
     */
    public function extractGradeLevel(?string $className): ?string
    {
        $raw = trim((string) $className);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/^(?:kelas|kls)\.?\s*/i', '', $raw) ?? $raw;
        $normalized = trim($normalized);

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^(1[0-2]|[1-9])(?=$|[^0-9])/u', $normalized, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(XII|XI|IX|VIII|VII|VI|IV|III|II|X|V|I)(?=$|[^A-Za-z0-9])/iu', $normalized, $matches)) {
            $roman = strtoupper($matches[1]);
            $romanMap = [
                'I' => '1',
                'II' => '2',
                'III' => '3',
                'IV' => '4',
                'V' => '5',
                'VI' => '6',
                'VII' => '7',
                'VIII' => '8',
                'IX' => '9',
                'X' => '10',
                'XI' => '11',
                'XII' => '12',
            ];

            return $romanMap[$roman] ?? null;
        }

        return null;
    }

    /**
     * Memeriksa kesiapan data peserta dan alokasi ruangan dari modul Pengaturan Ruang Ujian.
     */
    public function inspectCandidates(ExamSession $examSession, string $selectionMode = 'all', array $selectedClasses = []): array
    {
        $query = $examSession->students()->getQuery()->orderBy('room_name')->orderBy('class_name')->orderBy('student_name')->orderBy('id');

        $cleanClasses = array_values(array_unique(array_filter(
            array_map(static fn ($c) => trim((string) $c), $selectedClasses),
            static fn ($c) => $c !== ''
        )));

        if ($selectionMode === 'classes') {
            if (empty($cleanClasses)) {
                throw new RuntimeException('Pilih minimal satu kelas apabila menggunakan mode filter kelas.');
            }
            $query->whereIn('class_name', $cleanClasses);
        }

        /** @var Collection<int, Student> $candidates */
        $candidates = $query->get();

        $validStudents = [];
        $missingParticipantNumbers = [];
        $missingRooms = [];
        $unrecognizedClasses = [];
        $distinctRooms = [];
        $participantNumberCounts = [];

        foreach ($candidates as $student) {
            $pNum = trim((string) ($student->participant_number ?? ''));
            if ($pNum === '') {
                $missingParticipantNumbers[] = [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'class_name' => $student->class_name ?: '-',
                ];
                continue;
            }

            $participantNumberCounts[$pNum] = ($participantNumberCounts[$pNum] ?? 0) + 1;
            $gradeLevel = $this->extractGradeLevel($student->class_name);

            if ($gradeLevel === null) {
                $unrecognizedClasses[] = [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'class_name' => $student->class_name ?: '(Kosong)',
                    'participant_number' => $pNum,
                ];
            }

            $roomName = trim((string) ($student->room_name ?? ''));
            if ($roomName === '') {
                $missingRooms[] = [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'class_name' => $student->class_name ?: '-',
                    'participant_number' => $pNum,
                ];
            } else {
                $distinctRooms[$roomName] = ($distinctRooms[$roomName] ?? 0) + 1;
            }

            $validStudents[] = [
                'student' => $student,
                'participant_number' => $pNum,
                'grade_level' => $gradeLevel,
                'room_name' => $roomName,
            ];
        }

        $duplicateParticipantNumbers = [];
        foreach ($participantNumberCounts as $num => $count) {
            if ($count > 1) {
                $duplicateParticipantNumbers[] = $num . ' (' . $count . ' siswa)';
            }
        }

        $gradeSummary = [];
        foreach ($validStudents as $item) {
            $key = $item['grade_level'] !== null ? 'Tingkat ' . $item['grade_level'] : 'Tingkat Belum Dikenali';
            $gradeSummary[$key] = ($gradeSummary[$key] ?? 0) + 1;
        }
        ksort($gradeSummary);
        uksort($distinctRooms, 'strnatcasecmp');

        return [
            'total_queried' => $candidates->count(),
            'valid_students' => $validStudents,
            'valid_count' => count($validStudents),
            'missing_participant_numbers' => $missingParticipantNumbers,
            'duplicate_participant_numbers' => $duplicateParticipantNumbers,
            'missing_rooms' => $missingRooms,
            'unrecognized_classes' => $unrecognizedClasses,
            'grade_summary' => $gradeSummary,
            'selected_classes' => $cleanClasses,
            'distinct_rooms' => $distinctRooms,
        ];
    }

    /**
     * Mengacak pasangan lintas tingkat kelas di dalam masing-masing ruangan dari modul Pengaturan Ruang Ujian,
     * serta mereset nomor urut kursi (1..N) dan nomor meja (Meja 1..M) pada setiap ruangan.
     */
    public function generateAndSavePlan(ExamSession $examSession, array $options = []): SeatingPlan
    {
        $selectionMode = ($options['selection_mode'] ?? 'all') === 'classes' ? 'classes' : 'all';
        $selectedClasses = is_array($options['selected_classes'] ?? null) ? $options['selected_classes'] : [];
        $startingSeatNumber = max(1, (int) ($options['starting_seat_number'] ?? 1));
        $seatsPerDesk = 2;

        $inspection = $this->inspectCandidates($examSession, $selectionMode, $selectedClasses);

        if (! empty($inspection['duplicate_participant_numbers'])) {
            throw new RuntimeException(
                'Terdapat nomor peserta duplikat pada daftar terpilih: ' .
                implode(', ', $inspection['duplicate_participant_numbers']) .
                '. Perbaiki duplikasi pada menu Kelola Data Siswa terlebih dahulu.'
            );
        }

        if ($inspection['valid_count'] === 0) {
            throw new RuntimeException(
                'Tidak ada peserta valid yang memiliki nomor peserta untuk dibuatkan nomor meja.'
            );
        }

        if (! empty($inspection['missing_rooms'])) {
            throw new RuntimeException(
                'Terdapat ' . count($inspection['missing_rooms']) .
                ' peserta yang belum memiliki ruangan ujian. Silakan bagi ruangan terlebih dahulu melalui menu "Pengaturan Ruang Ujian".'
            );
        }

        $warnings = [];
        if (! empty($inspection['missing_participant_numbers'])) {
            $warnings[] = count($inspection['missing_participant_numbers']) .
                ' siswa dilewati karena belum memiliki nomor peserta.';
        }

        if (! empty($inspection['unrecognized_classes'])) {
            $warnings[] = count($inspection['unrecognized_classes']) .
                ' siswa memiliki nama kelas yang belum dapat dikenali tingkatannya.';
        }

        // Kelompokkan siswa berdasarkan nama ruangan eksisting dari modul Pengaturan Ruang Ujian
        $groupedByRoom = [];
        foreach ($inspection['valid_students'] as $item) {
            $groupedByRoom[$item['room_name']][] = $item;
        }
        uksort($groupedByRoom, 'strnatcasecmp');

        $roomBatches = [];
        $totalCrossGrade = 0;
        $totalSameGrade = 0;
        $totalSingleSeat = 0;
        $roomNumber = 1;

        foreach ($groupedByRoom as $roomName => $roomStudents) {
            $paired = $this->pairStudentsCrossGrade($roomStudents);
            $totalCrossGrade += $paired['cross_grade_pairs'];
            $totalSameGrade += $paired['same_grade_pairs'];
            $totalSingleSeat += $paired['single_seat_desks'];

            $roomBatches[] = [
                'room_number' => $roomNumber,
                'room_name' => $roomName,
                'desk_units' => $paired['desk_units'],
            ];
            $roomNumber++;
        }

        $recognizedGrades = [];
        foreach ($inspection['valid_students'] as $item) {
            if ($item['grade_level'] !== null) {
                $recognizedGrades[$item['grade_level']] = true;
            }
        }

        if (count($recognizedGrades) === 1) {
            $onlyGrade = array_key_first($recognizedGrades);
            $warnings[] = 'Peserta terpilih hanya berasal dari 1 tingkat kelas (Tingkat ' . $onlyGrade .
                '), sehingga pasangan lintas tingkat kelas tidak dapat diterapkan.';
        } elseif (count($recognizedGrades) > 1 && $totalSameGrade > 0) {
            $warnings[] = 'Terdapat ' . $totalSameGrade .
                ' meja berisi pasangan sesama tingkat kelas karena komposisi tingkat kelas di dalam ruangan tidak seimbang.';
        }

        // Hitung nomor urut kursi & nomor meja (di-reset mulai dari startingSeatNumber di setiap ruangan)
        $assignmentPayloads = [];
        $totalDesks = 0;
        $now = now();

        foreach ($roomBatches as $batch) {
            $currentSeatNumber = $startingSeatNumber;
            $firstDeskNumber = (int) ceil($startingSeatNumber / $seatsPerDesk);

            foreach ($batch['desk_units'] as $deskIndex => $unit) {
                $deskNumber = $firstDeskNumber + $deskIndex;
                $totalDesks++;

                foreach ($unit as $posIndex => $entry) {
                    /** @var Student $studentModel */
                    $studentModel = $entry['student'];
                    $pNum = $entry['participant_number'];
                    $seatPosition = $posIndex + 1;
                    $deskParticipantNumber = $pNum . '.' . $currentSeatNumber;

                    $assignmentPayloads[] = [
                        'student_id' => $studentModel->id,
                        'participant_number_snapshot' => $pNum,
                        'student_name_snapshot' => (string) $studentModel->student_name,
                        'class_name_snapshot' => (string) $studentModel->class_name,
                        'grade_level' => $entry['grade_level'],
                        'room_name' => $batch['room_name'],
                        'room_number' => $batch['room_number'],
                        'seat_number' => $currentSeatNumber,
                        'desk_number' => $deskNumber,
                        'seat_position' => $seatPosition,
                        'desk_participant_number' => $deskParticipantNumber,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $currentSeatNumber++;
                }
            }
        }

        return DB::transaction(function () use (
            $examSession,
            $selectionMode,
            $inspection,
            $seatsPerDesk,
            $startingSeatNumber,
            $totalDesks,
            $roomBatches,
            $totalCrossGrade,
            $totalSameGrade,
            $totalSingleSeat,
            $warnings,
            $assignmentPayloads,
            $now
        ): SeatingPlan {
            $previousLatest = $examSession->seatingPlans()->first();
            $nextVersion = $previousLatest ? ($previousLatest->version_number + 1) : 1;

            $examSession->seatingPlans()
                ->where('status', 'draft')
                ->update(['status' => 'archived']);

            $inheritedLayout = $previousLatest
                ? $previousLatest->resolvedLayoutSettings()
                : SeatingPlan::defaultLayoutSettings();

            $plan = SeatingPlan::create([
                'exam_session_id' => $examSession->id,
                'version_number' => $nextVersion,
                'status' => 'draft',
                'selection_mode' => $selectionMode,
                'selected_classes_json' => $selectionMode === 'classes' ? $inspection['selected_classes'] : null,
                'seats_per_desk' => $seatsPerDesk,
                'starting_seat_number' => $startingSeatNumber,
                'total_students' => count($assignmentPayloads),
                'total_desks' => $totalDesks,
                'total_rooms' => count($roomBatches),
                'cross_grade_pairs' => $totalCrossGrade,
                'same_grade_pairs' => $totalSameGrade,
                'single_seat_desks' => $totalSingleSeat,
                'warnings_json' => $warnings,
                'layout_settings_json' => $inheritedLayout,
                'generated_at' => $now,
            ]);

            foreach ($assignmentPayloads as &$row) {
                $row['seating_plan_id'] = $plan->id;
            }
            unset($row);

            foreach (array_chunk($assignmentPayloads, 500) as $chunk) {
                SeatingAssignment::insert($chunk);
            }

            return $plan->fresh(['assignments']);
        });
    }

    /**
     * Memasangkan daftar peserta secara maksimal lintas tingkat kelas di dalam satu ruangan.
     */
    private function pairStudentsCrossGrade(array $students): array
    {
        $buckets = [];
        foreach ($students as $item) {
            $bucketKey = $item['grade_level'] !== null ? 'G_' . $item['grade_level'] : 'UNKNOWN';
            $buckets[$bucketKey][] = $item;
        }

        foreach ($buckets as $key => $items) {
            shuffle($buckets[$key]);
        }

        $pairedDeskUnits = [];
        $singleDeskUnit = null;
        $crossGradePairs = 0;
        $sameGradePairs = 0;
        $singleSeatDesks = 0;

        while ($this->countRemainingStudents($buckets) >= 2) {
            $sortedKeys = $this->getNonEmptyBucketKeysSortedByCountDesc($buckets);
            $primaryKey = $sortedKeys[0];
            $firstStudent = array_pop($buckets[$primaryKey]);

            if (count($sortedKeys) >= 2) {
                $secondaryKey = $sortedKeys[1];
                $secondStudent = array_pop($buckets[$secondaryKey]);

                $isVerifiedCrossGrade = ($firstStudent['grade_level'] !== null)
                    && ($secondStudent['grade_level'] !== null)
                    && ($firstStudent['grade_level'] !== $secondStudent['grade_level']);

                if ($isVerifiedCrossGrade) {
                    $crossGradePairs++;
                } else {
                    $sameGradePairs++;
                }

                $pairedDeskUnits[] = [$firstStudent, $secondStudent];
            } else {
                $secondStudent = array_pop($buckets[$primaryKey]);
                $sameGradePairs++;
                $pairedDeskUnits[] = [$firstStudent, $secondStudent];
            }
        }

        if ($this->countRemainingStudents($buckets) === 1) {
            $sortedKeys = $this->getNonEmptyBucketKeysSortedByCountDesc($buckets);
            $lastStudent = array_pop($buckets[$sortedKeys[0]]);
            $singleSeatDesks++;
            $singleDeskUnit = [$lastStudent];
        }

        shuffle($pairedDeskUnits);
        $deskUnits = $pairedDeskUnits;
        if ($singleDeskUnit !== null) {
            $deskUnits[] = $singleDeskUnit;
        }

        return [
            'desk_units' => $deskUnits,
            'cross_grade_pairs' => $crossGradePairs,
            'same_grade_pairs' => $sameGradePairs,
            'single_seat_desks' => $singleSeatDesks,
        ];
    }

    /**
     * Memfinalisasi versi rencana penempatan sehingga menjadi acuan resmi cetak & denah.
     */
    public function finalizePlan(SeatingPlan $plan): SeatingPlan
    {
        return DB::transaction(function () use ($plan): SeatingPlan {
            SeatingPlan::where('exam_session_id', $plan->exam_session_id)
                ->where('id', '!=', $plan->id)
                ->where('status', 'finalized')
                ->update(['status' => 'archived']);

            $plan->update([
                'status' => 'finalized',
                'finalized_at' => now(),
            ]);

            return $plan->fresh(['assignments']);
        });
    }

    private function countRemainingStudents(array $buckets): int
    {
        $total = 0;
        foreach ($buckets as $items) {
            $total += count($items);
        }

        return $total;
    }

    private function getNonEmptyBucketKeysSortedByCountDesc(array $buckets): array
    {
        $counts = [];
        foreach ($buckets as $key => $items) {
            $cnt = count($items);
            if ($cnt > 0) {
                $counts[$key] = $cnt;
            }
        }

        $keys = array_keys($counts);
        shuffle($keys);

        usort($keys, static fn (string $a, string $b): int => $counts[$b] <=> $counts[$a]);

        return $keys;
    }
}