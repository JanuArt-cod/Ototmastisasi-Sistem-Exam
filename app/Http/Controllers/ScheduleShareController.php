<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Services\ScheduleShareService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ScheduleShareController extends Controller
{
    public function __construct(
        private readonly ScheduleShareService $shareService
    ) {
    }

    public function index(ExamSession $examSession): View
    {
        $scheduleData = $this->shareService->buildScheduleData($examSession);

        return view('schedules.share', [
            'session' => $examSession,
            'data' => $scheduleData,
            'paperPresets' => ScheduleShareService::PAPER_PRESETS,
        ]);
    }

    public function update(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'header_title' => ['required', 'string', 'max:180'],
            'footer_signatory' => ['nullable', 'string', 'max:140'],
            'paper_size' => ['required', 'in:a4_portrait,f4_portrait'],
            'rooms_text' => ['required', 'string', 'max:250'],
            'teacher_pool_text' => ['nullable', 'string', 'max:5000'],
            'proctors' => ['nullable', 'array'],
            'committees' => ['nullable', 'array'],
        ], [
            'header_title.required' => 'Judul utama dokumen wajib diisi.',
            'rooms_text.required' => 'Daftar nomor/nama ruang wajib diisi (pisahkan dengan koma).',
        ]);

        $rooms = $this->parseRoomsText($validated['rooms_text']);
        if (empty($rooms)) {
            return redirect()
                ->route('exam-sessions.schedules.share.index', $examSession)
                ->withErrors(['rooms_text' => 'Minimal harus ada 1 ruang ujian (contoh: 1, 2, 3, 4, 5, 6).']);
        }

        $this->shareService->saveConfig($examSession, [
            'header_title' => $validated['header_title'],
            'footer_signatory' => (string) ($validated['footer_signatory'] ?? ''),
            'paper_size' => $validated['paper_size'],
            'show_proctor_columns' => $request->boolean('show_proctor_columns'),
            'rooms' => $rooms,
            'teacher_pool_text' => (string) ($validated['teacher_pool_text'] ?? ''),
            'proctors' => $validated['proctors'] ?? [],
            'committees' => $validated['committees'] ?? [],
        ]);

        return redirect()
            ->route('exam-sessions.schedules.share.index', $examSession)
            ->with('success', 'Pengaturan jadwal, pengawas ruang, dan panitia berhasil disimpan.');
    }

    public function autoAssign(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_pool_text' => ['nullable', 'string', 'max:5000'],
            'teachers' => ['nullable', 'array', 'max:100'],
            'teachers.*.name' => ['nullable', 'string', 'max:120'],
            'teachers.*.max_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'teachers.*.allowed_days' => ['nullable', 'array'],
            'teachers.*.allowed_days.*' => ['integer', 'min:1', 'max:30'],
            'teachers.*.forbidden_subjects_text' => ['nullable', 'string', 'max:250'],
            'max_days_per_teacher' => ['nullable', 'integer', 'min:1', 'max:30'],
            'rooms_text' => ['nullable', 'string', 'max:250'],
        ]);

        $structuredRules = [];
        foreach ((array) ($validated['teachers'] ?? []) as $row) {
            if (is_array($row) && trim((string) ($row['name'] ?? '')) !== '') {
                $structuredRules[] = $row;
            }
        }

        $poolText = trim((string) ($validated['teacher_pool_text'] ?? ''));

        if (empty($structuredRules) && $poolText === '') {
            return back()->with('error', 'Isi minimal satu nama guru pengawas terlebih dahulu.');
        }

        if (! empty($validated['rooms_text'])) {
            $rooms = $this->parseRoomsText($validated['rooms_text']);
            if (! empty($rooms)) {
                $this->shareService->saveConfig($examSession, ['rooms' => $rooms]);
            }
        }

        $maxDays = isset($validated['max_days_per_teacher']) && $validated['max_days_per_teacher'] !== ''
            ? (int) $validated['max_days_per_teacher']
            : null;

        $result = $this->shareService->autoAssignProctors(
            $examSession,
            $poolText,
            $maxDays,
            $structuredRules
        );

        $redirect = redirect()->route('exam-sessions.schedules.share.index', $examSession);

        if (! empty($result['auto_assign_warnings'])) {
            return $redirect
                ->with('success', 'Pembagian pengawas otomatis selesai dijalankan dengan catatan kuota.')
                ->with('error', implode(' | ', array_slice($result['auto_assign_warnings'], 0, 3)));
        }

        return $redirect->with('success', 'Pembagian pengawas ruang otomatis berhasil diterapkan sesuai hari tersedia, kuota hari, rolling ruang & larangan mapel.');
    }

    public function reset(ExamSession $examSession): RedirectResponse
    {
        $this->shareService->resetConfig($examSession);

        return redirect()
            ->route('exam-sessions.schedules.share.index', $examSession)
            ->with('success', 'Pengaturan jadwal dan daftar pengawas berhasil dikembalikan ke bawaan.');
    }

    public function exportPdf(Request $request, ExamSession $examSession): Response
    {
        $override = [];
        if ($request->filled('paper_size')) {
            $override['paper_size'] = (string) $request->input('paper_size');
        }
        if ($request->has('show_proctor_columns')) {
            $override['show_proctor_columns'] = $request->boolean('show_proctor_columns');
        }

        $scheduleData = $this->shareService->buildScheduleData($examSession, $override);

        if (empty($scheduleData['days'])) {
            return redirect()
                ->route('exam-sessions.schedules.share.index', $examSession)
                ->with('error', 'Jadwal mata pelajaran masih kosong. Tambahkan jadwal terlebih dahulu sebelum mencetak PDF.');
        }

        $paperSize = $scheduleData['options']['paper_size'];
        $pdf = Pdf::setOption([
            'dpi' => 96,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
        ])->loadView('schedules.share-pdf', [
            'session' => $examSession,
            'data' => $scheduleData,
        ]);

        if ($paperSize === 'a4_portrait') {
            $pdf->setPaper('A4', 'portrait');
        } else {
            // F4 / Folio Portrait (215 x 330 mm = 609.45 x 935.43 pt)
            $pdf->setPaper([0, 0, 609.45, 935.43], 'portrait');
        }

        $slugSession = Str::slug((string) $examSession->session_name) ?: 'sesi-ujian';
        $filename = 'jadwal-ujian-' . $slugSession . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    private function parseRoomsText(string $roomsText): array
    {
        $parts = preg_split('/[,;\r\n]+/', $roomsText) ?: [];

        return array_values(array_unique(array_filter(
            array_map('trim', $parts),
            fn (string $item) => $item !== ''
        )));
    }
}