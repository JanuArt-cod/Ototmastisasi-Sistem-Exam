<?php

namespace App\Services;

use App\Models\ExamSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class CardPdfGeneratorService
{
    private const MM_TO_PT = 2.834645669;

    /**
     * Ukuran batch aman per file PDF (150 siswa) agar penggunaan RAM selalu rendah.
     */
    public const DEFAULT_BATCH_SIZE = 150;

    /**
     * Batas maksimal kartu dalam satu file PDF tunggal sebelum dipecah menjadi batch ZIP.
     */
    public const MAX_SINGLE_PDF_STUDENTS = 600;

    public function __construct(
        private readonly CardLayoutCalculatorService $calculatorService,
        private readonly PrivateStorageService $storageService
    ) {}

    /**
     * Menghasilkan satu file PDF untuk siswa terpilih dan memverifikasi bahwa file terbentuk valid.
     */
    public function generateSinglePdf(ExamSession $examSession, array $options = []): array
    {
        $startedAt = microtime(true);

        $cardSetting = $examSession->cardSetting()->firstOrCreate(
            ['exam_session_id' => $examSession->id]
        );
        $layout = $cardSetting->toLayoutArray();
        $isDoubleSided = ($layout['card_side_mode'] ?? 'single') === 'double_sided';

        $sampleOnePage = (bool) ($options['sample_one_page'] ?? false);
        $className = trim((string) ($options['class_name'] ?? ''));
        $search = trim((string) ($options['search'] ?? ''));
        $offset = max(0, (int) ($options['offset'] ?? 0));
        $limit = isset($options['limit']) ? max(1, (int) $options['limit']) : null;

        $baseQuery = $this->buildValidStudentsQuery($examSession, $className, $search);
        $totalMatchingStudents = (clone $baseQuery)->count();

        if ($totalMatchingStudents === 0) {
            throw new RuntimeException('Tidak ditemukan data siswa valid yang sesuai dengan kriteria cetak.');
        }

        $calc = $this->calculatorService->calculate($layout, $totalMatchingStudents);
        if (! $calc['fits_on_page']) {
            throw new RuntimeException('Pengaturan ukuran kartu melebihi area cetak kertas: ' . implode(' ', $calc['errors']));
        }

        if ($sampleOnePage) {
            $limit = (int) $calc['cards_per_page'];
            $offset = 0;
        }

        $expectedCount = $limit !== null
            ? min($limit, max(0, $totalMatchingStudents - $offset))
            : max(0, $totalMatchingStudents - $offset);

        if ($expectedCount > self::MAX_SINGLE_PDF_STUDENTS) {
            throw new RuntimeException(
                'Jumlah kartu (' . number_format($expectedCount, 0, ',', '.') . ' siswa) melebihi batas satu file PDF tunggal (' .
                self::MAX_SINGLE_PDF_STUDENTS . ' siswa). Gunakan mode Ekspor Paket Batch (ZIP) agar memori tetap aman.'
            );
        }

        $query = (clone $baseQuery)
            ->orderBy('class_name')
            ->orderBy('student_name')
            ->orderBy('id');

        if ($offset > 0) {
            $query->skip($offset);
        }
        if ($limit !== null) {
            $query->take($limit);
        }

        $students = $query->get();
        if ($students->isEmpty()) {
            throw new RuntimeException('Daftar siswa pada rentang cetak yang dipilih kosong.');
        }

        $studentCount = $students->count();
        if ($studentCount > 100 || ($isDoubleSided && $studentCount > 60)) {
            $this->ensureSufficientMemory('512M');
            @set_time_limit(240);
        }

        $sharedLogoUri = $this->resolveImageDataUri($examSession->logo_path);
        $sharedSignatureUri = $this->resolveImageDataUri($examSession->signature_path);
        $sharedStampUri = $isDoubleSided
            ? $this->resolveImageDataUri($examSession->stamp_path ?? null)
            : null;

        $schedules = $isDoubleSided
            ? $examSession->schedules()->orderBy('day_number')->orderBy('id')->get()
            : collect();

        $photoCache = [];

        $renderedItems = [];
        foreach ($students as $student) {
            $photoUri = null;
            if (! empty($layout['show_photo']) and ! empty($student->photo_path)) {
                $photoKey = (string) $student->photo_path;
                if (! array_key_exists($photoKey, $photoCache)) {
                    $photoCache[$photoKey] = $this->resolveImageDataUri($photoKey);
                }
                $photoUri = $photoCache[$photoKey];
            }

            $renderedItems[] = [
                'student' => $student,
                'assets' => [
                    'logo_src' => $sharedLogoUri,
                    'signature_src' => $sharedSignatureUri,
                    'stamp_src' => $sharedStampUri,
                    'photo_src' => $photoUri,
                ],
            ];
        }

        $cardsPerPage = max(1, (int) $calc['cards_per_page']);
        $pages = array_chunk($renderedItems, $cardsPerPage);
        $pageCount = count($pages);

        $widthPt = round($calc['page_width_mm'] * self::MM_TO_PT, 2);
        $heightPt = round($calc['page_height_mm'] * self::MM_TO_PT, 2);

        $pdf = Pdf::loadView('cards.pdf-sheet', [
            'session' => $examSession,
            'layout' => $layout,
            'calc' => $calc,
            'pages' => $pages,
            'schedules' => $schedules,
        ]);

        $pdf->setPaper([0, 0, $widthPt, $heightPt], 'portrait');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', 'Arial');

        $outputBinary = $pdf->output();

        // Bebaskan seluruh objek DomPDF dan array halaman sebelum menulis file
        unset($pdf, $pages, $renderedItems, $students, $photoCache, $schedules);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        $exportDir = $this->exportDirectory($examSession);
        File::ensureDirectoryExists($exportDir);

        $fileName = $this->buildDownloadFilename($examSession, $options, $studentCount, $isDoubleSided);
        $storedName = now()->format('Ymd_His') . '_' . Str::random(8) . '_' . $fileName;
        $fullPath = $exportDir . DIRECTORY_SEPARATOR . $storedName;

        File::put($fullPath, $outputBinary);
        unset($outputBinary);

        $this->verifyPdfFile($fullPath);

        $durationMs = round((microtime(true) - $startedAt) * 1000, 2);

        return [
            'full_path' => $fullPath,
            'stored_name' => $storedName,
            'download_name' => $fileName,
            'file_size_bytes' => (int) filesize($fullPath),
            'student_count' => $studentCount,
            'page_count' => $pageCount,
            'cards_per_page' => $cardsPerPage,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * Memecah pembuatan PDF ribuan siswa menjadi beberapa file batch terukur agar memori tetap rendah.
     */
    public function generateBatchedPdfs(ExamSession $examSession, array $options = [], ?callable $onBatchComplete = null): array
    {
        $startedAt = microtime(true);
        $batchSize = min(
            self::MAX_SINGLE_PDF_STUDENTS,
            max(25, (int) ($options['batch_size'] ?? self::DEFAULT_BATCH_SIZE))
        );
        $className = trim((string) ($options['class_name'] ?? ''));
        $search = trim((string) ($options['search'] ?? ''));
        $maxStudents = isset($options['max_students']) ? max(1, (int) $options['max_students']) : null;

        $baseQuery = $this->buildValidStudentsQuery($examSession, $className, $search);
        $availableStudents = (clone $baseQuery)->count();

        if ($availableStudents === 0) {
            throw new RuntimeException('Tidak ditemukan data siswa valid yang sesuai dengan kriteria cetak.');
        }

        $totalStudents = $maxStudents !== null
            ? min($maxStudents, $availableStudents)
            : $availableStudents;

        $totalBatches = (int) ceil($totalStudents / $batchSize);
        $files = [];
        $totalPages = 0;

        for ($batchIndex = 0; $batchIndex < $totalBatches; $batchIndex++) {
            $offset = $batchIndex * $batchSize;
            $remaining = $totalStudents - $offset;
            $currentLimit = min($batchSize, $remaining);
            $batchNumber = $batchIndex + 1;

            $batchOptions = array_merge($options, [
                'sample_one_page' => false,
                'offset' => $offset,
                'limit' => $currentLimit,
                'batch_label' => 'bagian-' . str_pad((string) $batchNumber, 2, '0', STR_PAD_LEFT) . '-dari-' . str_pad((string) $totalBatches, 2, '0', STR_PAD_LEFT),
            ]);

            $result = $this->generateSinglePdf($examSession, $batchOptions);
            $files[] = $result;
            $totalPages += $result['page_count'];

            if ($onBatchComplete !== null) {
                $onBatchComplete($batchNumber, $totalBatches, $result);
            }

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return [
            'files' => $files,
            'batch_count' => count($files),
            'total_students' => $totalStudents,
            'total_pages' => $totalPages,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ];
    }

    /**
     * Mengemas beberapa file PDF batch menjadi satu file arsip ZIP di penyimpanan privat.
     */
    public function createZipFromBatchFiles(ExamSession $examSession, array $files, string $className = ''): ?array
    {
        if (! class_exists(ZipArchive::class) or empty($files)) {
            return null;
        }

        $exportDir = $this->exportDirectory($examSession);
        File::ensureDirectoryExists($exportDir);

        $baseSlug = Str::slug($examSession->session_name ?: 'kartu-ujian');
        if ($baseSlug === '') {
            $baseSlug = 'kartu-ujian';
        }

        $parts = [$baseSlug];
        if (trim($className) !== '') {
            $parts[] = 'kelas-' . Str::slug($className);
        }
        $parts[] = 'paket-' . count($files) . '-pdf';

        $downloadName = implode('-', $parts) . '.zip';
        $storedName = now()->format('Ymd_His') . '_' . Str::random(8) . '_' . $downloadName;
        $fullPath = $exportDir . DIRECTORY_SEPARATOR . $storedName;

        $zip = new ZipArchive();
        if ($zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        foreach ($files as $file) {
            if (! empty($file['full_path']) and is_file($file['full_path'])) {
                $zip->addFile($file['full_path'], $file['download_name']);
            }
        }

        $zip->close();

        if (! is_file($fullPath) or filesize($fullPath) === 0) {
            return null;
        }

        return [
            'full_path' => $fullPath,
            'stored_name' => $storedName,
            'download_name' => $downloadName,
            'file_size_bytes' => (int) filesize($fullPath),
        ];
    }

    /**
     * Mengambil daftar file PDF dan ZIP hasil ekspor yang tersimpan di folder privat sesi.
     */
    public function listExportFiles(ExamSession $examSession): array
    {
        $exportDir = $this->exportDirectory($examSession);
        if (! is_dir($exportDir)) {
            return [];
        }

        $items = [];
        foreach (File::files($exportDir) as $file) {
            $storedName = $file->getFilename();
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['pdf', 'zip'], true)) {
                continue;
            }

            $cleanName = preg_replace('/^\d{8}_\d{6}_[A-Za-z0-9]{8}_/', '', $storedName) ?: $storedName;
            $mtime = $file->getMTime();

            $items[] = [
                'stored_name' => $storedName,
                'download_name' => $cleanName,
                'extension' => strtoupper($ext),
                'size_bytes' => $file->getSize(),
                'created_timestamp' => $mtime,
                'created_at' => date('d/m/Y H:i:s', $mtime),
            ];
        }

        usort($items, static fn (array $a, array $b): int => $b['created_timestamp'] <=> $a['created_timestamp']);

        return $items;
    }

    /**
     * Memvalidasi nama file dan mengembalikan jalur absolut aman di folder privat sesi.
     */
    public function resolveExportFilePath(ExamSession $examSession, string $storedName): ?string
    {
        if ($storedName === '' or basename($storedName) !== $storedName) {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $storedName)) {
            return null;
        }

        $ext = strtolower(pathinfo($storedName, PATHINFO_EXTENSION));
        if (! in_array($ext, ['pdf', 'zip'], true)) {
            return null;
        }

        $fullPath = $this->exportDirectory($examSession) . DIRECTORY_SEPARATOR . $storedName;

        return is_file($fullPath) ? $fullPath : null;
    }

    public function deleteExportFile(ExamSession $examSession, string $storedName): bool
    {
        $fullPath = $this->resolveExportFilePath($examSession, $storedName);
        if ($fullPath === null) {
            return false;
        }

        return File::delete($fullPath);
    }

    public function buildValidStudentsQuery(ExamSession $examSession, string $className = '', string $search = ''): Builder
    {
        $query = $examSession->students()
            ->getQuery()
            ->whereNotNull('student_name')
            ->where('student_name', '!=', '')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '');

        if ($className !== '') {
            $query->where('class_name', $className);
        }

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('student_name', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%')
                    ->orWhere('nisn', 'like', '%' . $search . '%')
                    ->orWhere('participant_number', 'like', '%' . $search . '%')
                    ->orWhere('room_name', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    public function exportDirectory(ExamSession $examSession): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'exports' . DIRECTORY_SEPARATOR . 'session-' . $examSession->id);
    }

    private function resolveImageDataUri(?string $relativePath): ?string
    {
        if (empty($relativePath) or ! $this->storageService->exists($relativePath)) {
            return null;
        }

        $fullPath = $this->storageService->fullPath($relativePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $imageInfo = @getimagesize($fullPath);
        if ($imageInfo === false or empty($imageInfo['mime'])) {
            return null;
        }

        $mime = (string) $imageInfo['mime'];
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $rawBytes = @file_get_contents($fullPath);
        if ($rawBytes === false or $rawBytes === '') {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($rawBytes);
    }

    private function verifyPdfFile(string $fullPath): void
    {
        if (! is_file($fullPath)) {
            throw new RuntimeException('File PDF gagal ditulis ke penyimpanan lokal.');
        }

        $size = filesize($fullPath);
        if ($size === false or $size < 500) {
            throw new RuntimeException('Ukuran file PDF tidak valid atau kosong.');
        }

        $handle = @fopen($fullPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('File PDF terbentuk tetapi tidak dapat dibaca kembali untuk verifikasi.');
        }

        $header = fread($handle, 5);
        fclose($handle);

        if ($header !== '%PDF-') {
            throw new RuntimeException('Struktur file hasil ekspor bukan dokumen PDF yang valid.');
        }
    }

    private function buildDownloadFilename(ExamSession $examSession, array $options, int $studentCount, bool $isDoubleSided = false): string
    {
        $baseSlug = Str::slug($examSession->session_name ?: 'kartu-ujian');
        if ($baseSlug === '') {
            $baseSlug = 'kartu-ujian';
        }

        $parts = [$baseSlug];

        if ($isDoubleSided) {
            $parts[] = '2-sisi';
        }

        if (! empty($options['sample_one_page'])) {
            $parts[] = 'sampel-1-halaman';
        } elseif (! empty($options['class_name'])) {
            $parts[] = 'kelas-' . Str::slug((string) $options['class_name']);
        } else {
            $parts[] = $studentCount . '-siswa';
        }

        if (! empty($options['batch_label'])) {
            $parts[] = Str::slug((string) $options['batch_label']);
        }

        return implode('-', $parts) . '.pdf';
    }

    private function ensureSufficientMemory(string $targetLimit): void
    {
        $current = (string) ini_get('memory_limit');
        if ($current === '-1') {
            return;
        }

        $currentBytes = $this->parseBytes($current);
        $targetBytes = $this->parseBytes($targetLimit);

        if ($currentBytes > 0 and $currentBytes < $targetBytes) {
            @ini_set('memory_limit', $targetLimit);
        }
    }

    private function parseBytes(string $val): int
    {
        $trimmed = trim($val);
        if ($trimmed === '') {
            return 0;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = (int) $trimmed;

        if ($unit === 'g') {
            return $number * 1024 * 1024 * 1024;
        }
        if ($unit === 'm') {
            return $number * 1024 * 1024;
        }
        if ($unit === 'k') {
            return $number * 1024;
        }

        return $number;
    }
}