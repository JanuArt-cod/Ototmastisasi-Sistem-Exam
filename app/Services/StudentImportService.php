<?php

namespace App\Services;

use App\Models\ExamSession;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StudentImportService
{
    private const INSERT_CHUNK_SIZE = 500;

    public function __construct(
        private readonly SpreadsheetReaderService $readerService,
        private readonly StudentValidationService $validationService
    ) {}

    /**
     * Membaca dan memvalidasi file sementara untuk ditampilkan pada halaman pratinjau sebelum simpan.
     */
    public function previewFromFile(
        string $fullPath,
        ExamSession $examSession,
        array $columnMapping,
        int $sheetIndex = 0,
        int $headerRowNumber = 1,
        ?string $csvDelimiter = null,
        array $validationOptions = []
    ): array {
        $mappedRows = $this->readerService->streamMappedRows(
            $fullPath,
            $columnMapping,
            $sheetIndex,
            $headerRowNumber,
            $csvDelimiter
        );

        return $this->validationService->validateRows(
            $mappedRows,
            $examSession,
            $validationOptions
        );
    }

    /**
     * Menyimpan baris siswa yang lolos validasi ke dalam database secara transaksional per 500 baris.
     */
    public function commitImport(
        string $fullPath,
        ExamSession $examSession,
        array $columnMapping,
        int $sheetIndex = 0,
        int $headerRowNumber = 1,
        ?string $csvDelimiter = null,
        array $validationOptions = [],
        bool $allowPartialSkipErrors = true
    ): array {
        $mappedRows = $this->readerService->streamMappedRows(
            $fullPath,
            $columnMapping,
            $sheetIndex,
            $headerRowNumber,
            $csvDelimiter
        );

        // Gunakan preview_limit = 0 saat commit agar tidak menyimpan salinan preview ekstra di RAM
        $optionsForCommit = array_merge($validationOptions, ['preview_limit' => 0]);

        $checkDuplicateNis = (bool) ($optionsForCommit['check_duplicate_nis'] ?? true);
        $checkDuplicateNisn = (bool) ($optionsForCommit['check_duplicate_nisn'] ?? true);

        $existingParticipantNumbers = $this->loadExistingLookup($examSession, 'participant_number');
        $existingNis = $checkDuplicateNis ? $this->loadExistingLookup($examSession, 'nis') : [];
        $existingNisn = $checkDuplicateNisn ? $this->loadExistingLookup($examSession, 'nisn') : [];

        $seenParticipant = [];
        $seenNis = [];
        $seenNisn = [];

        $recordsToInsert = [];
        $skippedErrorRows = 0;
        $now = now();

        foreach ($mappedRows as $item) {
            if ($item['is_empty']) {
                continue;
            }

            $data = $item['data'];
            $studentName = trim((string) ($data['student_name'] ?? ''));
            $className = trim((string) ($data['class_name'] ?? ''));
            $nis = trim((string) ($data['nis'] ?? ''));
            $nisn = trim((string) ($data['nisn'] ?? ''));
            $participantNumber = trim((string) ($data['participant_number'] ?? ''));
            $roomName = trim((string) ($data['room_name'] ?? ''));
            $internalNote = trim((string) ($data['internal_note'] ?? ''));

            $isValid = true;

            if ($studentName === '' || mb_strlen($studentName) > 255) {
                $isValid = false;
            }

            if ($className === '' || mb_strlen($className) > 100) {
                $isValid = false;
            }

            if ($isValid && $participantNumber !== '') {
                $key = mb_strtolower($participantNumber);
                if (isset($existingParticipantNumbers[$key]) || isset($seenParticipant[$key])) {
                    $isValid = false;
                } else {
                    $seenParticipant[$key] = true;
                }
            }

            if ($isValid && $checkDuplicateNis && $nis !== '') {
                $key = mb_strtolower($nis);
                if (isset($existingNis[$key]) || isset($seenNis[$key])) {
                    $isValid = false;
                } else {
                    $seenNis[$key] = true;
                }
            }

            if ($isValid && $checkDuplicateNisn && $nisn !== '') {
                $key = mb_strtolower($nisn);
                if (isset($existingNisn[$key]) || isset($seenNisn[$key])) {
                    $isValid = false;
                } else {
                    $seenNisn[$key] = true;
                }
            }

            if (! $isValid) {
                if (! $allowPartialSkipErrors) {
                    throw new RuntimeException('Impor dibatalkan karena masih ditemukan data tidak valid atau duplikat pada baris ke-' . $item['source_row'] . '.');
                }
                $skippedErrorRows++;
                continue;
            }

            $recordsToInsert[] = [
                'exam_session_id' => $examSession->id,
                'student_name' => $studentName,
                'nis' => $nis !== '' ? $nis : null,
                'nisn' => $nisn !== '' ? $nisn : null,
                'class_name' => $className,
                'participant_number' => $participantNumber !== '' ? $participantNumber : null,
                'room_name' => $roomName !== '' ? $roomName : null,
                'photo_path' => null,
                'internal_note' => $internalNote !== '' ? $internalNote : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($recordsToInsert)) {
            throw new RuntimeException('Tidak ada baris data siswa yang valid untuk disimpan.');
        }

        DB::transaction(function () use ($recordsToInsert, $examSession) {
            foreach (array_chunk($recordsToInsert, self::INSERT_CHUNK_SIZE) as $chunk) {
                DB::table('students')->insert($chunk);
            }

            $examSession->touch();
        });

        return [
            'inserted_count' => count($recordsToInsert),
            'skipped_error_rows' => $skippedErrorRows,
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
}