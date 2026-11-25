<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $data['options']['header_title'] }}</title>
    @php
        $options = $data['options'];
        $sessionInfo = $data['session'];
        $days = $data['days'];
        $showProctors = (bool) $options['show_proctor_columns'];
        $isA4 = ($options['paper_size'] ?? 'f4_portrait') === 'a4_portrait';
        $pageCssSize = $isA4 ? 'A4 portrait' : '215mm 330mm';
    @endphp
    <style>
        @page {
            size: {{ $pageCssSize }};
            margin: 9mm 9mm 10mm 9mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 7pt;
            line-height: 1.18;
        }
        .doc-header {
            text-align: center;
            margin-bottom: 6pt;
            line-height: 1.24;
        }
        .doc-header-line {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            padding: 0;
        }
        .sched-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }
        .sched-table + .sched-table {
            margin-top: -0.7pt;
        }
        .day-table-unit {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .sched-table th,
        .sched-table td {
            border: 0.7pt solid #000000;
            color: #000000;
            padding: 1.5pt 3pt;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .sched-table th {
            font-weight: bold;
            text-align: center;
            font-size: 6.9pt;
            padding: 3.5pt 2pt;
            background-color: #ffffff;
            text-transform: uppercase;
        }
        /* Lebar kolom eksplisit per sel agar dipatuhi DomPDF */
        .col-no { width: 3.5%; }
        .col-day { width: 13.5%; }
        .col-jam { width: 5%; }
        .col-time { width: 11%; }
        .col-subj { width: 16%; }
        .col-room { width: 5%; }
        .col-proc { width: 26%; }
        .col-comm { width: 20%; }

        .col-s-no { width: 6%; }
        .col-s-day { width: 25%; }
        .col-s-jam { width: 10%; }
        .col-s-time { width: 19%; }
        .col-s-subj { width: 40%; }

        .no-top {
            border-top: none !important;
        }
        .no-bot {
            border-bottom: none !important;
        }
        .td-center {
            text-align: center;
        }
        .td-day-date {
            text-align: center;
            font-size: 6.9pt;
            line-height: 1.18;
        }
        .td-time {
            text-align: center;
            font-size: 6.6pt;
        }
        .td-subject {
            text-align: center;
            font-size: 6.9pt;
            line-height: 1.18;
        }
        .td-room {
            text-align: center;
            font-size: 6.8pt;
            padding-top: 1.1pt !important;
            padding-bottom: 1.1pt !important;
        }
        .td-proctor {
            text-align: left;
            font-size: 6.5pt;
            line-height: 1.15;
            padding: 1.1pt 3.5pt !important;
        }
        .td-committee {
            text-align: left;
            font-size: 6.5pt;
            line-height: 1.15;
            padding: 1.1pt 3.5pt !important;
        }
        .footer-sign {
            margin-top: 8pt;
            text-align: right;
            font-size: 8.2pt;
            font-weight: bold;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <div class="doc-header-line">{{ strtoupper($options['header_title']) }}</div>
        <div class="doc-header-line">{{ strtoupper($sessionInfo['school_name']) }}</div>
        @if ($sessionInfo['academic_year'] !== '')
            <div class="doc-header-line">TAHUN PELAJARAN {{ strtoupper($sessionInfo['academic_year']) }}</div>
        @endif
    </div>

    {{-- Header Tabel Tunggal (hanya di halaman pertama) --}}
    <table class="sched-table">
        <tbody>
            @if ($showProctors)
                <tr>
                    <th class="col-no">NO</th>
                    <th class="col-day">HARI,<br>TANGGAL</th>
                    <th class="col-jam">JAM<br>KE-</th>
                    <th class="col-time">WAKTU</th>
                    <th class="col-subj">MATA<br>PELAJARAN</th>
                    <th class="col-room">RUANG</th>
                    <th class="col-proc">PENGAWAS RUANG</th>
                    <th class="col-comm">PANITIA</th>
                </tr>
            @else
                <tr>
                    <th class="col-s-no">NO</th>
                    <th class="col-s-day">HARI, TANGGAL</th>
                    <th class="col-s-jam">JAM KE-</th>
                    <th class="col-s-time">WAKTU</th>
                    <th class="col-s-subj">MATA PELAJARAN</th>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Setiap hari dalam tabel mandiri agar tidak terbelah saat pindah halaman --}}
    @foreach ($days as $day)
        <table class="sched-table day-table-unit">
            <tbody>
                @if ($showProctors)
                    @php
                        $flatDayRows = [];
                        foreach ($day['slots'] as $slot) {
                            $roomCount = count($slot['room_rows']);
                            $midRoomIdx = (int) floor(($roomCount - 1) / 2);
                            foreach ($slot['room_rows'] as $rIdx => $rRow) {
                                $flatDayRows[] = [
                                    'jam_ke' => $slot['jam_ke'],
                                    'time_slot' => $slot['time_slot'],
                                    'subject_name' => $slot['subject_name'],
                                    'room_label' => $rRow['room_label'],
                                    'proctor_name' => $rRow['proctor_name'],
                                    'is_slot_first' => ($rIdx === 0),
                                    'is_slot_last' => ($rIdx === $roomCount - 1),
                                    'is_slot_mid' => ($rIdx === $midRoomIdx),
                                ];
                            }
                        }

                        $totalDayRows = count($flatDayRows);
                        $midDayIdx = (int) floor(($totalDayRows - 1) / 2);
                        $committeeLines = $day['committee_lines'] ?? [];
                        $commCount = count($committeeLines);
                        $commStartIdx = $commCount > 0
                            ? max(0, (int) floor(($totalDayRows - $commCount) / 2))
                            : 0;
                    @endphp

                    @foreach ($flatDayRows as $dIdx => $row)
                        @php
                            $dayClass = '';
                            if ($totalDayRows > 1) {
                                if ($dIdx === 0) {
                                    $dayClass = 'no-bot';
                                } elseif ($dIdx === $totalDayRows - 1) {
                                    $dayClass = 'no-top';
                                } else {
                                    $dayClass = 'no-top no-bot';
                                }
                            }

                            $slotClass = '';
                            if (! ($row['is_slot_first'] && $row['is_slot_last'])) {
                                if ($row['is_slot_first']) {
                                    $slotClass = 'no-bot';
                                } elseif ($row['is_slot_last']) {
                                    $slotClass = 'no-top';
                                } else {
                                    $slotClass = 'no-top no-bot';
                                }
                            }

                            $commText = '';
                            if ($commCount > 0 && $dIdx >= $commStartIdx && ($dIdx - $commStartIdx) < $commCount) {
                                $commText = $committeeLines[$dIdx - $commStartIdx];
                            }
                        @endphp
                        <tr>
                            <td class="col-no td-center {{ $dayClass }}">
                                {{ $dIdx === $midDayIdx ? $day['day_number'] : '' }}
                            </td>
                            <td class="col-day td-day-date {{ $dayClass }}">
                                {{ $dIdx === $midDayIdx ? $day['day_date_label'] : '' }}
                            </td>
                            <td class="col-jam td-center {{ $slotClass }}">
                                {{ $row['is_slot_mid'] ? $row['jam_ke'] : '' }}
                            </td>
                            <td class="col-time td-time {{ $slotClass }}">
                                {{ $row['is_slot_mid'] ? $row['time_slot'] : '' }}
                            </td>
                            <td class="col-subj td-subject {{ $slotClass }}">
                                {{ $row['is_slot_mid'] ? $row['subject_name'] : '' }}
                            </td>
                            <td class="col-room td-room">
                                {{ $row['room_label'] }}
                            </td>
                            <td class="col-proc td-proctor">
                                {{ $row['proctor_name'] !== '' ? $row['proctor_name'] : ' ' }}
                            </td>
                            <td class="col-comm td-committee {{ $dayClass }}">
                                {{ $commText !== '' ? $commText : ' ' }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    @php
                        $slotCount = count($day['slots']);
                        $midSlotIdx = (int) floor(($slotCount - 1) / 2);
                    @endphp
                    @foreach ($day['slots'] as $sIdx => $slot)
                        @php
                            $dayClass = '';
                            if ($slotCount > 1) {
                                if ($sIdx === 0) {
                                    $dayClass = 'no-bot';
                                } elseif ($sIdx === $slotCount - 1) {
                                    $dayClass = 'no-top';
                                } else {
                                    $dayClass = 'no-top no-bot';
                                }
                            }
                        @endphp
                        <tr>
                            <td class="col-s-no td-center {{ $dayClass }}">
                                {{ $sIdx === $midSlotIdx ? $day['day_number'] : '' }}
                            </td>
                            <td class="col-s-day td-day-date {{ $dayClass }}">
                                {{ $sIdx === $midSlotIdx ? $day['day_date_label'] : '' }}
                            </td>
                            <td class="col-s-jam td-center">{{ $slot['jam_ke'] }}</td>
                            <td class="col-s-time td-time">{{ $slot['time_slot'] }}</td>
                            <td class="col-s-subj td-subject">{{ $slot['subject_name'] }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    @endforeach

    @if ($options['footer_signatory'] !== '')
        <div class="footer-sign">{{ $options['footer_signatory'] }}</div>
    @endif
</body>
</html>