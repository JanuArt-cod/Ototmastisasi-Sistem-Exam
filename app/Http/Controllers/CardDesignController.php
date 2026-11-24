<?php

namespace App\Http\Controllers;

use App\Models\CardSetting;
use App\Models\ExamSession;
use App\Models\Student;
use App\Services\CardLayoutCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CardDesignController extends Controller
{
    public function __construct(
        private readonly CardLayoutCalculatorService $layoutCalculator
    ) {}

    public function edit(Request $request, ExamSession $examSession): View
    {
        $cardSetting = $examSession->cardSetting()->firstOrCreate([], CardSetting::defaultValues());
        $layout = $cardSetting->toLayoutArray();

        $layoutError = null;
        $metrics = null;

        try {
            $metrics = $this->layoutCalculator->calculate($layout);
        } catch (Throwable $e) {
            $layoutError = $e->getMessage();
        }

        $validStudentsQuery = $examSession->students()
            ->getQuery()
            ->whereNotNull('student_name')
            ->where('student_name', '!=', '')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '');

        $firstStudent = (clone $validStudentsQuery)
            ->orderBy('class_name')
            ->orderBy('student_name')
            ->first();

        $longestStudent = (clone $validStudentsQuery)
            ->orderByRaw('LENGTH(student_name) DESC')
            ->first();

        if (! $firstStudent) {
            $firstStudent = $this->makeFallbackStudent('PAT.26.01.001', 'ADRIYANSYAH', '20260001', '0081234567', '7 (Tujuh) A', 'Ruang 01');
        }

        if (! $longestStudent) {
            $longestStudent = $this->makeFallbackStudent('PAT.26.01.099', 'MUHAMMAD RADITYA AL-FARIZI RAMADHAN', '20260099', '0089876543', '9 (Sembilan) B', 'Ruang 05');
        }

        $previewStudent = $request->query('sample') === 'longest' ? $longestStudent : $firstStudent;
        $schedules = $examSession->schedules()->get();

        $version = $examSession->updated_at?->timestamp ?? time();
        $logoUrl = $examSession->logo_path
            ? route('exam-sessions.information.asset', [$examSession, 'logo']) . '?v=' . $version
            : null;
        $signatureUrl = $examSession->signature_path
            ? route('exam-sessions.information.asset', [$examSession, 'signature']) . '?v=' . $version
            : null;
        $stampUrl = $examSession->stamp_path
            ? route('exam-sessions.information.asset', [$examSession, 'stamp']) . '?v=' . $version
            : null;

        return view('cards.design', [
            'session' => $examSession,
            'cardSetting' => $cardSetting,
            'layout' => $layout,
            'metrics' => $metrics,
            'layoutError' => $layoutError,
            'previewStudent' => $previewStudent,
            'useLongestSample' => $request->query('sample') === 'longest',
            'schedules' => $schedules,
            'logoUrl' => $logoUrl,
            'signatureUrl' => $signatureUrl,
            'stampUrl' => $stampUrl,
        ]);
    }

    public function update(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'card_side_mode' => ['required', 'in:single,double_sided'],
            'card_width_mm' => ['required', 'numeric', 'min:40', 'max:250'],
            'card_height_mm' => ['required', 'numeric', 'min:30', 'max:250'],
            'border_color' => ['required', 'string', 'max:20'],
            'header_bg_color' => ['required', 'string', 'max:20'],
            'accent_color' => ['required', 'string', 'max:20'],
            'photo_position' => ['required', 'in:left,right'],
            'photo_width_mm' => ['required', 'numeric', 'min:10', 'max:60'],
            'photo_height_mm' => ['required', 'numeric', 'min:10', 'max:80'],
            'back_header_title' => ['nullable', 'string', 'max:120'],
            'schedule_font_size_pt' => ['required', 'numeric', 'min:4.5', 'max:11'],
            'print_paper_size' => ['required', 'in:A4,F4,Letter,Custom'],
            'print_orientation' => ['required', 'in:portrait,landscape'],
            'custom_paper_width_mm' => ['nullable', 'required_if:print_paper_size,Custom', 'numeric', 'min:50', 'max:1000'],
            'custom_paper_height_mm' => ['nullable', 'required_if:print_paper_size,Custom', 'numeric', 'min:50', 'max:1000'],
            'margin_top_mm' => ['required', 'numeric', 'min:0', 'max:80'],
            'margin_right_mm' => ['required', 'numeric', 'min:0', 'max:80'],
            'margin_bottom_mm' => ['required', 'numeric', 'min:0', 'max:80'],
            'margin_left_mm' => ['required', 'numeric', 'min:0', 'max:80'],
            'gap_x_mm' => ['required', 'numeric', 'min:0', 'max:40'],
            'gap_y_mm' => ['required', 'numeric', 'min:0', 'max:40'],
            'cards_per_row' => ['nullable', 'integer', 'min:0', 'max:20'],
            'cards_per_column' => ['nullable', 'integer', 'min:0', 'max:20'],
        ], [
            'card_width_mm.required' => 'Lebar fisik kartu wajib diisi.',
            'card_height_mm.required' => 'Tinggi fisik kartu wajib diisi.',
            'custom_paper_width_mm.required_if' => 'Lebar kertas kustom wajib diisi.',
            'custom_paper_height_mm.required_if' => 'Tinggi kertas kustom wajib diisi.',
        ]);

        $payload = [
            'card_side_mode' => $validated['card_side_mode'],
            'card_width_mm' => (float) $validated['card_width_mm'],
            'card_height_mm' => (float) $validated['card_height_mm'],
            'border_color' => trim($validated['border_color']),
            'header_bg_color' => trim($validated['header_bg_color']),
            'accent_color' => trim($validated['accent_color']),
            'show_logo' => $request->boolean('show_logo'),
            'show_foundation_name' => $request->boolean('show_foundation_name'),
            'show_accreditation' => $request->boolean('show_accreditation'),
            'show_school_address' => $request->boolean('show_school_address'),
            'show_exam_date' => $request->boolean('show_exam_date'),
            'show_participant_number' => $request->boolean('show_participant_number'),
            'show_nis' => $request->boolean('show_nis'),
            'show_nisn' => $request->boolean('show_nisn'),
            'show_class_name' => $request->boolean('show_class_name'),
            'show_room_name' => $request->boolean('show_room_name'),
            'show_photo' => $request->boolean('show_photo'),
            'photo_position' => $validated['photo_position'],
            'photo_width_mm' => (float) $validated['photo_width_mm'],
            'photo_height_mm' => (float) $validated['photo_height_mm'],
            'show_issue_city' => $request->boolean('show_issue_city'),
            'show_signature' => $request->boolean('show_signature'),
            'show_signatory_nip' => $request->boolean('show_signatory_nip'),
            'show_stamp' => $request->boolean('show_stamp'),
            'show_card_note' => $request->boolean('show_card_note'),
            'show_back_header' => $request->boolean('show_back_header'),
            'back_header_title' => trim((string) ($validated['back_header_title'] ?? '')) ?: 'JADWAL UJIAN',
            'schedule_font_size_pt' => (float) $validated['schedule_font_size_pt'],
            'print_paper_size' => $validated['print_paper_size'],
            'print_orientation' => $validated['print_orientation'],
            'custom_paper_width_mm' => $validated['print_paper_size'] === 'Custom'
                ? (float) $validated['custom_paper_width_mm']
                : null,
            'custom_paper_height_mm' => $validated['print_paper_size'] === 'Custom'
                ? (float) $validated['custom_paper_height_mm']
                : null,
            'margin_top_mm' => (float) $validated['margin_top_mm'],
            'margin_right_mm' => (float) $validated['margin_right_mm'],
            'margin_bottom_mm' => (float) $validated['margin_bottom_mm'],
            'margin_left_mm' => (float) $validated['margin_left_mm'],
            'gap_x_mm' => (float) $validated['gap_x_mm'],
            'gap_y_mm' => (float) $validated['gap_y_mm'],
            'cards_per_row' => (int) ($validated['cards_per_row'] ?? 0),
            'cards_per_column' => (int) ($validated['cards_per_column'] ?? 0),
            'show_cut_lines' => $request->boolean('show_cut_lines'),
        ];

        try {
            $this->layoutCalculator->calculate($payload);
        } catch (Throwable $e) {
            return redirect()
                ->route('exam-sessions.design.edit', $examSession)
                ->withInput()
                ->withErrors(['layout' => $e->getMessage()]);
        }

        $cardSetting = $examSession->cardSetting()->firstOrCreate([], CardSetting::defaultValues());
        $cardSetting->update($payload);

        return redirect()
            ->route('exam-sessions.design.edit', $examSession)
            ->with('success', 'Pengaturan desain kartu dan tata letak cetak berhasil disimpan.');
    }

    private function makeFallbackStudent(
        string $participantNumber,
        string $name,
        string $nis,
        string $nisn,
        string $className,
        string $roomName
    ): Student {
        $student = new Student();
        $student->participant_number = $participantNumber;
        $student->student_name = $name;
        $student->nis = $nis;
        $student->nisn = $nisn;
        $student->class_name = $className;
        $student->room_name = $roomName;

        return $student;
    }
}