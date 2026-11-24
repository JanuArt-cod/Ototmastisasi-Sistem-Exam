<?php

namespace App\Services;

use App\Models\ExamSession;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ParticipantNumberService
{
    private const UPDATE_CHUNK_SIZE = 500;

    /**
     * Menghitung pratinjau nomor peserta otomatis dan memeriksa potensi konflik tanpa mengubah database.
     */
    public function preview(ExamSession $examSession, array $config): array
    {
        $prefix = (string) ($config['prefix'] ?? '');
        $startNumber = max(0, (int) ($config['start_number'] ?? 1));
        $digitLength = min(12, max(1, (int) ($config['digit_length'] ?? 4)));
        $sortOrder = (string) ($config['sort_order'] ?? 'class_name');
        $targetScope = (string) ($config['target_scope'] ?? 'only_empty');

        $targetStudents = $this->queryTargetStudents($examSession, $targetScope, $sortOrder);
        $totalTarget = count($targetStudents);

        // Jika hanya mengisi yang kosong, kumpulkan nomor peserta yang sudah terpakai di sesi ini
        $existingNumbers = [];
        if ($targetScope === 'only_empty') {
            $existingRecords = $examSession->students()
                ->whereNotNull('participant_number')
                ->where('participant_number', '!=', '')
                ->get(['id', 'student_name', 'participant_number']);

            foreach ($existingRecords as $record) {
                $key = mb_strtolower((string) $record->participant_number);
                $existingNumbers[$key] = $record->student_name . ' (' . $record->participant_number . ')';
            }
        }

        $samples = [];
        $conflicts = [];
        $generatedSet = [];

        foreach ($targetStudents as $index => $student) {
            $sequence = $startNumber + $index;
            $formattedNumber = $prefix . str_pad((string) $sequence, $digitLength, '0', STR_PAD_LEFT);
            $normalizedKey = mb_strtolower($formattedNumber);

            if (mb_strlen($formattedNumber) > 100) {
                $conflicts[] = [
                    'student_name' => $student->student_name,
                    'class_name' => $student->class_name,
                    'generated_number' => $formattedNumber,
                    'reason' => 'Panjang nomor peserta melebihi batas 100 karakter.',
                ];
            } elseif (isset($existingNumbers[$normalizedKey])) {
                $conflicts[] = [
                    'student_name' => $student->student_name,
                    'class_name' => $student->class_name,
                    'generated_number' => $formattedNumber,
                    'reason' => 'Bentrok dengan nomor peserta yang sudah dimiliki oleh ' . $existingNumbers[$normalizedKey] . '.',
                ];
            } elseif (isset($generatedSet[$normalizedKey])) {
                $conflicts[] = [
                    'student_name' => $student->student_name,
                    'class_name' => $student->class_name,
                    'generated_number' => $formattedNumber,
                    'reason' => 'Nomor peserta terduplikasi dalam daftar pembuatan otomatis.',
                ];
            }

            $generatedSet[$normalizedKey] = true;

            // Ambil 8 contoh pertama dan 2 contoh terakhir untuk ditampilkan di pratinjau
            $isFirstEight = $index < 8;
            $isLastTwo = $index >= ($totalTarget - 2);
            if ($isFirstEight or $isLastTwo) {
                $samples[] = [
                    'order' => $index + 1,
                    'student_name' => $student->student_name,
                    'class_name' => $student->class_name,
                    'old_number' => $student->participant_number ?: '(kosong)',
                    'new_number' => $formattedNumber,
                ];
            }
        }

        return [
            'config' => [
                'prefix' => $prefix,
                'start_number' => $startNumber,
                'digit_length' => $digitLength,
                'sort_order' => $sortOrder,
                'target_scope' => $targetScope,
            ],
            'total_target' => $totalTarget,
            'samples' => $samples,
            'conflicts' => $conflicts,
            'has_conflicts' => ! empty($conflicts),
        ];
    }

    /**
     * Menerapkan nomor peserta otomatis ke database secara transaksional apabila tidak ada konflik.
     */
    public function apply(ExamSession $examSession, array $config): int
    {
        $previewResult = $this->preview($examSession, $config);

        if ($previewResult['total_target'] === 0) {
            throw new RuntimeException('Tidak ada siswa yang memenuhi cakupan penomoran otomatis.');
        }

        if ($previewResult['has_conflicts']) {
            $firstConflict = $previewResult['conflicts'][0];
            throw new RuntimeException(
                'Pembuatan nomor otomatis dibatalkan karena ditemukan ' . count($previewResult['conflicts']) .
                ' konflik nomor peserta (contoh: ' . $firstConflict['generated_number'] . ' - ' . $firstConflict['reason'] . ').'
            );
        }

        $prefix = $previewResult['config']['prefix'];
        $startNumber = $previewResult['config']['start_number'];
        $digitLength = $previewResult['config']['digit_length'];
        $sortOrder = $previewResult['config']['sort_order'];
        $targetScope = $previewResult['config']['target_scope'];

        $targetStudents = $this->queryTargetStudents($examSession, $targetScope, $sortOrder);

        $updates = [];
        foreach ($targetStudents as $index => $student) {
            $sequence = $startNumber + $index;
            $formattedNumber = $prefix . str_pad((string) $sequence, $digitLength, '0', STR_PAD_LEFT);

            $updates[] = [
                'id' => (int) $student->id,
                'participant_number' => $formattedNumber,
            ];
        }

        DB::transaction(function () use ($updates, $examSession) {
            $now = now()->toDateTimeString();

            foreach (array_chunk($updates, self::UPDATE_CHUNK_SIZE) as $chunk) {
                $cases = [];
                $bindings = [];
                $ids = [];

                foreach ($chunk as $item) {
                    $cases[] = 'WHEN ? THEN ?';
                    $bindings[] = $item['id'];
                    $bindings[] = $item['participant_number'];
                    $ids[] = $item['id'];
                }

                $bindings[] = $now;
                $bindings = array_merge($bindings, $ids);

                $caseSql = implode(' ', $cases);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                DB::update(
                    "UPDATE students SET participant_number = CASE id {$caseSql} END, updated_at = ? WHERE id IN ({$placeholders})",
                    $bindings
                );
            }

            $examSession->touch();
        });

        return count($updates);
    }

    private function queryTargetStudents(ExamSession $examSession, string $targetScope, string $sortOrder): array
    {
        $query = $examSession->students()->select(['id', 'student_name', 'class_name', 'participant_number']);

        if ($targetScope === 'only_empty') {
            $query->where(function ($q) {
                $q->whereNull('participant_number')
                    ->orWhere('participant_number', '');
            });
        }

        if ($sortOrder === 'student_name') {
            $query->orderBy('student_name')->orderBy('id');
        } elseif ($sortOrder === 'id') {
            $query->orderBy('id');
        } else {
            $query->orderBy('class_name')->orderBy('student_name')->orderBy('id');
        }

        return $query->get()->all();
    }
}