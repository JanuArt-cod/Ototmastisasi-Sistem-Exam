<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Paket Dokumen Ruangan - {{ $session->session_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: {{ $marginTopMm }}mm {{ $marginRightMm }}mm {{ $marginBottomMm }}mm {{ $marginLeftMm }}mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9pt;
            color: #0f172a;
        }
        .page-break {
            page-break-after: always;
        }
        .doc-header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            margin-bottom: 8pt;
            padding-bottom: 5pt;
        }
        .doc-header-table td {
            vertical-align: middle;
        }
        .school-title {
            font-size: 11.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 2pt 0;
        }
        .exam-subtitle {
            font-size: 9pt;
            font-weight: bold;
            color: #1e293b;
            margin: 0 0 2pt 0;
        }
        .room-banner {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-size: 9pt;
            font-weight: bold;
            padding: 2.5pt 8pt;
            border-radius: 3pt;
            margin-top: 2pt;
        }
        .qr-header-box {
            text-align: center;
            border: 1px solid #cbd5e1;
            padding: 3pt 5pt;
            border-radius: 3pt;
            background: #f8fafc;
        }
        .qr-header-caption {
            font-size: 6pt;
            color: #334155;
            margin-top: 1pt;
            font-weight: bold;
        }

        /* TABEL DAFTAR PESERTA RUANGAN */
        .roster-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .roster-table thead {
            display: table-header-group;
        }
        .roster-table th {
            background: #1e293b;
            color: #ffffff;
            font-weight: bold;
            padding: 4.5pt 5pt;
            border: 1px solid #0f172a;
            text-align: left;
        }
        .roster-table td {
            padding: 3.8pt 5pt;
            border: 1px solid #94a3b8;
            vertical-align: middle;
        }
        .roster-table tr {
            page-break-inside: avoid;
        }
        .roster-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .mono-code {
            font-family: "DejaVu Sans Mono", monospace;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12pt;
            page-break-inside: avoid;
        }
        .sign-table td {
            font-size: 8.5pt;
            vertical-align: top;
        }

        /* DENAH DUDUK RUANGAN */
        .front-board {
            width: 100%;
            background: #0f172a;
            color: #ffffff;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            padding: 5pt 0;
            margin-bottom: 8pt;
            letter-spacing: 0.5pt;
        }
        .floor-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5pt;
        }
        .floor-grid-table tr {
            page-break-inside: avoid;
        }
        .floor-desk-cell {
            border: 1.5px solid #334155;
            vertical-align: top;
            padding: 0;
            background: #ffffff;
        }
        .floor-desk-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .floor-desk-title {
            background: #e2e8f0;
            font-weight: bold;
            font-size: 8pt;
            padding: 2.5pt 4pt;
            border-bottom: 1px solid #64748b;
            text-align: center;
        }
        .floor-seat-td {
            width: 50%;
            padding: 3.5pt 4.5pt;
            vertical-align: top;
            font-size: 7.3pt;
            line-height: 1.22;
        }
        .floor-seat-td:first-child {
            border-right: 1px dashed #94a3b8;
        }
        .seat-badge {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 6.8pt;
            padding: 1pt 3.5pt;
            border-radius: 2pt;
        }

        /* KARTU MEJA UKURAN KECIL + QR CODE (14 KARTU PER LEMBAR A4) */
        .cut-guide-bar {
            font-size: 7.3pt;
            color: #334155;
            margin-bottom: 2.5mm;
            border-bottom: 1px dashed #94a3b8;
            padding-bottom: 1.5mm;
        }
        .cards-sheet-table {
            border-collapse: separate;
            border-spacing: {{ $gapXMm }}mm {{ $gapYMm }}mm;
            margin-left: -{{ $gapXMm }}mm;
            margin-top: -{{ $gapYMm }}mm;
        }
        .cards-sheet-table tr {
            page-break-inside: avoid;
        }
        .desk-card-cell {
            width: {{ $cardWidthMm }}mm;
            height: {{ $cardHeightMm }}mm;
            border: 1px dashed #475569;
            padding: 0;
            vertical-align: top;
            background: #ffffff;
        }
        .desk-card-empty {
            width: {{ $cardWidthMm }}mm;
            height: {{ $cardHeightMm }}mm;
            border: none;
        }
        .desk-card-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .desk-card-top {
            background: #0f172a;
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: bold;
            padding: 1.2mm 2mm;
        }
        .desk-card-body-left {
            padding: 1.4mm 1.6mm 1.2mm 2mm;
            vertical-align: middle;
        }
        .desk-card-body-right {
            width: 21mm;
            padding: 1.2mm 1.5mm 1.2mm 1mm;
            vertical-align: middle;
            text-align: center;
            border-left: 1px dotted #cbd5e1;
        }
        .card-seat-pill {
            display: inline-block;
            background: #e2e8f0;
            color: #0f172a;
            font-weight: bold;
            font-size: 6.5pt;
            padding: 0.3mm 1.4mm;
            border-radius: 2pt;
        }
        .card-desk-number {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8.2pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0.7mm 0 0.6mm 0;
        }
        .card-student-name {
            font-weight: bold;
            color: #0f172a;
            line-height: 1.12;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .card-student-class {
            font-size: 6.8pt;
            color: #334155;
            margin-top: 0.6mm;
        }
        .card-qr-label {
            font-size: 5.2pt;
            font-weight: bold;
            color: #334155;
            margin-top: 0.5mm;
            line-height: 1.05;
        }
    </style>
</head>
<body>
    @foreach ($roomsPayload as $roomIndex => $room)
        @php
            $hasNextRoom = $roomIndex < (count($roomsPayload) - 1);
            $showRoster = in_array($documentType, ['all', 'roster'], true);
            $showFloorPlan = in_array($documentType, ['all', 'floor_plan'], true);
            $showDeskCards = in_array($documentType, ['all', 'desk_cards'], true);
        @endphp

        {{-- ============================================================== --}}
        {{-- BAGIAN 1: DAFTAR PESERTA PADA RUANGAN                          --}}
        {{-- ============================================================== --}}
        @if ($showRoster)
            <table class="doc-header-table">
                <tr>
                    <td style="width: 82%;">
                        <div class="school-title">{{ $session->school_name ?: 'PANITIA UJIAN SEKOLAH' }}</div>
                        <div class="exam-subtitle">
                            DAFTAR PESERTA &amp; NOMOR MEJA UJIAN &bull; {{ strtoupper($session->exam_title ?: $session->session_name) }}
                        </div>
                        <div style="font-size: 8pt; color: #334155;">
                            Sesi: {{ $session->session_name }}
                            @if ($session->academic_year)
                                &bull; Tahun Pelajaran {{ $session->academic_year }}
                            @endif
                        </div>
                        <div class="room-banner">
                            {{ strtoupper($room['room_name']) }} &mdash; {{ $room['total_students'] }} PESERTA ({{ $room['total_desks'] }} MEJA)
                        </div>
                    </td>
                    <td style="width: 18%; text-align: right;">
                        <div class="qr-header-box" style="display: inline-block;">
                            <img src="{{ $room['qr_data_uri'] }}" alt="QR Denah" style="width: 17mm; height: 17mm; display: block; margin: 0 auto;">
                            <div class="qr-header-caption">QR DENAH {{ strtoupper($room['room_name']) }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="roster-table">
                <thead>
                    <tr>
                        <th style="width: 6%;" class="text-center">Urut</th>
                        <th style="width: 12%;" class="text-center">Meja / Kursi</th>
                        <th style="width: 22%;">Nomor Meja Peserta</th>
                        <th style="width: 16%;">No. Peserta Asli</th>
                        <th style="width: 26%;">Nama Lengkap Siswa</th>
                        <th style="width: 10%;" class="text-center">Kelas</th>
                        <th style="width: 8%;" class="text-center">Ket.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($room['roster'] as $item)
                        <tr>
                            <td class="text-center"><strong>{{ $item->seat_number }}</strong></td>
                            <td class="text-center">Meja {{ $item->desk_number }} (K{{ $item->seat_position }})</td>
                            <td class="mono-code">{{ $item->desk_participant_number }}</td>
                            <td>{{ $item->participant_number_snapshot }}</td>
                            <td><strong>{{ $item->student_name_snapshot }}</strong></td>
                            <td class="text-center">{{ $item->class_name_snapshot }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="sign-table">
                <tr>
                    <td style="width: 62%;">
                        <strong>Catatan:</strong> Nomor meja peserta ditempel pada masing-masing meja sesuai nomor urut ruangan.
                        Pindai QR Code pada kartu meja untuk melihat denah digital.
                    </td>
                    <td style="width: 38%; text-align: center;">
                        Pengawas Ruang {{ $room['room_name'] }},<br><br><br>
                        ( ___________________________ )
                    </td>
                </tr>
            </table>

            @if ($showFloorPlan || $showDeskCards || $hasNextRoom)
                <div class="page-break"></div>
            @endif
        @endif

        {{-- ============================================================== --}}
        {{-- BAGIAN 2: DENAH TEMPAT DUDUK RUANGAN                           --}}
        {{-- ============================================================== --}}
        @if ($showFloorPlan)
            <table class="doc-header-table">
                <tr>
                    <td style="width: 82%;">
                        <div class="school-title">{{ $session->school_name ?: 'PANITIA UJIAN SEKOLAH' }}</div>
                        <div class="exam-subtitle">
                            DENAH TEMPAT DUDUK PESERTA UJIAN &bull; {{ strtoupper($session->exam_title ?: $session->session_name) }}
                        </div>
                        <div class="room-banner">
                            {{ strtoupper($room['room_name']) }} &mdash; {{ $room['total_students'] }} PESERTA ({{ $room['total_desks'] }} MEJA)
                        </div>
                    </td>
                    <td style="width: 18%; text-align: right;">
                        <div class="qr-header-box" style="display: inline-block;">
                            <img src="{{ $room['qr_data_uri'] }}" alt="QR Denah" style="width: 17mm; height: 17mm; display: block; margin: 0 auto;">
                            <div class="qr-header-caption">SCAN DENAH DIGITAL</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="front-board">
                DEPAN RUANGAN &bull; PAPAN TULIS &amp; MEJA PENGAWAS UJIAN
            </div>

            @php $colWidthPercent = round(100 / $floorColumns, 2); @endphp
            <table class="floor-grid-table">
                @foreach ($room['desk_rows'] as $deskRow)
                    <tr>
                        @foreach ($deskRow as $desk)
                            <td class="floor-desk-cell" style="width: {{ $colWidthPercent }}%;">
                                <table class="floor-desk-inner">
                                    <tr>
                                        <td colspan="2" class="floor-desk-title">
                                            MEJA {{ $desk['desk_number'] }}
                                        </td>
                                    </tr>
                                    <tr>
                                        @php $s1 = $desk['seat_1']; @endphp
                                        <td class="floor-seat-td">
                                            @if ($s1)
                                                <span class="seat-badge">#{{ $s1->seat_number }}</span>
                                                <span style="color: #475569;">K1</span><br>
                                                <strong>{{ $s1->student_name_snapshot }}</strong><br>
                                                <span class="mono-code" style="font-size: 6.8pt;">{{ $s1->desk_participant_number }}</span><br>
                                                <span style="color: #334155;">Kls: {{ $s1->class_name_snapshot }}</span>
                                            @else
                                                <span style="color: #94a3b8;">Kosong</span>
                                            @endif
                                        </td>

                                        @php $s2 = $desk['seat_2']; @endphp
                                        <td class="floor-seat-td">
                                            @if ($s2)
                                                <span class="seat-badge">#{{ $s2->seat_number }}</span>
                                                <span style="color: #475569;">K2</span><br>
                                                <strong>{{ $s2->student_name_snapshot }}</strong><br>
                                                <span class="mono-code" style="font-size: 6.8pt;">{{ $s2->desk_participant_number }}</span><br>
                                                <span style="color: #334155;">Kls: {{ $s2->class_name_snapshot }}</span>
                                            @else
                                                <span style="color: #94a3b8;">Kosong</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        @endforeach

                        @for ($pad = $deskRow->count(); $pad < $floorColumns; $pad++)
                            <td style="width: {{ $colWidthPercent }}%; border: none;"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>

            @if ($showDeskCards || $hasNextRoom)
                <div class="page-break"></div>
            @endif
        @endif

        {{-- ============================================================== --}}
        {{-- BAGIAN 3: KARTU MEJA UKURAN KECIL + QR CODE DENAH DIGITAL      --}}
        {{-- ============================================================== --}}
        @if ($showDeskCards)
            @foreach ($room['card_pages'] as $pageIdx => $pageCards)
                <div class="cut-guide-bar">
                    <strong>KARTU MEJA PESERTA &bull; {{ strtoupper($room['room_name']) }}</strong>
                    (Hal. {{ $pageIdx + 1 }}/{{ $room['card_pages']->count() }} &bull; {{ $pageCards->count() }} kartu)
                    &mdash; Ukuran {{ number_format($cardWidthMm, 0) }}&times;{{ number_format($cardHeightMm, 0) }} mm.
                    Cetak skala 100% &amp; gunting garis putus-putus.
                </div>

                @php
                    $cardRows = $pageCards->chunk($cardsPerRow);
                    $qrSizeMm = max(14, min(19, $cardHeightMm - 15));
                    $bodyHeightMm = max(18, $cardHeightMm - 6);
                @endphp

                <table class="cards-sheet-table">
                    @foreach ($cardRows as $rowCards)
                        <tr>
                            @foreach ($rowCards as $cardItem)
                                @php
                                    $nameLen = mb_strlen((string) $cardItem->student_name_snapshot);
                                    $nameFontPt = $nameLen > 28 ? '6.8pt' : ($nameLen > 20 ? '7.4pt' : '8pt');
                                @endphp
                                <td class="desk-card-cell">
                                    <table class="desk-card-inner">
                                        <tr>
                                            <td colspan="2" class="desk-card-top">
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <tr>
                                                        <td style="text-align: left; color: #ffffff;">
                                                            {{ strtoupper($room['room_name']) }}
                                                        </td>
                                                        <td style="text-align: right; color: #fde047;">
                                                            MEJA {{ $cardItem->desk_number }} &bull; KURSI {{ $cardItem->seat_position }}
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="desk-card-body-left" style="height: {{ $bodyHeightMm }}mm;">
                                                <div>
                                                    <span class="card-seat-pill">NO. URUT #{{ $cardItem->seat_number }}</span>
                                                </div>
                                                <div class="card-desk-number">{{ $cardItem->desk_participant_number }}</div>
                                                <div class="card-student-name" style="font-size: {{ $nameFontPt }};">
                                                    {{ $cardItem->student_name_snapshot }}
                                                </div>
                                                <div class="card-student-class">
                                                    Kelas: <strong>{{ $cardItem->class_name_snapshot }}</strong>
                                                </div>
                                            </td>
                                            <td class="desk-card-body-right" style="height: {{ $bodyHeightMm }}mm;">
                                                <img
                                                    src="{{ $room['qr_data_uri'] }}"
                                                    alt="QR Denah"
                                                    style="width: {{ $qrSizeMm }}mm; height: {{ $qrSizeMm }}mm; display: block; margin: 0 auto;"
                                                >
                                                <div class="card-qr-label">SCAN DENAH</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            @endforeach

                            @for ($emptyCol = $rowCards->count(); $emptyCol < $cardsPerRow; $emptyCol++)
                                <td class="desk-card-empty"></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>

                @if ($pageIdx < ($room['card_pages']->count() - 1))
                    <div class="page-break"></div>
                @endif
            @endforeach

            @if ($hasNextRoom)
                <div class="page-break"></div>
            @endif
        @endif
    @endforeach
</body>
</html>