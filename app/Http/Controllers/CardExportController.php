<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\CardSetting;
use App\Services\CardLayoutCalculatorService;
use App\Services\CardPdfGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class CardExportController extends Controller
{
    public function __construct(
        private readonly CardPdfGeneratorService $pdfService,
        private readonly CardLayoutCalculatorService $calculatorService
    ) {}

    public function index(ExamSession $examSession): View
    {
        $cardSetting = $examSession->cardSetting()->firstOrCreate(
            ['exam_session_id' => $examSession->id],
            CardSetting::defaultValues()
        );
        $layout = $cardSetting->toLayoutArray();

        $totalValidStudents = $this->pdfService->buildValidStudentsQuery($examSession)->count();
        $totalAllStudents = $examSession->students()->count();
        $calc = $this->calculatorService->calculate($layout, $totalValidStudents);

        $classStats = $this->pdfService->buildValidStudentsQuery($examSession)
            ->selectRaw('class_name, COUNT(*) as total')
            ->groupBy('class_name')
            ->orderBy('class_name')
            ->get();

        $exportedFiles = $this->pdfService->listExportFiles($examSession);

        return view('cards.export', [
            'session' => $examSession,
            'layout' => $layout,
            'calc' => $calc,
            'totalValidStudents' => $totalValidStudents,
            'totalAllStudents' => $totalAllStudents,
            'classStats' => $classStats,
            'exportedFiles' => $exportedFiles,
            'maxSingleStudents' => CardPdfGeneratorService::MAX_SINGLE_PDF_STUDENTS,
            'defaultBatchSize' => CardPdfGeneratorService::DEFAULT_BATCH_SIZE,
            'zipAvailable' => class_exists(ZipArchive::class),
        ]);
    }

    public function exportSingle(Request $request, ExamSession $examSession): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:sample_one_page,filtered'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $isSample = $validated['mode'] === 'sample_one_page';
        $className = trim((string) ($validated['class_name'] ?? ''));
        $search = trim((string) ($validated['search'] ?? ''));

        try {
            $result = $this->pdfService->generateSinglePdf($examSession, [
                'sample_one_page' => $isSample,
                'class_name' => $className,
                'search' => $search,
            ]);

            return response()->download($result['full_path'], $result['download_name'], [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (Throwable $e) {
            return redirect()
                ->route('exam-sessions.export.index', $examSession)
                ->withInput()
                ->withErrors(['export' => $e->getMessage()]);
        }
    }

    public function exportBatch(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'batch_size' => ['required', 'integer', 'in:100,150,250'],
        ]);

        $className = trim((string) ($validated['class_name'] ?? ''));
        $search = trim((string) ($validated['search'] ?? ''));
        $batchSize = (int) $validated['batch_size'];

        try {
            @set_time_limit(0);

            $batchResult = $this->pdfService->generateBatchedPdfs($examSession, [
                'class_name' => $className,
                'search' => $search,
                'batch_size' => $batchSize,
            ]);

            $zipInfo = $this->pdfService->createZipFromBatchFiles(
                $examSession,
                $batchResult['files'],
                $className
            );

            $durationSec = number_format($batchResult['duration_ms'] / 1000, 1, ',', '.');
            $message = 'Paket ekspor berhasil dibuat untuk ' .
                number_format($batchResult['total_students'], 0, ',', '.') . ' siswa (' .
                number_format($batchResult['total_pages'], 0, ',', '.') . ' halaman dalam ' .
                $batchResult['batch_count'] . ' file PDF, waktu ' . $durationSec . ' detik). ';

            if ($zipInfo !== null) {
                $message .= 'Seluruh file PDF sudah dikemas ke arsip ZIP "' . $zipInfo['download_name'] . '" pada daftar unduhan di bawah.';
            } else {
                $message .= 'Silakan unduh masing-masing file PDF bagian pada daftar unduhan di bawah.';
            }

            return redirect()
                ->route('exam-sessions.export.index', $examSession)
                ->with('success', $message);
        } catch (Throwable $e) {
            return redirect()
                ->route('exam-sessions.export.index', $examSession)
                ->withInput()
                ->withErrors(['export' => $e->getMessage()]);
        }
    }

    public function downloadFile(ExamSession $examSession, string $filename): BinaryFileResponse
    {
        $fullPath = $this->pdfService->resolveExportFilePath($examSession, $filename);
        if ($fullPath === null) {
            abort(404, 'File hasil ekspor tidak ditemukan.');
        }

        $cleanName = preg_replace('/^\d{8}_\d{6}_[A-Za-z0-9]{8}_/', '', $filename) ?: $filename;

        return response()->download($fullPath, $cleanName);
    }

    public function deleteFile(ExamSession $examSession, string $filename): RedirectResponse
    {
        if ($this->pdfService->deleteExportFile($examSession, $filename)) {
            return redirect()
                ->route('exam-sessions.export.index', $examSession)
                ->with('success', 'File hasil ekspor berhasil dihapus dari penyimpanan lokal.');
        }

        return redirect()
            ->route('exam-sessions.export.index', $examSession)
            ->withErrors(['export' => 'File tidak ditemukan atau sudah dihapus sebelumnya.']);
    }
}