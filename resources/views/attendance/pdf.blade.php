<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Hadir Peserta - {{ $data['session']['exam_title'] }}</title>
    @php
        $options = $data['options'];
        $scheduleGroups = $data['schedule_groups'];
        $rooms = $data['rooms'];
        $isA4 = ($options['paper_size'] ?? 'f4_landscape') === 'a4_landscape';
        $pageCssSize = $isA4 ? 'A4 landscape' : '14in 8.5in';

        $totalSubjects = max(1, (int) ($data['summary']['total_subjects'] ?? 1));
        $hasSchedules = ((int) ($data['summary']['total_subjects'] ?? 0)) > 0;

        // Proporsi persentase identik dengan pratinjau UI
        $noWidthPct = $hasSchedules ? 2.6 : 5.0;
        $partNumWidthPct = $hasSchedules ? 11.4 : 25.0;
        $nameWidthPct = $hasSchedules ? 23.5 : 70.0;
        $subjWidthPct = $hasSchedules ? round(62.5 / $totalSubjects, 4) : 0;

        $subjFontPt = $totalSubjects > 18 ? 6.0 : ($totalSubjects > 14 ? 6.6 : 7.0);
    @endphp
    <style>
        @page {
            size: {{ $pageCssSize }};
            margin: 14pt 20pt 14pt 20pt;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            color: #0f172a;
            font-size: 8pt;
        }
        .page-break {
            page-break-after: always;
        }
        .doc-header {
            text-align: center;
            line-height: 1.24;
            padding-bottom: 5pt;
            margin-bottom: 5pt;
            border-bottom: 1.5pt solid #000000;
        }
        .doc-header-title {
            font-size: 10.5pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
            color: #000000;
            margin: 0;
        }
        .doc-header-sub {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
            margin: 0;
        }
        .room-bar {
            margin: 3pt 0 4pt 0;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
            letter-spacing: 0.3pt;
        }
        .att-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1pt solid #000000;
        }
        .att-table th,
        .att-table td {
            border: 0.6pt solid #1e293b;
            color: #000000;
            padding: 1.5pt 3pt;
        }
        .att-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
        }
        .th-identity {
            font-size: 8pt;
            vertical-align: middle;
        }
        .th-day {
            font-size: 6.6pt;
            line-height: 1.15;
            padding: 2.5pt 2pt;
            word-wrap: break-word;
            border-bottom: 0.8pt solid #000000;
        }
        .th-subj {
            height: 62pt;
            padding: 0 !important;
            vertical-align: middle;
            text-align: center;
            border-bottom: 1pt solid #000000;
        }
        .v-subject-text {
            display: inline-block;
            width: 58pt;
            margin-left: -18pt;
            margin-right: -18pt;
            transform: rotate(-90deg);
            font-size: {{ $subjFontPt }}pt;
            font-weight: bold;
            line-height: 1.05;
            text-align: center;
            word-wrap: break-word;
        }
        .day-group-end {
            border-right: 1.1pt solid #000000 !important;
        }
        .td-row {
            height: 16.2pt;
            font-size: 7.8pt;
            vertical-align: middle;
            background-color: #ffffff;
        }
        .td-center {
            text-align: center;
        }
        .td-part-num {
            text-align: center;
            font-size: 7.5pt;
            white-space: nowrap;
            padding-left: 2pt !important;
            padding-right: 2pt !important;
        }
        .td-name {
            text-align: left;
            padding-left: 5pt !important;
            padding-right: 4pt !important;
            white-space: nowrap;
            overflow: hidden;
        }
        .td-paraf {
            padding: 0 !important;
            background-color: #ffffff;
        }
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 9pt;
            page-break-inside: avoid;
        }
        .sign-table td {
            border: none;
            vertical-align: top;
            font-size: 8.2pt;
            color: #000000;
            padding: 0;
            line-height: 1.25;
        }
        .sign-space {
            height: 36pt;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    @foreach ($rooms as $roomIndex => $room)
        @php
            $isLastRoom = ($roomIndex === count($rooms) - 1);
        @endphp

        @foreach ($room['pages'] as $pageIndex => $page)
            @php
                $isLastPageOfRoom = ($pageIndex === count($room['pages']) - 1);
                $needPageBreak = ! ($isLastRoom && $isLastPageOfRoom);
            @endphp

            <div class="doc-header">
                <div class="doc-header-title">DAFTAR HADIR PESERTA</div>
                <div class="doc-header-sub">{{ strtoupper($data['session']['exam_title']) }}</div>
                <div class="doc-header-sub">{{ strtoupper($data['session']['school_name']) }}</div>
                @if ($data['session']['academic_year'] !== '')
                    <div class="doc-header-sub">TAHUN PELAJARAN {{ strtoupper($data['session']['academic_year']) }}</div>
                @endif
            </div>

            <div class="room-bar">{{ strtoupper($room['room_name']) }}</div>

            <table class="att-table" width="100%">
                <thead>
                    <tr>
                        <th rowspan="2" class="th-identity" width="{{ $noWidthPct }}%" style="width: {{ $noWidthPct }}%;">No</th>
                        <th rowspan="2" class="th-identity" width="{{ $partNumWidthPct }}%" style="width: {{ $partNumWidthPct }}%;">No Peserta</th>
                        <th rowspan="2" class="th-identity {{ $hasSchedules ? 'day-group-end' : '' }}" width="{{ $nameWidthPct }}%" style="width: {{ $nameWidthPct }}%;">Nama Peserta</th>
                        @foreach ($scheduleGroups as $gIdx => $group)
                            @php
                                $isLastDayGroup = ($gIdx === count($scheduleGroups) - 1);
                                $dayGroupPct = round($subjWidthPct * $group['colspan'], 4);
                            @endphp
                            <th
                                colspan="{{ $group['colspan'] }}"
                                width="{{ $dayGroupPct }}%"
                                style="width: {{ $dayGroupPct }}%;"
                                class="th-day {{ $isLastDayGroup ? '' : 'day-group-end' }}"
                            >
                                {{ $group['day_date_label'] }}
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($scheduleGroups as $gIdx => $group)
                            @php
                                $isLastDayGroup = ($gIdx === count($scheduleGroups) - 1);
                                $subjCount = count($group['subjects']);
                            @endphp
                            @foreach ($group['subjects'] as $sIdx => $subj)
                                @php
                                    $isLastSubjInDay = ($sIdx === $subjCount - 1) && ! $isLastDayGroup;
                                @endphp
                                <th
                                    width="{{ $subjWidthPct }}%"
                                    style="width: {{ $subjWidthPct }}%;"
                                    class="th-subj {{ $isLastSubjInDay ? 'day-group-end' : '' }}"
                                >
                                    <div class="v-subject-text">{{ $subj['subject_name'] }}</div>
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($page['rows'] as $row)
                        <tr>
                            <td class="td-row td-center" width="{{ $noWidthPct }}%" style="width: {{ $noWidthPct }}%;">{{ $row['row_number'] }}</td>
                            <td class="td-row td-part-num" width="{{ $partNumWidthPct }}%" style="width: {{ $partNumWidthPct }}%;">{{ $row['participant_number'] }}</td>
                            <td class="td-row td-name {{ $hasSchedules ? 'day-group-end' : '' }}" width="{{ $nameWidthPct }}%" style="width: {{ $nameWidthPct }}%;">{{ $row['student_name'] }}</td>
                            @foreach ($scheduleGroups as $gIdx => $group)
                                @php
                                    $isLastDayGroup = ($gIdx === count($scheduleGroups) - 1);
                                    $subjCount = count($group['subjects']);
                                @endphp
                                @foreach ($group['subjects'] as $sIdx => $subj)
                                    @php
                                        $isLastSubjInDay = ($sIdx === $subjCount - 1) && ! $isLastDayGroup;
                                    @endphp
                                    <td
                                        width="{{ $subjWidthPct }}%"
                                        style="width: {{ $subjWidthPct }}%;"
                                        class="td-row td-paraf {{ $isLastSubjInDay ? 'day-group-end' : '' }}"
                                    >&nbsp;</td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- TANDA TANGAN HANYA PADA HALAMAN TERAKHIR MASING-MASING RUANG --}}
            @if ($page['is_last_page'])
                @php
                    $cityText = $options['issue_city'] !== '' ? $options['issue_city'] : '................';
                    $datePart = $options['issue_date_text'] !== '' ? $options['issue_date_text'] . ' ' : '................ ';
                    $yearText = $options['issue_year'] !== '' ? $options['issue_year'] : date('Y');
                @endphp
                <table class="sign-table">
                    <tr>
                        <td style="width: 7%;"></td>
                        <td style="width: 45%;">
                            <div>&nbsp;</div>
                            <div>{{ $options['chair_title'] }}</div>
                            <div class="sign-space"></div>
                            <div class="sign-name">
                                {{ $options['chair_name'] !== '' ? $options['chair_name'] : '........................................' }}
                            </div>
                        </td>
                        <td style="width: 14%;"></td>
                        <td style="width: 34%;">
                            <div>{{ $cityText }}, {{ $datePart }}{{ $yearText }}</div>
                            <div>{{ $options['secretary_title'] }}</div>
                            <div class="sign-space"></div>
                            <div class="sign-name">
                                {{ $options['secretary_name'] !== '' ? $options['secretary_name'] : '........................................' }}
                            </div>
                        </td>
                    </tr>
                </table>
            @endif

            @if ($needPageBreak)
                <div class="page-break"></div>
            @endif
        @endforeach
    @endforeach
</body>
</html>