<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamRoomAssignmentService
{
    /**
     * Menyusun pratinjau pembagian ruang ujian tanpa langsung mengubah database.
     */
    public function buildPreview(ExamSession $examSession, array $config): array
    {
        $rawPrefix = trim((string) ($config['room_prefix'] ?? 'Ruang'));
        if ($rawPrefix !== '' and ctype_alnum(substr($rawPrefix, -1))) {
            $rawPrefix .= ' ';
        }

        $startNumber = max(1, (int) ($config['start_number'] ?? 1));
        $digitLength = min(4, max(1, (int) ($config['digit_length'] ?? 2)));

        $allowedAllocationModes = ['by_capacity', 'by_room_count', 'custom_per_room'];
        $allocationMode = in_array($config['allocation_mode'] ?? 'by_capacity', $allowedAllocationModes, true)
            ? (string) $config['allocation_mode']
            : 'by_capacity';

        $capacityPerRoom = max(1, (int) ($config['capacity_per_room'] ?? 20));
        $totalRoomsInput = max(1, (int) ($config['total_rooms'] ?? 10));

        $allowedMethods = ['random', 'random_per_class', 'sequential'];
        $distributionMethod = in_array($config['distribution_method'] ?? 'random', $allowedMethods, true)
            ? (string) $config['distribution_method']
            : 'random';

        $targetScope = ($config['target_scope'] ?? 'empty_only') === 'all'
            ? 'all'
            : 'empty_only';

        $query = $examSession->students()
            ->getQuery()
            ->whereNotNull('student_name')
            ->where('student_name', '!=', '')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '');

        if ($targetScope === 'empty_only') {
            $query->where(function ($q) {
                $q->whereNull('room_name')
                    ->orWhere('room_name', '');
            });
        }

        $students = $query
            ->orderBy('class_name')
            ->orderBy('student_name')
            ->orderBy('id')
            ->get(['id', 'participant_number', 'student_name', 'class_name', 'room_name']);

        if ($students->isEmpty()) {
            if ($targetScope === 'empty_only') {
                throw new RuntimeException('Tidak ada siswa valid dengan kolom Ruang Ujian kosong. Pilih cakupan "Atur ulang semua siswa valid" jika ingin mengatur ulang seluruh ruangan.');
            }

            throw new RuntimeException('Tidak ditemukan data siswa valid pada sesi ujian ini.');
        }

        $orderedStudents = $this->arrangeStudents($students->all(), $distributionMethod);
        $totalTarget = count($orderedStudents);

        $roomBucketsWithMeta = [];
        $normalizedCustomRooms = [];

        if ($allocationMode === 'custom_per_room') {
            $rawCustomRooms = is_array($config['custom_rooms'] ?? null) ? $config['custom_rooms'] : [];
            $seenRoomNames = [];
            $totalCustomCapacity = 0;

            foreach ($rawCustomRooms as $row) {
                $rName = trim((string) ($row['name'] ?? ''));
                $rCap = (int) ($row['capacity'] ?? 0);

                if ($rName === '' and $rCap <= 0) {
                    continue;
                }

                if ($rName === '' or $rCap < 1) {
                    throw new RuntimeException('Setiap baris ruangan kustom wajib memiliki nama ruang dan kapasitas minimal 1 siswa.');
                }

                $lowerKey = mb_strtolower($rName);
                if (isset($seenRoomNames[$lowerKey])) {
                    throw new RuntimeException('Terdapat nama ruang ganda pada daftar kapasitas ruangan: "' . $rName . '". Gunakan nama ruang yang unik.');
                }
                $seenRoomNames[$lowerKey] = true;

                $normalizedCustomRooms[] = [
                    'name' => $rName,
                    'capacity' => $rCap,
                ];
                $totalCustomCapacity += $rCap;
            }

            if (empty($normalizedCustomRooms)) {
                throw new RuntimeException('Masukkan minimal 1 ruangan beserta kapasitas siswanya.');
            }

            if ($totalCustomCapacity < $totalTarget) {
                $shortage = $totalTarget - $totalCustomCapacity;
                throw new RuntimeException(
                    'Total kapasitas ruangan yang diatur (' . number_format($totalCustomCapacity, 0, ',', '.') .
                    ' kursi) masih kurang dari jumlah siswa target (' . number_format($totalTarget, 0, ',', '.') .
                    ' siswa). Tambahkan ruangan atau perbesar kapasitas sebesar ' . number_format($shortage, 0, ',', '.') . ' kursi lagi.'
                );
            }

            $cursor = 0;
            foreach ($normalizedCustomRooms as $roomDef) {
                if ($cursor >= $totalTarget) {
                    break;
                }
                $take = min($roomDef['capacity'], $totalTarget - $cursor);
                $bucket = array_slice($orderedStudents, $cursor, $take);
                $cursor += $take;

                $roomBucketsWithMeta[] = [
                    'room_name' => $roomDef['name'],
                    'capacity' => $roomDef['capacity'],
                    'students' => $bucket,
                ];
            }
        } elseif ($allocationMode === 'by_capacity') {
            $chunks = array_chunk($orderedStudents, $capacityPerRoom);
            foreach ($chunks as $index => $bucket) {
                $roomNumber = $startNumber + $index;
                $formattedNumber = str_pad((string) $roomNumber, $digitLength, '0', STR_PAD_LEFT);
                $roomBucketsWithMeta[] = [
                    'room_name' => $rawPrefix . $formattedNumber,
                    'capacity' => $capacityPerRoom,
                    'students' => $bucket,
                ];
            }
        } else {
            $actualRooms = min($totalRoomsInput, $totalTarget);
            $baseCount = intdiv($totalTarget, $actualRooms);
            $remainder = $totalTarget % $actualRooms;

            $cursor = 0;
            for ($i = 0; $i < $actualRooms; $i++) {
                $take = $baseCount + ($i < $remainder ? 1 : 0);
                $bucket = array_slice($orderedStudents, $cursor, $take);
                $cursor += $take;

                $roomNumber = $startNumber + $i;
                $formattedNumber = str_pad((string) $roomNumber, $digitLength, '0', STR_PAD_LEFT);
                $roomBucketsWithMeta[] = [
                    'room_name' => $rawPrefix . $formattedNumber,
                    'capacity' => $take,
                    'students' => $bucket,
                ];
            }
        }

        $assignments = [];
        $roomSummary = [];
        $sampleRows = [];

        foreach ($roomBucketsWithMeta as $meta) {
            $roomLabel = $meta['room_name'];
            $bucket = $meta['students'];

            $classCounts = [];
            foreach ($bucket as $student) {
                $assignments[$student->id] = $roomLabel;
                $cls = (string) ($student->class_name ?: '-');
                $classCounts[$cls] = ($classCounts[$cls] ?? 0) + 1;

                if (count($sampleRows) < 25) {
                    $sampleRows[] = [
                        'participant_number' => $student->participant_number ?: '-',
                        'student_name' => $student->student_name,
                        'class_name' => $cls,
                        'old_room_name' => $student->room_name ?: '(Kosong)',
                        'new_room_name' => $roomLabel,
                    ];
                }
            }

            ksort($classCounts);
            $classBreakdown = [];
            foreach ($classCounts as $clsName => $cnt) {
                $classBreakdown[] = $clsName . ' (' . $cnt . ')';
            }

            $roomSummary[] = [
                'room_name' => $roomLabel,
                'student_count' => count($bucket),
                'capacity' => $meta['capacity'],
                'classes_text' => implode(', ', $classBreakdown),
            ];
        }

        return [
            'config' => [
                'room_prefix' => trim((string) ($config['room_prefix'] ?? 'Ruang')),
                'start_number' => $startNumber,
                'digit_length' => $digitLength,
                'allocation_mode' => $allocationMode,
                'capacity_per_room' => $capacityPerRoom,
                'total_rooms' => $totalRoomsInput,
                'custom_rooms' => $normalizedCustomRooms,
                'distribution_method' => $distributionMethod,
                'target_scope' => $targetScope,
            ],
            'assignments' => $assignments,
            'room_summary' => $roomSummary,
            'sample_rows' => $sampleRows,
            'total_assigned' => count($assignments),
            'total_rooms_used' => count($roomSummary),
        ];
    }

    /**
     * Menerapkan peta alokasi [student_id => room_name] ke database secara berkelompok per ruangan.
     */
    public function applyAssignments(ExamSession $examSession, array $assignments): int
    {
        if (empty($assignments)) {
            return 0;
        }

        $groupedByRoom = [];
        foreach ($assignments as $studentId => $roomName) {
            $id = (int) $studentId;
            $cleanRoom = trim((string) $roomName);
            if ($id > 0 and $cleanRoom !== '') {
                $groupedByRoom[$cleanRoom][] = $id;
            }
        }

        $updatedCount = 0;

        DB::transaction(function () use ($examSession, $groupedByRoom, &$updatedCount) {
            foreach ($groupedByRoom as $roomName => $studentIds) {
                foreach (array_chunk($studentIds, 500) as $idChunk) {
                    $affected = Student::where('exam_session_id', $examSession->id)
                        ->whereIn('id', $idChunk)
                        ->update(['room_name' => $roomName]);
                    $updatedCount += $affected;
                }
            }
        });

        return $updatedCount;
    }

    /**
     * Mengosongkan kembali kolom ruang ujian seluruh siswa pada sesi ini.
     */
    public function clearAllRooms(ExamSession $examSession): int
    {
        return Student::where('exam_session_id', $examSession->id)
            ->whereNotNull('room_name')
            ->where('room_name', '!=', '')
            ->update(['room_name' => null]);
    }

    private function arrangeStudents(array $students, string $method): array
    {
        if ($method === 'sequential') {
            return $students;
        }

        if ($method === 'random_per_class') {
            $byClass = [];
            foreach ($students as $student) {
                $cls = (string) ($student->class_name ?: '-');
                $byClass[$cls][] = $student;
            }

            $queues = [];
            foreach ($byClass as $classStudents) {
                shuffle($classStudents);
                $queues[] = $classStudents;
            }

            $interleaved = [];
            $hasRemaining = true;
            $index = 0;

            while ($hasRemaining) {
                $hasRemaining = false;
                foreach ($queues as $queue) {
                    if (isset($queue[$index])) {
                        $interleaved[] = $queue[$index];
                        $hasRemaining = true;
                    }
                }
                $index++;
            }

            return $interleaved;
        }

        // Metode 'random': acak seluruh urutan siswa sebelum dibagi ke tiap ruang
        shuffle($students);

        return $students;
    }
}