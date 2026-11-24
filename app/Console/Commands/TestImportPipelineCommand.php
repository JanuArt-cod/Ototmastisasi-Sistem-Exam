<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Services\PrivateStorageService;
use App\Services\SpreadsheetReaderService;
use App\Services\StudentImportService;
use Illuminate\Console\Command;
use Throwable;

class TestImportPipelineCommand extends Command
{
    protected $signature = 'students:test-import';

    protected $description = 'Menguji pembacaan dan validasi file Excel/CSV sintetis (10 hingga 5.000 siswa)';

    public function handle(
        PrivateStorageService $storageService,
        SpreadsheetReaderService $readerService,
        StudentImportService $importService
    ): int {
        $tempDir = $storageService->fullPath(PrivateStorageService::DIR_TEMP);
        $dummySession = new ExamSession(['id' => 0, 'session_name' => 'Sesi Uji Memori']);

        $testCases = [
            'dummy_10_siswa.xlsx' => 'Uji 10 siswa valid & nol di depan',
            'dummy_kasus_validasi.xlsx' => 'Uji deteksi baris kosong, nama/kelas kosong, dan duplikat',
            'dummy_1000_siswa.csv' => 'Uji 1.000 siswa CSV pemisah titik koma (;)',
            'dummy_5000_siswa.xlsx' => 'Uji performa 5.000 siswa XLSX',
        ];

        try {
            foreach ($testCases as $filename => $label) {
                $fullPath = $tempDir . DIRECTORY_SEPARATOR . $filename;
                if (! is_file($fullPath)) {
                    $this->warn("File uji {$filename} tidak ditemukan. Jalankan php artisan students:generate-dummy lebih dulu.");
                    return self::FAILURE;
                }

                $startTime = microtime(true);

                $headerInfo = $readerService->extractHeadersAndMapping($fullPath, 0, 1);
                $previewResult = $importService->previewFromFile(
                    $fullPath,
                    $dummySession,
                    $headerInfo['suggested_mapping'],
                    0,
                    1,
                    $headerInfo['csv_delimiter']
                );

                $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
                $summary = $previewResult['summary'];

                $this->info("=== {$label} ({$filename}) ===");
                $this->line("Waktu baca & validasi : {$elapsedMs} ms");
                $this->line("Total baris terisi    : {$summary['total_non_empty_rows']}");
                $this->line("Baris valid           : {$summary['valid_count']}");
                $this->line("Baris bermasalah      : {$summary['error_count']} (duplikat: {$summary['duplicate_count']})");
                $this->line("Baris kosong dilewati : {$summary['empty_rows_skipped']}");

                if ($filename === 'dummy_10_siswa.xlsx' && ! empty($previewResult['preview_rows'][0])) {
                    $firstStudent = $previewResult['preview_rows'][0]['data'];
                    $this->line("Contoh NIS baris 1    : {$firstStudent['nis']} (nol di depan tetap terjaga)");
                }

                if ($filename === 'dummy_kasus_validasi.xlsx') {
                    foreach ($previewResult['problem_rows'] as $problem) {
                        $messages = array_merge($problem['errors'], $problem['warnings']);
                        $this->line(" - Baris ke-{$problem['source_row']}: " . implode(' | ', $messages));
                    }
                }

                $this->newLine();
            }

            $peakMemoryMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
            $this->info("Pengujian selesai. Puncak penggunaan memori PHP: {$peakMemoryMb} MB.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Pengujian gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}