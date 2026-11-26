<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Ujian - {{ $session->session_name }}</title>
    @include('cards.partials._card-styles', ['layout' => $layout])
    <style>
        @page {
            margin: {{ $calc['margin_top_mm'] }}mm {{ $calc['margin_right_mm'] }}mm {{ $calc['margin_bottom_mm'] }}mm {{ $calc['margin_left_mm'] }}mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background-color: #ffffff;
        }

        table.sheet-grid {
            border-collapse: collapse;
            margin: 0 auto;
            padding: 0;
        }

        table.sheet-grid tr {
            page-break-inside: avoid;
        }

        table.sheet-grid td.card-slot {
            vertical-align: top;
            padding: 0;
        }

        table.ds-pair-sheet {
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        table.ds-pair-sheet td.ds-side-cell {
            vertical-align: top;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
        }

        /* .pdf-system-footer {
            position: fixed;
            bottom: 2.5mm;
            left: 10mm;
            right: 10mm;
            text-align: center;
            font-size: 6.5pt;
            color: #64748b;
            font-style: italic;
        } */
    </style>
</head>
<body>
    @php
        $isDoubleSided = ($layout['card_side_mode'] ?? 'single') === 'double_sided';
        $halfGapX = round($calc['gap_x_mm'] / 2, 2);
        $halfGapY = round($calc['gap_y_mm'] / 2, 2);
        $showCut = (bool) ($layout['show_cut_lines'] ?? true);
        $totalPages = count($pages);
        $sessionSchedules = $schedules ?? collect();
    @endphp

    @foreach ($pages as $pageIndex => $pageItems)
        <table class="sheet-grid">
            @for ($r = 0; $r < $calc['rows']; $r++)
                <tr>
                    @for ($c = 0; $c < $calc['columns']; $c++)
                        @php
                            $slotIndex = ($r * $calc['columns']) + $c;
                            $item = $pageItems[$slotIndex] ?? null;

                            $padTop = $r > 0 ? $halfGapY : 0;
                            $padBottom = $r < ($calc['rows'] - 1) ? $halfGapY : 0;
                            $padLeft = $c > 0 ? $halfGapX : 0;
                            $padRight = $c < ($calc['columns'] - 1) ? $halfGapX : 0;

                            $borderRightStyle = ($showCut and $c < ($calc['columns'] - 1)) ? '0.25mm dashed #94a3b8' : 'none';
                            $borderBottomStyle = ($showCut and $r < ($calc['rows'] - 1)) ? '0.25mm dashed #94a3b8' : 'none';
                        @endphp
                        <td
                            class="card-slot"
                            style="
                                padding: {{ $padTop }}mm {{ $padRight }}mm {{ $padBottom }}mm {{ $padLeft }}mm;
                                border-right: {{ $borderRightStyle }};
                                border-bottom: {{ $borderBottomStyle }};
                            "
                        >
                            @if ($item !== null)
                                @if ($isDoubleSided)
                                    @php
                                        $studentModel = $item['student'];
                                        $studentModel->photo_data_uri = $item['assets']['photo_src'] ?? null;
                                        $centerBorderStyle = $showCut ? '0.25mm dashed #94a3b8' : 'none';
                                    @endphp
                                    <table class="ds-pair-sheet">
                                        <tr>
                                            <td class="ds-side-cell" style="padding-right: {{ $halfGapX }}mm; border-right: {{ $centerBorderStyle }};">
                                                @include('cards.partials._front-card', [
                                                    'session' => $session,
                                                    'student' => $studentModel,
                                                    'layout' => $layout,
                                                    'logoUrl' => $item['assets']['logo_src'] ?? null,
                                                    'signatureUrl' => $item['assets']['signature_src'] ?? null,
                                                    'stampUrl' => $item['assets']['stamp_src'] ?? null,
                                                ])
                                            </td>
                                            <td class="ds-side-cell" style="padding-left: {{ $halfGapX }}mm;">
                                                @include('cards.partials._back-card', [
                                                    'session' => $session,
                                                    'layout' => $layout,
                                                    'schedules' => $sessionSchedules,
                                                ])
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    @include('cards.partials._single-card', [
                                        'session' => $session,
                                        'student' => $item['student'],
                                        'layout' => $layout,
                                        'assets' => $item['assets'],
                                    ])
                                @endif
                            @else
                                @php
                                    $emptyWidth = $isDoubleSided
                                        ? ($calc['unit_width_mm'] ?? (($calc['card_width_mm'] * 2) + $calc['gap_x_mm']))
                                        : $calc['card_width_mm'];
                                @endphp
                                <div style="width: {{ $emptyWidth }}mm; height: {{ $calc['card_height_mm'] }}mm;"></div>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </table>

        @if ($pageIndex < ($totalPages - 1))
            <div class="page-break"></div>
        @endif
    @endforeach

    {{-- <div class="pdf-system-footer">
        Kartu ujian ini dicetak secara otomatis oleh sistem asisten &bull; by januarramdhani
    </div> --}}
</body>
</html> 