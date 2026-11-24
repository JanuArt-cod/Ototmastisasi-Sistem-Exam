<?php

namespace App\Services;

use App\Models\ExamSession;

class StudentValidationService
{
    private const ALLOWED_PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private readonly PrivateStorageService $storageService
    ) {}

    /**
     * Memvalidasi sekumpulan baris data siswa hasil pembacaan spreadsheet.
     */
    public function validateRows(
        iterable $mappedRows,
        ExamSession $examSession,
        array $options = []
    ): array {
        $checkDuplicateNis = (bool) ($options['check_duplicate_nis'] ?? true);
        $checkDuplicateNisn = (bool) ($options['check_duplicate_nisn'] ?? true);
        $previewLimit = (int) ($options['preview_limit'] ?? 50);

        // Ambil nomor identitas yang sudah ada di sesi aktif agar tidak bentrok saat impor tambahan
        $existingParticipantNumbers = $this->loadExistingLookup($examSession, 'participant_number');
        $existingNis = $checkDuplicateNis ? $this->loadExistingLookup($examSession, 'nis') : [];
        $existingNisn = $checkDuplicateNisn ? $this->loadExistingLookup($examSession, 'nisn') : [];

        $seenParticipantInFile = [];
        $seenNisInFile = [];
        $seenNisnInFile = [];

        $totalNonEmptyRows = 0;
        $emptyRowsSkipped = 0;
        $validCount = 0;
        $errorCount = 0;
        $warningCount = 0;
        $duplicateCount = 0;

        $previewRows = [];
        $problemRows = [];

        foreach ($mappedRows as $item) {
            if ($item['is_empty']) {
                $emptyRowsSkipped++;
                continue;
            }

            $totalNonEmptyRows++;
            $sourceRow = $item['source_row'];
            $data = $this->sanitizeStudentFields($item['data']);

            $rowErrors = [];
            $rowWarnings = [];
            $hasDuplicate = false;

            // 1. Validasi kolom wajib
            if ($data['student_name'] === '') {
                $rowErrors[] = 'Nama siswa wajib diisi.';
            } elseif (mb_strlen($data['student_name']) > 255) {
                $rowErrors[] = 'Nama siswa terlalu panjang (maksimal 255 karakter).';
            }

            if ($data['class_name'] === '') {
                $rowErrors[] = 'Kelas / Rombel wajib diisi.';
            } elseif (mb_strlen($data['class_name']) > 100) {
                $rowErrors[] = 'Nama kelas maksimal 100 karakter.';
            }

            // 2. Pemeriksaan format notasi ilmiah (misal akibat format angka Excel)
            foreach (['nis' => 'NIS', 'nisn' => 'NISN', 'participant_number' => 'Nomor Peserta'] as $field => $label) {
                if ($data[$field] !== '' && preg_match('/^[0-9.,]+[eE][+-]?[0-9]+$/', $data[$field])) {
                    $rowErrors[] = $label . ' terbaca dalam format eksponen (' . $data[$field] . '). Ubah format sel di Excel menjadi Text.';
                }
            }

            // 3. Pemeriksaan duplikasi Nomor Peserta
            if ($data['participant_number'] !== '') {
                $key = mb_strtolower($data['participant_number']);
                if (isset($existingParticipantNumbers[$key])) {
                    $rowErrors[] = 'Nomor peserta "' . $data['participant_number'] . '" sudah terdaftar pada sesi ini.';
                    $hasDuplicate = true;
                } elseif (isset($seenParticipantInFile[$key])) {
                    $rowErrors[] = 'Nomor peserta "' . $data['participant_number'] . '" duplikat dengan baris ke-' . $seenParticipantInFile[$key] . '.';
                    $hasDuplicate = true;
                } else {
                    $seenParticipantInFile[$key] = $sourceRow;
                }
            }

            // 4. Pemeriksaan duplikasi NIS
            if ($checkDuplicateNis && $data['nis'] !== '') {
                $key = mb_strtolower($data['nis']);
                if (isset($existingNis[$key])) {
                    $rowErrors[] = 'NIS "' . $data['nis'] . '" sudah terdaftar pada sesi ini.';
                    $hasDuplicate = true;
                } elseif (isset($seenNisInFile[$key])) {
                    $rowErrors[] = 'NIS "' . $data['nis'] . '" duplikat dengan baris ke-' . $seenNisInFile[$key] . '.';
                    $hasDuplicate = true;
                } else {
                    $seenNisInFile[$key] = $sourceRow;
                }
            }

            // 5. Pemeriksaan duplikasi NISN
            if ($checkDuplicateNisn && $data['nisn'] !== '') {
                $key = mb_strtolower($data['nisn']);
                if (isset($existingNisn[$key])) {
                    $rowErrors[] = 'NISN "' . $data['nisn'] . '" sudah terdaftar pada sesi ini.';
                    $hasDuplicate = true;
                } elseif (isset($seenNisnInFile[$key])) {
                    $rowErrors[] = 'NISN "' . $data['nisn'] . '" duplikat dengan baris ke-' . $seenNisnInFile[$key] . '.';
                    $hasDuplicate = true;
                } else {
                    $seenNisnInFile[$key] = $sourceRow;
                }
            }

            // 6. Pemeriksaan foto opsional
            $resolvedPhotoPath = null;
            if ($data['photo_filename'] !== '') {
                $photoCheck = $this->checkPhotoReference($data['photo_filename']);
                if ($photoCheck['valid']) {
                    $resolvedPhotoPath = $photoCheck['relative_path'];
                } else {
                    $rowWarnings[] = $photoCheck['message'];
                }
            }

            $status = 'valid';
            if (! empty($rowErrors)) {
                $status = 'error';
                $errorCount++;
                if ($hasDuplicate) {
                    $duplicateCount++;
                }
            } else {
                $validCount++;
                if (! empty($rowWarnings)) {
                    $status = 'warning';
                    $warningCount++;
                }
            }

            $evaluatedRow = [
                'source_row' => $sourceRow,
                'status' => $status,
                'data' => $data,
                'resolved_photo_path' => $resolvedPhotoPath,
                'errors' => $rowErrors,
                'warnings' => $rowWarnings,
            ];

            if (count($previewRows) < $previewLimit) {
                $previewRows[] = $evaluatedRow;
            }

            if ($status !== 'valid') {
                $problemRows[] = $evaluatedRow;
            }
        }

        return [
            'summary' => [
                'total_non_empty_rows' => $totalNonEmptyRows,
                'valid_count' => $validCount,
                'error_count' => $errorCount,
                'warning_count' => $warningCount,
                'duplicate_count' => $duplicateCount,
                'empty_rows_skipped' => $emptyRowsSkipped,
            ],
            'preview_rows' => $previewRows,
            'problem_rows' => $problemRows,
        ];
    }

    private function sanitizeStudentFields(array $raw): array
    {
        return [
            'student_name' => trim((string) ($raw['student_name'] ?? '')),
            'nis' => trim((string) ($raw['nis'] ?? '')),
            'nisn' => trim((string) ($raw['nisn'] ?? '')),
            'class_name' => trim((string) ($raw['class_name'] ?? '')),
            'participant_number' => trim((string) ($raw['participant_number'] ?? '')),
            'room_name' => trim((string) ($raw['room_name'] ?? '')),
            'photo_filename' => trim((string) ($raw['photo_filename'] ?? '')),
            'internal_note' => trim((string) ($raw['internal_note'] ?? '')),
        ];
    }

    private function loadExistingLookup(ExamSession $examSession, string $column): array
    {
        $values = $examSession->students()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->pluck($column)
            ->all();

        $lookup = [];
        foreach ($values as $value) {
            $lookup[mb_strtolower((string) $value)] = true;
        }

        return $lookup;
    }

    private function checkPhotoReference(string $photoFilename): array
    {
        $cleaned = basename(str_replace('\\', '/', $photoFilename));
        $extension = strtolower(pathinfo($cleaned, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_PHOTO_EXTENSIONS, true)) {
            return [
                'valid' => false,
                'relative_path' => null,
                'message' => 'Format foto "' . $cleaned . '" tidak didukung (gunakan JPG, PNG, atau WEBP).',
            ];
        }

        $relativePath = PrivateStorageService::DIR_STUDENT_PHOTOS . '/' . $cleaned;
        if (! $this->storageService->exists($relativePath)) {
            return [
                'valid' => false,
                'relative_path' => null,
                'message' => 'File foto "' . $cleaned . '" belum tersedia di folder foto siswa.',
            ];
        }

        return [
            'valid' => true,
            'relative_path' => $relativePath,
            'message' => null,
        ];
    }
}