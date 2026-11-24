<?php

namespace App\Services;

use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

class SpreadsheetReaderService
{
    public const SUPPORTED_FIELDS = [
        'student_name' => 'Nama Siswa (Wajib)',
        'class_name' => 'Kelas / Rombel (Wajib)',
        'nis' => 'NIS',
        'nisn' => 'NISN',
        'participant_number' => 'Nomor Peserta',
        'room_name' => 'Ruang Ujian',
        'photo_filename' => 'Nama File Foto',
        'internal_note' => 'Catatan Internal',
    ];

    /**
     * Memeriksa metadata file, daftar sheet, pemisah CSV, dan 15 baris awal untuk pemilihan header.
     */
    public function inspectFile(string $fullPath, int $sheetIndex = 0, ?string $csvDelimiter = null): array
    {
        $extension = $this->detectExtension($fullPath);
        $resolvedDelimiter = $extension === 'csv'
            ? ($csvDelimiter ?: $this->detectCsvDelimiter($fullPath))
            : null;

        $reader = $this->openReader($fullPath, $extension, $resolvedDelimiter);

        $sheets = [];
        $sampleRows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $currentIndex = $sheet->getIndex();
                $sheets[] = [
                    'index' => $currentIndex,
                    'name' => $sheet->getName(),
                ];

                if ($currentIndex === $sheetIndex) {
                    $collected = 0;
                    foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                        $sampleRows[] = [
                            'row_number' => $rowNumber,
                            'cells' => $this->normalizeRowCells($row->toArray()),
                        ];
                        $collected++;
                        if ($collected >= 15) {
                            break;
                        }
                    }
                }
            }
        } finally {
            $reader->close();
        }

        if (empty($sheets)) {
            throw new RuntimeException('File spreadsheet tidak memiliki sheet yang dapat dibaca.');
        }

        return [
            'extension' => $extension,
            'csv_delimiter' => $resolvedDelimiter,
            'sheets' => $sheets,
            'selected_sheet_index' => $sheetIndex,
            'sample_rows' => $sampleRows,
        ];
    }

    /**
     * Mengambil daftar kolom header pada baris tertentu beserta saran pemetaan otomatis.
     */
    public function extractHeadersAndMapping(
        string $fullPath,
        int $sheetIndex = 0,
        int $headerRowNumber = 1,
        ?string $csvDelimiter = null
    ): array {
        $extension = $this->detectExtension($fullPath);
        $resolvedDelimiter = $extension === 'csv'
            ? ($csvDelimiter ?: $this->detectCsvDelimiter($fullPath))
            : null;

        $reader = $this->openReader($fullPath, $extension, $resolvedDelimiter);

        $headers = [];
        $previewDataRows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getIndex() !== $sheetIndex) {
                    continue;
                }

                foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                    if ($rowNumber < $headerRowNumber) {
                        continue;
                    }

                    $cells = $this->normalizeRowCells($row->toArray());

                    if ($rowNumber === $headerRowNumber) {
                        foreach ($cells as $colIndex => $cellValue) {
                            $headers[] = [
                                'index' => $colIndex,
                                'column_letter' => $this->columnIndexToLetter($colIndex),
                                'label' => $cellValue !== '' ? $cellValue : 'Kolom ' . $this->columnIndexToLetter($colIndex),
                            ];
                        }
                        continue;
                    }

                    if (count($previewDataRows) < 5) {
                        $previewDataRows[] = [
                            'row_number' => $rowNumber,
                            'cells' => array_pad($cells, count($headers), ''),
                        ];
                    } else {
                        break;
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }

        if (empty($headers)) {
            throw new RuntimeException('Baris header ke-' . $headerRowNumber . ' tidak ditemukan atau kosong.');
        }

        return [
            'headers' => $headers,
            'suggested_mapping' => $this->suggestColumnMapping($headers),
            'preview_data_rows' => $previewDataRows,
            'csv_delimiter' => $resolvedDelimiter,
        ];
    }

    /**
     * Membaca seluruh baris data setelah header secara bertahap (streaming) sesuai pemetaan kolom.
     */
    public function streamMappedRows(
        string $fullPath,
        array $columnMapping,
        int $sheetIndex = 0,
        int $headerRowNumber = 1,
        ?string $csvDelimiter = null
    ): Generator {
        $extension = $this->detectExtension($fullPath);
        $resolvedDelimiter = $extension === 'csv'
            ? ($csvDelimiter ?: $this->detectCsvDelimiter($fullPath))
            : null;

        $reader = $this->openReader($fullPath, $extension, $resolvedDelimiter);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getIndex() !== $sheetIndex) {
                    continue;
                }

                foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                    if ($rowNumber <= $headerRowNumber) {
                        continue;
                    }

                    $rawCells = $this->normalizeRowCells($row->toArray());
                    $isCompletelyEmpty = $this->isRowEmpty($rawCells);

                    $mapped = [];
                    foreach (array_keys(self::SUPPORTED_FIELDS) as $fieldKey) {
                        $mappedIndex = $columnMapping[$fieldKey] ?? null;
                        if ($mappedIndex !== null && $mappedIndex !== '' && is_numeric($mappedIndex)) {
                            $mapped[$fieldKey] = $rawCells[(int) $mappedIndex] ?? '';
                        } else {
                            $mapped[$fieldKey] = '';
                        }
                    }

                    yield [
                        'source_row' => $rowNumber,
                        'is_empty' => $isCompletelyEmpty,
                        'data' => $mapped,
                    ];
                }

                break;
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Mendeteksi delimiter CSV (titik koma, koma, atau tab) dari baris pertama yang berisi teks.
     */
    public function detectCsvDelimiter(string $fullPath): string
    {
        $handle = fopen($fullPath, 'rb');
        if ($handle === false) {
            return ',';
        }

        $firstLine = '';
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $firstLine = $trimmed;
                break;
            }
        }
        fclose($handle);

        if ($firstLine === '') {
            return ',';
        }

        $candidates = [';', ',', "\t"];
        $bestDelimiter = ',';
        $highestCount = -1;

        foreach ($candidates as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $highestCount) {
                $highestCount = $count;
                $bestDelimiter = $candidate;
            }
        }

        return $bestDelimiter;
    }

    private function suggestColumnMapping(array $headers): array
    {
        $mapping = [];
        foreach (array_keys(self::SUPPORTED_FIELDS) as $fieldKey) {
            $mapping[$fieldKey] = null;
        }

        $patterns = [
            'nisn' => ['nisn'],
            'nis' => ['nis', 'no induk', 'nomor induk', 'no. induk'],
            'student_name' => ['nama', 'nama siswa', 'nama lengkap', 'nama peserta', 'student name'],
            'class_name' => ['kelas', 'rombel', 'kelas/rombel', 'class'],
            'participant_number' => ['no peserta', 'no. peserta', 'nomor peserta', 'no ujian', 'nomor ujian', 'participant'],
            'room_name' => ['ruang', 'ruangan', 'ruang ujian', 'no ruang', 'room'],
            'photo_filename' => ['foto', 'file foto', 'nama foto', 'pas foto', 'photo'],
            'internal_note' => ['catatan', 'keterangan', 'note'],
        ];

        foreach ($headers as $header) {
            $normalizedLabel = mb_strtolower(trim($header['label']));
            $normalizedLabel = preg_replace('/\s+/', ' ', $normalizedLabel);

            foreach ($patterns as $fieldKey => $keywords) {
                if ($mapping[$fieldKey] !== null) {
                    continue;
                }

                if (in_array($normalizedLabel, $keywords, true)) {
                    $mapping[$fieldKey] = $header['index'];
                    break;
                }
            }
        }

        return $mapping;
    }

    private function openReader(string $fullPath, string $extension, ?string $csvDelimiter): XlsxReader|CsvReader
    {
        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            throw new RuntimeException('File spreadsheet tidak ditemukan atau tidak dapat dibaca.');
        }

        if ($extension === 'xlsx') {
            $reader = new XlsxReader();
            $reader->open($fullPath);
            return $reader;
        }

        if ($extension === 'csv') {
            $options = new CsvOptions();
            $options->FIELD_DELIMITER = $csvDelimiter ?: ',';
            $reader = new CsvReader($options);
            $reader->open($fullPath);
            return $reader;
        }

        throw new InvalidArgumentException('Format file tidak didukung. Gunakan file .xlsx atau .csv.');
    }

    private function detectExtension(string $fullPath): string
    {
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw new InvalidArgumentException('Ekstensi file harus .xlsx atau .csv.');
        }

        return $extension;
    }

    private function normalizeRowCells(array $rawCells): array
    {
        $normalized = [];
        foreach ($rawCells as $cell) {
            $normalized[] = $this->cellToString($cell);
        }

        return $normalized;
    }

    private function cellToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (floor($value) == $value) {
                return number_format($value, 0, '.', '');
            }
            return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    private function isRowEmpty(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== '') {
                return false;
            }
        }

        return true;
    }

    private function columnIndexToLetter(int $index): string
    {
        $letter = '';
        $num = $index + 1;

        while ($num > 0) {
            $mod = ($num - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $num = (int) (($num - $mod) / 26);
        }

        return $letter;
    }
}