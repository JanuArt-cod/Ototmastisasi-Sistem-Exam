<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\SeatingPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Str;
use RuntimeException;

class SeatingPdfService
{
    public function __construct(
        private readonly QrCodeService $qrCodeService
    ) {
    }

    /**
     * Membangun objek DomPDF berisi Daftar Peserta Ruangan, Denah Duduk, dan Kartu Meja Kecil ber-QR.
     */
    public function buildPdf(ExamSession $examSession, SeatingPlan $plan, array $options = []): DomPdfWrapper
    {
        @ini_set('memory_limit', '512M');

        $allowedDocTypes = ['all', 'roster', 'desk_cards', 'floor_plan'];
        $documentType = in_array($options['document_type'] ?? 'all', $allowedDocTypes, true)
            ? (string) $options['document_type']
            : 'all';

        $roomNumberFilter = (int) ($options['room_number'] ?? 0);
        $customQrUrl = trim((string) ($options['qr_custom_url'] ?? ''));

        $layout = $plan->resolvedLayoutSettings();
        // Default 88 x 35 mm agar muat 2 kolom x 7 baris = 14 kartu dalam 1 lembar A4 Portrait
        $cardWidthMm = max(60.0, min(95.0, (float) ($options['card_width_mm'] ?? 88.0)));
        $cardHeightMm = max(28.0, min(55.0, (float) ($options['card_height_mm'] ?? 35.0)));
        $floorColumns = max(2, min(4, (int) ($options['floor_columns'] ?? ($layout['floor_columns'] ?? 3))));

        // Margin kertas A4 Portrait (210mm x 297mm)
        $marginTopMm = 8.0;
        $marginRightMm = 10.0;
        $marginBottomMm = 8.0;
        $marginLeftMm = 10.0;
        $gapXMm = 4.0;
        $gapYMm = 3.0;

        $printableWidthMm = 210.0 - $marginLeftMm - $marginRightMm;   // 190 mm
        $printableHeightMm = 297.0 - $marginTopMm - $marginBottomMm; // 281 mm

        // Hitung otomatis jumlah kartu meja kecil yang muat per baris dan per halaman A4
        $cardsPerRow = max(1, (int) floor(($printableWidthMm + $gapXMm) / ($cardWidthMm + $gapXMm)));
        // Sisakan 10mm di atas halaman kartu meja untuk judul & petunjuk potong
        $usableCardHeightMm = $printableHeightMm - 10.0;
        $cardsPerColumn = max(1, (int) floor(($usableCardHeightMm + $gapYMm) / ($cardHeightMm + $gapYMm)));
        $cardsPerPage = $cardsPerRow * $cardsPerColumn;

        $query = $plan->assignments()
            ->orderBy('room_number')
            ->orderBy('seat_number');

        if ($roomNumberFilter > 0) {
            $query->where('room_number', $roomNumberFilter);
        }

        $assignments = $query->get();

        if ($assignments->isEmpty()) {
            throw new RuntimeException('Tidak ditemukan data penempatan peserta untuk ruangan yang dipilih.');
        }

        $roomsPayload = [];
        $groupedByRoom = $assignments->groupBy('room_number');

        foreach ($groupedByRoom as $roomNum => $roomAssignments) {
            $firstRow = $roomAssignments->first();
            $roomName = (string) $firstRow->room_name;

            $qrTargetUrl = $this->resolveQrTargetUrl(
                $customQrUrl,
                $examSession,
                $plan,
                (int) $roomNum,
                $roomName
            );
            $qrDataUri = $this->qrCodeService->toSvgDataUri($qrTargetUrl, 4);

            // Susun data meja (2 kursi per meja) untuk denah cetak ruangan
            $desks = $roomAssignments
                ->sortBy([
                    ['desk_number', 'asc'],
                    ['seat_position', 'asc'],
                ])
                ->groupBy('desk_number')
                ->map(function ($seats, $deskNum) {
                    return [
                        'desk_number' => (int) $deskNum,
                        'seat_1' => $seats->firstWhere('seat_position', 1),
                        'seat_2' => $seats->firstWhere('seat_position', 2),
                    ];
                })
                ->values();

            $deskRows = $desks->chunk($floorColumns);
            $cardPages = $roomAssignments->values()->chunk($cardsPerPage);

            $roomsPayload[] = [
                'room_number' => (int) $roomNum,
                'room_name' => $roomName,
                'total_students' => $roomAssignments->count(),
                'total_desks' => $desks->count(),
                'qr_data_uri' => $qrDataUri,
                'floor_plan_url' => $qrTargetUrl,
                'roster' => $roomAssignments->values(),
                'desk_rows' => $deskRows,
                'card_pages' => $cardPages,
            ];
        }

        $pdf = Pdf::setOption([
            'dpi' => 96,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
        ])->loadView('seating.pdf-package', [
            'session' => $examSession,
            'plan' => $plan,
            'documentType' => $documentType,
            'roomsPayload' => $roomsPayload,
            'floorColumns' => $floorColumns,
            'cardWidthMm' => $cardWidthMm,
            'cardHeightMm' => $cardHeightMm,
            'cardsPerRow' => $cardsPerRow,
            'gapXMm' => $gapXMm,
            'gapYMm' => $gapYMm,
            'marginTopMm' => $marginTopMm,
            'marginRightMm' => $marginRightMm,
            'marginBottomMm' => $marginBottomMm,
            'marginLeftMm' => $marginLeftMm,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    public function resolveQrTargetUrl(
        string $customQrUrl,
        ExamSession $examSession,
        SeatingPlan $plan,
        int $roomNumber,
        string $roomName
    ): string {
        if ($customQrUrl !== '') {
            $roomSlug = Str::slug($roomName);

            return str_replace(
                ['{room}', '{room_slug}', '{room_name}'],
                [(string) $roomNumber, $roomSlug, rawurlencode($roomName)],
                $customQrUrl
            );
        }

        return route('exam-sessions.seating.floor-plan', [
            'examSession' => $examSession->id,
            'seatingPlan' => $plan->id,
            'room' => $roomNumber,
        ]);
    }

    public function buildFilename(ExamSession $examSession, SeatingPlan $plan, string $documentType, int $roomNumber = 0): string
    {
        $slugSession = preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $examSession->session_name);
        $slugSession = trim((string) $slugSession, '-') ?: 'Sesi-Ujian';

        $typeSuffix = match ($documentType) {
            'roster' => 'Daftar-Peserta-Ruangan',
            'desk_cards' => 'Kartu-Meja-QR',
            'floor_plan' => 'Denah-Ruangan',
            default => 'Paket-Ruangan-Lengkap',
        };

        $roomSuffix = $roomNumber > 0 ? '-Ruang-' . $roomNumber : '-Semua-Ruang';

        return $typeSuffix . '-' . $slugSession . '-v' . $plan->version_number . $roomSuffix . '.pdf';
    }
}