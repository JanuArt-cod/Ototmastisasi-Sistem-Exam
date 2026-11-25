<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Services\AttendanceSheetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AttendanceSheetController extends Controller
{
    public function __construct(
        private readonly AttendanceSheetService $attendanceService
    ) {
    }

    public function index(Request $request, ExamSession $examSession): View|RedirectResponse
    {
        $sessionStorageKey = 'attendance_sheet_options.' . $examSession->id;

        if ($request->boolean('reset_defaults')) {
            $request->session()->forget($sessionStorageKey);

            return redirect()
                ->route('exam-sessions.attendance.index', $examSession)
                ->with('success', 'Pengaturan daftar hadir dikembalikan ke standar referensi.');
        }

        $effectiveInput = $this->resolveAndPersistOptions($request, $examSession);
        $attendanceData = $this->attendanceService->buildAttendanceData($examSession, $effectiveInput);
        $allSessions = ExamSession::query()
            ->orderByDesc('updated_at')
            ->get(['id', 'session_name', 'academic_year']);

        return view('attendance.index', [
            'session' => $examSession,
            'allSessions' => $allSessions,
            'data' => $attendanceData,
            'paperPresets' => AttendanceSheetService::PAPER_PRESETS,
        ]);
    }

    public function exportPdf(Request $request, ExamSession $examSession): Response
    {
        @ini_set('memory_limit', '512M');

        $effectiveInput = $this->resolveAndPersistOptions($request, $examSession);

        if ($request->filled('room_filter')) {
            $effectiveInput['room_filter'] = trim((string) $request->input('room_filter'));
        }

        $attendanceData = $this->attendanceService->buildAttendanceData($examSession, $effectiveInput);

        if (empty($attendanceData['rooms'])) {
            return redirect()
                ->route('exam-sessions.attendance.index', $examSession)
                ->with('error', 'Tidak ditemukan data peserta yang memiliki ruang ujian untuk dicetak.');
        }

        $paperSize = $attendanceData['options']['paper_size'];
        $pdf = Pdf::setOption([
            'dpi' => 96,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
        ])->loadView('attendance.pdf', [
            'session' => $examSession,
            'data' => $attendanceData,
        ]);

        if ($paperSize === 'a4_landscape') {
            $pdf->setPaper('A4', 'landscape');
        } else {
            // DomPDF menukar indeks 2 dan 3 saat orientation='landscape', menghasilkan 14 x 8.5 inci (1008 x 612 pt)
            $pdf->setPaper([0, 0, 612.0, 1008.0], 'landscape');
        }

        $slugSession = Str::slug((string) $examSession->session_name) ?: 'sesi-ujian';
        $roomFilter = $attendanceData['options']['room_filter'];
        $roomSuffix = $roomFilter === 'all'
            ? 'semua-ruang'
            : (Str::slug($roomFilter) ?: 'ruang');

        $filename = 'daftar-hadir-' . $slugSession . '-' . $roomSuffix . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    private function resolveAndPersistOptions(Request $request, ExamSession $examSession): array
    {
        $sessionStorageKey = 'attendance_sheet_options.' . $examSession->id;
        $savedOptions = $request->session()->get($sessionStorageKey, []);

        $settingKeys = [
            'room_filter',
            'sort_by',
            'paper_size',
            'first_page_rows',
            'continuation_page_rows',
            'target_room_capacity',
            'fill_empty_rows',
            'issue_city',
            'issue_date_text',
            'issue_year',
            'chair_title',
            'chair_name',
            'secretary_title',
            'secretary_name',
        ];

        $hasIncomingSettings = false;
        foreach ($settingKeys as $key) {
            if ($request->has($key)) {
                $hasIncomingSettings = true;
                break;
            }
        }

        if (! $hasIncomingSettings) {
            return $savedOptions;
        }

        $incoming = $request->only($settingKeys);
        if ($request->has('fill_empty_rows') || $request->has('form_submitted')) {
            $incoming['fill_empty_rows'] = $request->boolean('fill_empty_rows');
        }

        $merged = array_merge($savedOptions, $incoming);
        $normalized = $this->attendanceService->normalizeOptions($examSession, $merged);

        $toStore = $normalized;
        unset($toStore['paper_preset']);
        $request->session()->put($sessionStorageKey, $toStore);

        return $toStore;
    }
}