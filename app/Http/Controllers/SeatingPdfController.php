<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\SeatingPlan;
use App\Services\SeatingPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SeatingPdfController extends Controller
{
    public function __construct(
        private readonly SeatingPdfService $pdfService
    ) {
    }

    public function saveQrUrl(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'qr_custom_url' => ['nullable', 'string', 'max:500'],
            'plan_id' => ['nullable', 'integer'],
        ]);

        $url = trim((string) ($validated['qr_custom_url'] ?? ''));
        $examSession->qr_floor_plan_url = $url !== '' ? $url : null;
        $examSession->save();

        $routeParams = ['examSession' => $examSession->id];
        if (! empty($validated['plan_id'])) {
            $routeParams['plan_id'] = (int) $validated['plan_id'];
        }

        return redirect()
            ->route('exam-sessions.seating.index', $routeParams)
            ->with('success', $url !== ''
                ? 'Link tujuan QR Code berhasil disimpan untuk sesi ujian ini.'
                : 'Link tujuan QR Code dikosongkan (kembali menggunakan link denah lokal).');
    }

    public function export(Request $request, ExamSession $examSession, SeatingPlan $seatingPlan): Response
    {
        abort_if($seatingPlan->exam_session_id !== $examSession->id, 404);

        $validated = $request->validate([
            'document_type' => ['nullable', 'in:all,roster,desk_cards,floor_plan'],
            'room_number' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'card_width_mm' => ['nullable', 'numeric', 'min:60', 'max:95'],
            'card_height_mm' => ['nullable', 'numeric', 'min:28', 'max:55'],
            'floor_columns' => ['nullable', 'integer', 'min:2', 'max:4'],
            'qr_custom_url' => ['nullable', 'string', 'max:500'],
            'download' => ['nullable', 'in:0,1'],
        ]);

        $documentType = $validated['document_type'] ?? 'all';
        $roomNumber = (int) ($validated['room_number'] ?? 0);

        if ($request->exists('qr_custom_url')) {
            $submittedUrl = trim((string) ($validated['qr_custom_url'] ?? ''));
            $newSavedValue = $submittedUrl !== '' ? $submittedUrl : null;
            if ($examSession->qr_floor_plan_url !== $newSavedValue) {
                $examSession->qr_floor_plan_url = $newSavedValue;
                $examSession->save();
            }
            $effectiveQrUrl = $submittedUrl;
        } else {
            $effectiveQrUrl = trim((string) ($examSession->qr_floor_plan_url ?? ''));
        }

        try {
            $pdf = $this->pdfService->buildPdf($examSession, $seatingPlan, [
                'document_type' => $documentType,
                'room_number' => $roomNumber,
                'card_width_mm' => $validated['card_width_mm'] ?? 88,
                'card_height_mm' => $validated['card_height_mm'] ?? 35,
                'floor_columns' => $validated['floor_columns'] ?? 3,
                'qr_custom_url' => $effectiveQrUrl,
            ]);

            $filename = $this->pdfService->buildFilename($examSession, $seatingPlan, $documentType, $roomNumber);

            if (($validated['download'] ?? '0') === '1') {
                return $pdf->download($filename);
            }

            return $pdf->stream($filename);
        } catch (Throwable $e) {
            return redirect()
                ->route('exam-sessions.seating.index', [
                    'examSession' => $examSession->id,
                    'plan_id' => $seatingPlan->id,
                ])
                ->with('error', $e->getMessage());
        }
    }
}