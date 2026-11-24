<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Services\PrivateStorageService;
use App\Services\SpreadsheetReaderService;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class StudentImportController extends Controller
{
    public function __construct(
        private readonly PrivateStorageService $storageService,
        private readonly SpreadsheetReaderService $readerService,
        private readonly StudentImportService $importService
    ) {}

    public function showWizard(Request $request, ExamSession $examSession): View
    {
        $sessionKey = $this->wizardSessionKey($examSession);
        $wizardState = $request->session()->get($sessionKey);

        // Pastikan file sementara masih tersedia jika state wizard ada
        if ($wizardState && ! $this->storageService->exists($wizardState['temp_path'] ?? null)) {
            $request->session()->forget($sessionKey);
            $wizardState = null;
        }

        return view('imports.wizard', [
            'session' => $examSession,
            'wizard' => $wizardState,
            'supportedFields' => SpreadsheetReaderService::SUPPORTED_FIELDS,
        ]);
    }

    public function upload(Request $request, ExamSession $examSession): RedirectResponse
    {
        $request->validate([
            'spreadsheet_file' => [
                'required',
                'file',
                'max:20480', // Maksimal 20 MB
                function (string $attribute, mixed $value, \Closure $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (! in_array($ext, ['xlsx', 'csv'], true)) {
                        $fail('Format file harus berupa Excel (.xlsx) atau CSV (.csv).');
                    }
                },
            ],
        ], [
            'spreadsheet_file.required' => 'Silakan pilih file Excel (.xlsx) atau CSV (.csv) terlebih dahulu.',
            'spreadsheet_file.max' => 'Ukuran file maksimal adalah 20 MB.',
        ]);

        $sessionKey = $this->wizardSessionKey($examSession);
        $previousState = $request->session()->get($sessionKey);
        if (! empty($previousState['temp_path'])) {
            $this->storageService->deleteIfExists($previousState['temp_path']);
        }

        $uploadedFile = $request->file('spreadsheet_file');
        $originalName = $uploadedFile->getClientOriginalName();
        $tempRelativePath = null;

        try {
            $tempRelativePath = $this->storageService->storeTempImport($uploadedFile);
            $fullPath = $this->storageService->fullPath($tempRelativePath);

            $inspection = $this->readerService->inspectFile($fullPath, 0, null);
            $headerExtraction = $this->readerService->extractHeadersAndMapping(
                $fullPath,
                0,
                1,
                $inspection['csv_delimiter']
            );

            $request->session()->put($sessionKey, [
                'temp_path' => $tempRelativePath,
                'original_name' => $originalName,
                'extension' => $inspection['extension'],
                'sheets' => $inspection['sheets'],
                'selected_sheet_index' => 0,
                'header_row_number' => 1,
                'csv_delimiter' => $inspection['csv_delimiter'],
                'sample_rows' => $inspection['sample_rows'],
                'headers' => $headerExtraction['headers'],
                'mapping' => $headerExtraction['suggested_mapping'],
                'preview_data_rows' => $headerExtraction['preview_data_rows'],
                'validation_options' => [
                    'check_duplicate_nis' => true,
                    'check_duplicate_nisn' => true,
                ],
                'validation_result' => null,
            ]);

            return redirect()
                ->route('exam-sessions.import.wizard', $examSession)
                ->with('success', 'File "' . $originalName . '" berhasil diunggah. Silakan periksa pemetaan kolom di bawah ini.');
        } catch (Throwable $e) {
            if ($tempRelativePath) {
                $this->storageService->deleteIfExists($tempRelativePath);
            }
            $request->session()->forget($sessionKey);

            return back()->withErrors([
                'spreadsheet_file' => 'File tidak dapat dibaca: ' . $e->getMessage(),
            ]);
        }
    }

    public function preview(Request $request, ExamSession $examSession): RedirectResponse
    {
        $sessionKey = $this->wizardSessionKey($examSession);
        $wizardState = $request->session()->get($sessionKey);

        if (! $wizardState || ! $this->storageService->exists($wizardState['temp_path'] ?? null)) {
            return redirect()
                ->route('exam-sessions.import.wizard', $examSession)
                ->withErrors(['spreadsheet_file' => 'Sesi unggahan file tidak ditemukan atau sudah kedaluwarsa. Silakan unggah ulang file.']);
        }

        $validated = $request->validate([
            'sheet_index' => ['required', 'integer', 'min:0'],
            'header_row_number' => ['required', 'integer', 'min:1', 'max:50'],
            'csv_delimiter' => ['nullable', 'string', 'max:5'],
            'action_mode' => ['required', 'in:reload_structure,run_validation'],
            'mapping' => ['nullable', 'array'],
            'check_duplicate_nis' => ['nullable', 'boolean'],
            'check_duplicate_nisn' => ['nullable', 'boolean'],
        ]);

        $fullPath = $this->storageService->fullPath($wizardState['temp_path']);
        $sheetIndex = (int) $validated['sheet_index'];
        $headerRowNumber = (int) $validated['header_row_number'];

        $csvDelimiter = $wizardState['csv_delimiter'];
        if ($wizardState['extension'] === 'csv' && ! empty($validated['csv_delimiter'])) {
            $csvDelimiter = $validated['csv_delimiter'] === 'tab' ? "\t" : $validated['csv_delimiter'];
        }

        try {
            $inspection = $this->readerService->inspectFile($fullPath, $sheetIndex, $csvDelimiter);
            $headerExtraction = $this->readerService->extractHeadersAndMapping(
                $fullPath,
                $sheetIndex,
                $headerRowNumber,
                $csvDelimiter
            );

            // Jika petugas mengganti sheet/baris header, gunakan saran pemetaan baru
            if ($validated['action_mode'] === 'reload_structure') {
                $wizardState['selected_sheet_index'] = $sheetIndex;
                $wizardState['header_row_number'] = $headerRowNumber;
                $wizardState['csv_delimiter'] = $csvDelimiter;
                $wizardState['sample_rows'] = $inspection['sample_rows'];
                $wizardState['headers'] = $headerExtraction['headers'];
                $wizardState['mapping'] = $headerExtraction['suggested_mapping'];
                $wizardState['preview_data_rows'] = $headerExtraction['preview_data_rows'];
                $wizardState['validation_result'] = null;

                $request->session()->put($sessionKey, $wizardState);

                return redirect()
                    ->route('exam-sessions.import.wizard', $examSession)
                    ->with('success', 'Struktur sheet dan baris header berhasil diperbarui.');
            }

            $submittedMapping = $validated['mapping'] ?? [];
            $cleanMapping = [];
            foreach (array_keys(SpreadsheetReaderService::SUPPORTED_FIELDS) as $fieldKey) {
                $val = $submittedMapping[$fieldKey] ?? '';
                $cleanMapping[$fieldKey] = ($val !== '' && $val !== null) ? (int) $val : null;
            }

            if ($cleanMapping['student_name'] === null || $cleanMapping['class_name'] === null) {
                return back()->withErrors([
                    'mapping' => 'Kolom "Nama Siswa" dan "Kelas / Rombel" wajib dipasangkan sebelum menjalankan pemeriksaan data.',
                ]);
            }

            $validationOptions = [
                'check_duplicate_nis' => $request->boolean('check_duplicate_nis'),
                'check_duplicate_nisn' => $request->boolean('check_duplicate_nisn'),
                'preview_limit' => 30,
            ];

            $validationResult = $this->importService->previewFromFile(
                $fullPath,
                $examSession,
                $cleanMapping,
                $sheetIndex,
                $headerRowNumber,
                $csvDelimiter,
                $validationOptions
            );

            $wizardState['selected_sheet_index'] = $sheetIndex;
            $wizardState['header_row_number'] = $headerRowNumber;
            $wizardState['csv_delimiter'] = $csvDelimiter;
            $wizardState['sample_rows'] = $inspection['sample_rows'];
            $wizardState['headers'] = $headerExtraction['headers'];
            $wizardState['mapping'] = $cleanMapping;
            $wizardState['preview_data_rows'] = $headerExtraction['preview_data_rows'];
            $wizardState['validation_options'] = [
                'check_duplicate_nis' => $validationOptions['check_duplicate_nis'],
                'check_duplicate_nisn' => $validationOptions['check_duplicate_nisn'],
            ];
            $wizardState['validation_result'] = $validationResult;

            $request->session()->put($sessionKey, $wizardState);

            return redirect()
                ->route('exam-sessions.import.wizard', $examSession)
                ->with('success', 'Pemeriksaan data selesai. Silakan tinjau ringkasan validasi sebelum menyimpan.');
        } catch (Throwable $e) {
            return back()->withErrors([
                'mapping' => 'Gagal memeriksa data: ' . $e->getMessage(),
            ]);
        }
    }

    public function commit(Request $request, ExamSession $examSession): RedirectResponse
    {
        $sessionKey = $this->wizardSessionKey($examSession);
        $wizardState = $request->session()->get($sessionKey);

        if (! $wizardState || empty($wizardState['validation_result']) || ! $this->storageService->exists($wizardState['temp_path'] ?? null)) {
            return redirect()
                ->route('exam-sessions.import.wizard', $examSession)
                ->withErrors(['spreadsheet_file' => 'Silakan unggah file dan jalankan pemeriksaan data terlebih dahulu.']);
        }

        $validated = $request->validate([
            'import_mode' => ['required', 'in:append,replace'],
            'error_handling' => ['required', 'in:skip_errors,strict'],
        ]);

        $fullPath = $this->storageService->fullPath($wizardState['temp_path']);
        $allowSkipErrors = $validated['error_handling'] === 'skip_errors';

        try {
            $result = DB::transaction(function () use ($validated, $examSession, $fullPath, $wizardState, $allowSkipErrors) {
                if ($validated['import_mode'] === 'replace') {
                    $examSession->students()->delete();
                }

                return $this->importService->commitImport(
                    $fullPath,
                    $examSession,
                    $wizardState['mapping'],
                    $wizardState['selected_sheet_index'],
                    $wizardState['header_row_number'],
                    $wizardState['csv_delimiter'],
                    $wizardState['validation_options'],
                    $allowSkipErrors
                );
            });

            $this->storageService->deleteIfExists($wizardState['temp_path']);
            $request->session()->forget($sessionKey);

            $message = 'Berhasil menyimpan ' . number_format($result['inserted_count'], 0, ',', '.') . ' data siswa ke dalam sesi.';
            if ($result['skipped_error_rows'] > 0) {
                $message .= ' Sebanyak ' . number_format($result['skipped_error_rows'], 0, ',', '.') . ' baris bermasalah dilewati.';
            }

            return redirect()
                ->route('exam-sessions.show', $examSession)
                ->with('success', $message);
        } catch (Throwable $e) {
            return back()->withErrors([
                'commit' => 'Gagal menyimpan data impor: ' . $e->getMessage(),
            ]);
        }
    }

    public function cancel(Request $request, ExamSession $examSession): RedirectResponse
    {
        $sessionKey = $this->wizardSessionKey($examSession);
        $wizardState = $request->session()->get($sessionKey);

        if (! empty($wizardState['temp_path'])) {
            $this->storageService->deleteIfExists($wizardState['temp_path']);
        }

        $request->session()->forget($sessionKey);

        return redirect()
            ->route('exam-sessions.show', $examSession)
            ->with('success', 'Proses impor data dibatalkan dan file sementara telah dibersihkan.');
    }

    private function wizardSessionKey(ExamSession $examSession): string
    {
        return 'import_wizard_session_' . $examSession->id;
    }
}