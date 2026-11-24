<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Services\CardPdfGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class TestCardPdfCommand extends Command
{
    protected $signature = 'cards:test-pdf';

    protected $description = 'Menguji pembuatan PDF kartu ujian (1 halaman, halaman sisa, 1 kelas, 1.000 siswa, dan 5.000 siswa)';

    public function handle(CardPdfGeneratorService $pdfService): int
    {
        $session = ExamSession::withCount('students')
            ->orderByDesc('students_count')
            ->first();

        if (! $session or $session->students_count < 10) {
            throw new RuntimeException('Dibutuhkan minimal satu sesi ujian yang sudah berisi data siswa uji (minimal 10 siswa).');
        }

        $generatedPaths = [];

        try {
            $this->info('Menggunakan sesi uji: ' . $session->session_name . ' (' . number_format($session->students_count, 0, ',', '.') . ' siswa)');
            $this->newLine();

            // 1. Uji 1 halaman sampel pertama
            $this->info('=== 1. Uji PDF Sampel 1 Halaman Pertama ===');
            $sampleOnePage = $pdfService->generateSinglePdf($session, [
                'sample_one_page' => true,
            ]);
            $generatedPaths[] = $sampleOnePage['full_path'];

            if ($sampleOnePage['page_count'] !== 1) {
                throw new RuntimeException('Jumlah halaman sampel seharusnya 1 halaman.');
            }

            $this->line('File terbentuk : ' . $sampleOnePage['download_name']);
            $this->line('Jumlah kartu   : ' . $sampleOnePage['student_count'] . ' kartu pada 1 halaman');
            $this->line('Ukuran file    : ' . number_format($sampleOnePage['file_size_bytes'] / 1024, 1, ',', '.') . ' KB (' . $sampleOnePage['duration_ms'] . ' ms)');
            $this->newLine();

            // 2. Uji halaman terakhir tidak penuh (10 siswa)
            $this->info('=== 2. Uji Halaman Terakhir Tidak Penuh (10 Siswa) ===');
            $partialPage = $pdfService->generateSinglePdf($session, [
                'offset' => 0,
                'limit' => 10,
            ]);
            $generatedPaths[] = $partialPage['full_path'];

            if ($partialPage['student_count'] !== 10 or $partialPage['page_count'] < 2) {
                throw new RuntimeException('Perhitungan 10 siswa (minimal 2 halaman) tidak sesuai.');
            }

            $this->line('File terbentuk : ' . $partialPage['download_name']);
            $this->line('Jumlah kartu   : ' . $partialPage['student_count'] . ' kartu terbagi dalam ' . $partialPage['page_count'] . ' halaman');
            $this->line('Ukuran file    : ' . number_format($partialPage['file_size_bytes'] / 1024, 1, ',', '.') . ' KB (' . $partialPage['duration_ms'] . ' ms)');
            $this->newLine();

            // 3. Uji cetak 1 kelas tertentu
            $firstClass = $session->students()->orderBy('class_name')->value('class_name');
            $this->info('=== 3. Uji Cetak Filter 1 Kelas (' . $firstClass . ') ===');
            $oneClassPdf = $pdfService->generateSinglePdf($session, [
                'class_name' => $firstClass,
            ]);
            $generatedPaths[] = $oneClassPdf['full_path'];

            $this->line('File terbentuk : ' . $oneClassPdf['download_name']);
            $this->line('Jumlah kartu   : ' . $oneClassPdf['student_count'] . ' kartu (' . $oneClassPdf['page_count'] . ' halaman)');
            $this->line('Ukuran file    : ' . number_format($oneClassPdf['file_size_bytes'] / 1024, 1, ',', '.') . ' KB (' . $oneClassPdf['duration_ms'] . ' ms)');
            $this->newLine();

            // 4. Uji 1.000 siswa dengan strategi batch terukur per 150 siswa
            if ($session->students_count >= 1000) {
                $this->info('=== 4. Uji Cetak 1.000 Siswa (Batch Terukur per 150 Siswa) ===');
                $thousandBatches = $pdfService->generateBatchedPdfs($session, [
                    'batch_size' => 150,
                    'max_students' => 1000,
                ]);

                foreach ($thousandBatches['files'] as $batchFile) {
                    $generatedPaths[] = $batchFile['full_path'];
                }

                if ($thousandBatches['total_students'] !== 1000) {
                    throw new RuntimeException('Jumlah kartu pada PDF 1.000 siswa tidak cocok.');
                }

                $this->line('Jumlah paket PDF : ' . $thousandBatches['batch_count'] . ' file PDF terverifikasi');
                $this->line('Total kartu      : ' . number_format($thousandBatches['total_students'], 0, ',', '.') . ' kartu (' . $thousandBatches['total_pages'] . ' halaman)');
                $this->line('Total waktu      : ' . $thousandBatches['duration_ms'] . ' ms');
                $this->newLine();
            }

            // 5. Uji skala penuh 5.000 siswa menggunakan strategi batch terukur per 150 siswa
            if ($session->students_count >= 5000) {
                $this->info('=== 5. Uji Skala Penuh 5.000 Siswa (Batch Terukur per 150 Siswa) ===');
                $batchedResult = $pdfService->generateBatchedPdfs(
                    $session,
                    ['batch_size' => 150],
                    function (int $batchNumber, int $totalBatches, array $result) {
                        if ($batchNumber === 1 or $batchNumber % 10 === 0 or $batchNumber === $totalBatches) {
                            $this->line('  -> Batch ' . $batchNumber . '/' . $totalBatches . ' selesai (' . $result['student_count'] . ' kartu, ' . $result['duration_ms'] . ' ms)');
                        }
                    }
                );

                foreach ($batchedResult['files'] as $batchFile) {
                    $generatedPaths[] = $batchFile['full_path'];
                }

                if ($batchedResult['total_students'] !== 5000) {
                    throw new RuntimeException('Total kartu pada ekspor batch 5.000 siswa tidak cocok.');
                }

                $this->line('Jumlah paket PDF : ' . $batchedResult['batch_count'] . ' file PDF terverifikasi');
                $this->line('Total kartu      : ' . number_format($batchedResult['total_students'], 0, ',', '.') . ' kartu (' . $batchedResult['total_pages'] . ' halaman)');
                $this->line('Total waktu      : ' . $batchedResult['duration_ms'] . ' ms');
                $this->newLine();
            }

            $peakMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
            $this->info('Semua file PDF terverifikasi valid (%PDF-). Puncak penggunaan memori PHP: ' . $peakMb . ' MB.');

            return self::SUCCESS;
        } finally {
            // Bersihkan seluruh file PDF sementara hasil pengujian
            foreach ($generatedPaths as $path) {
                if (is_file($path)) {
                    File::delete($path);
                }
            }
        }
    }
}