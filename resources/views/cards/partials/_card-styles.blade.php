@php
    $cardWidth = (float) ($layout['card_width_mm'] ?? 105.0);
    $cardHeight = (float) ($layout['card_height_mm'] ?? 75.0);
    $borderColor = $layout['border_color'] ?? '#1e293b';
    $headerBg = $layout['header_bg_color'] ?? '#f8fafc';
    $accentColor = $layout['accent_color'] ?? '#0f172a';
    $photoWidth = (float) ($layout['photo_width_mm'] ?? 20.0);
    $photoHeight = (float) ($layout['photo_height_mm'] ?? 26.0);
    $scheduleFontPt = (float) ($layout['schedule_font_size_pt'] ?? 6.8);

    $isB3Scale = ($cardWidth >= 100.0 && $cardHeight >= 68.0);
    $denseScheduleFontPt = $isB3Scale ? min($scheduleFontPt, 6.0) : min($scheduleFontPt, 5.6);

    // Skala proporsional antara ukuran standar lama (90x55 mm) dan Plastik B3 (105x75 mm)
    $logoCellWidth = $isB3Scale ? '13mm' : '11mm';
    $logoMaxSize = $isB3Scale ? '10.5mm' : '8.5mm';
    $schoolFont = $isB3Scale ? '8.4pt' : '7.1pt';
    $foundationFont = $isB3Scale ? '6.0pt' : '5.1pt';
    $accredFont = $isB3Scale ? '5.8pt' : '5.1pt';
    $addrFont = $isB3Scale ? '5.5pt' : '4.8pt';
    $cardTitleFont = $isB3Scale ? '7.6pt' : '6.5pt';
    $examTitleFont = $isB3Scale ? '6.4pt' : '5.5pt';
    $fieldFont = $isB3Scale ? '6.9pt' : '5.8pt';
    $fieldLabelWidth = $isB3Scale ? '22mm' : '18.5mm';
    $fieldPadY = $isB3Scale ? '0.45mm' : '0.18mm';
    $sigCellWidth = $isB3Scale ? '44mm' : '39mm';
    $sigFont = $isB3Scale ? '6.0pt' : '5.1pt';
    $sigMediaHeight = $isB3Scale ? '7.2mm' : '4.6mm';
    $sigImgMaxWidth = $isB3Scale ? '26mm' : '22mm';
    $stampMaxWidth = $isB3Scale ? '13mm' : '10.5mm';
    $sigNameFont = $isB3Scale ? '6.3pt' : '5.4pt';
    $sigNipFont = $isB3Scale ? '5.6pt' : '4.9pt';
    $noteFont = $isB3Scale ? '5.5pt' : '4.8pt';
    $backHeaderFont = $isB3Scale ? '6.8pt' : '5.8pt';
    $backHeaderPadY = $isB3Scale ? '0.9mm' : '0.5mm';
    $backBodyPad = $isB3Scale ? '1.1mm 1.8mm' : '0.65mm 1.4mm';
    $schedCellPadY = $isB3Scale ? '0.35mm' : '0.14mm';
    $schedDensePadY = $isB3Scale ? '0.22mm' : '0.08mm';
@endphp

<style>
    /* =====================================================================
       1. STYLESHEET KARTU 1 SISI (MODE STANDAR - .uk-*)
       ===================================================================== */
    .uk-card {
        width: {{ $cardWidth }}mm;
        height: {{ $cardHeight }}mm;
        border: 0.38mm solid {{ $borderColor }};
        background-color: #ffffff;
        color: #0f172a;
        font-family: Helvetica, Arial, DejaVu Sans, sans-serif;
        box-sizing: border-box;
        overflow: hidden;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .uk-head {
        width: 100%;
        border-collapse: collapse;
        background-color: {{ $headerBg }};
        border-bottom: 0.38mm solid {{ $borderColor }};
    }

    .uk-logo-td {
        width: {{ $logoCellWidth }};
        text-align: center;
        vertical-align: middle;
        padding: {{ $isB3Scale ? '1.2mm 0 1.2mm 2.2mm' : '0.9mm 0 0.9mm 1.8mm' }};
    }

    .uk-logo-img {
        max-width: {{ $logoMaxSize }};
        max-height: {{ $logoMaxSize }};
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    .uk-head-td {
        text-align: center;
        vertical-align: middle;
        padding: {{ $isB3Scale ? '1.2mm 2.4mm' : '0.9mm 2mm' }};
    }

    .uk-school {
        font-size: {{ $schoolFont }};
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.12;
        color: {{ $accentColor }};
    }

    .uk-addr {
        font-size: {{ $addrFont }};
        color: #334155;
        line-height: 1.12;
        margin-top: 0.2mm;
    }

    .uk-title {
        font-size: {{ $cardTitleFont }};
        font-weight: 700;
        text-transform: uppercase;
        color: {{ $accentColor }};
        line-height: 1.12;
        margin-top: {{ $isB3Scale ? '0.6mm' : '0.35mm' }};
        padding-top: {{ $isB3Scale ? '0.5mm' : '0.3mm' }};
        border-top: 0.25mm solid #cbd5e1;
    }

    .uk-exam {
        font-size: {{ $examTitleFont }};
        font-weight: 600;
        color: #1e293b;
        line-height: 1.12;
        margin-top: 0.2mm;
    }

    .uk-body {
        width: 100%;
        border-collapse: collapse;
        padding: {{ $isB3Scale ? '1.5mm 2.6mm 0 2.6mm' : '0.9mm 2mm 0 2mm' }};
        box-sizing: border-box;
    }

    .uk-photo-td-left {
        width: {{ $photoWidth + 2.5 }}mm;
        vertical-align: top;
        padding-right: 2mm;
    }

    .uk-photo-td-right {
        width: {{ $photoWidth + 2.5 }}mm;
        vertical-align: top;
        padding-left: 2mm;
        text-align: right;
    }

    .uk-photo-box {
        width: {{ $photoWidth }}mm;
        height: {{ $photoHeight }}mm;
        border: 0.25mm solid #64748b;
        background-color: #f8fafc;
        text-align: center;
        overflow: hidden;
        box-sizing: border-box;
    }

    .uk-photo-box-right {
        margin-left: auto;
    }

    .uk-photo-img {
        width: {{ $photoWidth }}mm;
        height: {{ $photoHeight }}mm;
        object-fit: cover;
        display: block;
    }

    .uk-photo-empty {
        font-size: {{ $isB3Scale ? '6.2pt' : '5.5pt' }};
        font-weight: 700;
        color: #64748b;
        padding-top: {{ max(2, ($photoHeight / 2) - 2) }}mm;
    }

    .uk-info {
        width: 100%;
        border-collapse: collapse;
    }

    .uk-info td {
        padding: {{ $fieldPadY }} 0;
        font-size: {{ $fieldFont }};
        line-height: 1.15;
        vertical-align: top;
        color: #0f172a;
    }

    .uk-lbl {
        width: {{ $fieldLabelWidth }};
        color: #334155;
    }

    .uk-sep {
        width: 2.5mm;
        text-align: center;
    }

    .uk-val-num {
        font-weight: 700;
        color: {{ $accentColor }};
    }

    .uk-val-name {
        font-weight: 700;
        word-wrap: break-word;
    }

    .uk-val {
        font-weight: 500;
        word-wrap: break-word;
    }

    .uk-val-bold {
        font-weight: 700;
        word-wrap: break-word;
    }

    .uk-foot {
        width: 100%;
        border-collapse: collapse;
    }

    .uk-note {
        vertical-align: bottom;
        font-size: {{ $noteFont }};
        color: #475569;
        line-height: 1.15;
        padding-right: 2.4mm;
    }

    .uk-sig {
        width: {{ $sigCellWidth }};
        vertical-align: bottom;
        text-align: center;
        font-size: {{ $sigFont }};
        color: #1e293b;
        line-height: 1.12;
    }

    .uk-sig-box {
        height: {{ $sigMediaHeight }};
        margin: 0.25mm auto;
        text-align: center;
    }

    .uk-sig-img {
        max-width: {{ $sigImgMaxWidth }};
        max-height: {{ $sigMediaHeight }};
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    .uk-sig-name {
        font-size: {{ $sigNameFont }};
        font-weight: 700;
        text-decoration: underline;
        color: #0f172a;
        line-height: 1.12;
    }

    /* =====================================================================
       2. STYLESHEET KARTU 2 SISI (FRONT SIDE & BACK SIDE BERPASANGAN - .ds-*)
       ===================================================================== */
    .ds-card {
        width: {{ $cardWidth }}mm;
        height: {{ $cardHeight }}mm;
        border: 0.4mm solid {{ $borderColor }};
        background-color: #ffffff;
        color: #0f172a;
        font-family: Helvetica, Arial, DejaVu Sans, sans-serif;
        box-sizing: border-box;
        overflow: hidden;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    /* --- SISI DEPAN (FRONT SIDE) --- */
    .ds-front-header {
        width: 100%;
        border-collapse: collapse;
        background-color: {{ $headerBg }};
        border-bottom: 0.4mm solid {{ $borderColor }};
    }

    .ds-front-logo-cell {
        width: {{ $logoCellWidth }};
        vertical-align: middle;
        text-align: center;
        padding: {{ $isB3Scale ? '1.1mm 0 1.1mm 2.2mm' : '0.8mm 0 0.8mm 1.8mm' }};
    }

    .ds-front-logo {
        max-width: {{ $logoMaxSize }};
        max-height: {{ $logoMaxSize }};
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    .ds-front-school-cell {
        vertical-align: middle;
        text-align: center;
        padding: {{ $isB3Scale ? '1.1mm 2.2mm' : '0.8mm 1.8mm' }};
    }

    .ds-front-foundation {
        font-size: {{ $foundationFont }};
        font-weight: 700;
        letter-spacing: 0.15pt;
        text-transform: uppercase;
        color: #334155;
        line-height: 1.12;
    }

    .ds-front-school {
        font-size: {{ $schoolFont }};
        font-weight: 700;
        letter-spacing: 0.2pt;
        text-transform: uppercase;
        color: {{ $accentColor }};
        line-height: 1.14;
        margin-top: 0.15mm;
    }

    .ds-front-accreditation {
        font-size: {{ $accredFont }};
        font-weight: 700;
        color: #0f172a;
        line-height: 1.12;
        margin-top: 0.12mm;
    }

    .ds-front-address {
        font-size: {{ $addrFont }};
        color: #475569;
        line-height: 1.12;
        margin-top: 0.15mm;
    }

    .ds-front-banner {
        text-align: center;
        padding: {{ $isB3Scale ? '0.85mm 2.4mm' : '0.55mm 2mm' }};
        background-color: #f8fafc;
        border-bottom: 0.25mm solid #cbd5e1;
    }

    .ds-front-card-title {
        font-size: {{ $cardTitleFont }};
        font-weight: 700;
        letter-spacing: 0.35pt;
        text-transform: uppercase;
        color: {{ $accentColor }};
        line-height: 1.14;
    }

    .ds-front-exam-title {
        font-size: {{ $examTitleFont }};
        font-weight: 700;
        letter-spacing: 0.1pt;
        text-transform: uppercase;
        color: #334155;
        line-height: 1.14;
        margin-top: 0.15mm;
    }

    .ds-front-body {
        width: 100%;
        border-collapse: collapse;
        padding: {{ $isB3Scale ? '1.4mm 2.6mm 0 2.6mm' : '0.85mm 2.2mm 0 2.2mm' }};
        box-sizing: border-box;
    }

    .ds-front-fields-cell {
        vertical-align: top;
    }

    .ds-front-fields-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ds-front-fields-table td {
        padding: {{ $fieldPadY }} 0;
        font-size: {{ $fieldFont }};
        line-height: 1.16;
        vertical-align: top;
        color: #0f172a;
    }

    .ds-front-label {
        width: {{ $fieldLabelWidth }};
        color: #334155;
        font-weight: 500;
    }

    .ds-front-sep {
        width: 2.5mm;
        text-align: center;
        color: #334155;
    }

    .ds-front-val {
        font-weight: 700;
        padding-left: 0.5mm;
        word-wrap: break-word;
    }

    .ds-front-val-num {
        color: {{ $accentColor }};
        letter-spacing: 0.15pt;
    }

    .ds-front-bottom {
        width: 100%;
        border-collapse: collapse;
    }

    .ds-front-note-cell {
        vertical-align: bottom;
        padding-right: 2.4mm;
    }

    .ds-front-note {
        font-size: {{ $noteFont }};
        font-style: italic;
        color: #475569;
        line-height: 1.15;
    }

    .ds-front-sign-cell {
        width: {{ $sigCellWidth }};
        vertical-align: bottom;
        text-align: left;
    }

    .ds-front-sign-city {
        font-size: {{ $sigFont }};
        color: #1e293b;
        line-height: 1.12;
        margin-bottom: 0.15mm;
    }

    .ds-front-sign-role {
        font-size: {{ $sigFont }};
        color: #1e293b;
        line-height: 1.12;
        margin-bottom: 0.2mm;
    }

    .ds-front-sign-media {
        position: relative;
        height: {{ $sigMediaHeight }};
        margin: 0.15mm 0 0.25mm 0;
    }

    .ds-front-sign-img {
        max-width: {{ $sigImgMaxWidth }};
        max-height: {{ $sigMediaHeight }};
        object-fit: contain;
        position: relative;
        z-index: 2;
    }

    .ds-front-stamp-img {
        max-width: {{ $stampMaxWidth }};
        max-height: {{ $sigMediaHeight }};
        object-fit: contain;
        position: absolute;
        left: -2mm;
        top: 0;
        opacity: 0.82;
        z-index: 1;
    }

    .ds-front-sign-name {
        font-size: {{ $sigNameFont }};
        font-weight: 700;
        text-decoration: underline;
        color: #0f172a;
        line-height: 1.12;
        margin-bottom: 0.15mm;
    }

    .ds-front-sign-nip {
        font-size: {{ $sigNipFont }};
        color: #334155;
        line-height: 1.1;
    }

    /* --- SISI BELAKANG (BACK SIDE - JADWAL UJIAN) --- */
    .ds-back-header {
        background-color: {{ $headerBg }};
        border-bottom: 0.38mm solid {{ $borderColor }};
        text-align: center;
        font-size: {{ $backHeaderFont }};
        font-weight: 700;
        letter-spacing: 0.35pt;
        text-transform: uppercase;
        color: {{ $accentColor }};
        padding: {{ $backHeaderPadY }} 2mm;
        line-height: 1.1;
    }

    .ds-back-body {
        padding: {{ $backBodyPad }};
        box-sizing: border-box;
    }

    .ds-schedule-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .ds-schedule-table th {
        background-color: {{ $headerBg }};
        border: 0.22mm solid {{ $borderColor }};
        font-size: {{ max(5.2, $scheduleFontPt - 0.35) }}pt;
        font-weight: 700;
        letter-spacing: 0.15pt;
        text-transform: uppercase;
        color: {{ $accentColor }};
        padding: {{ $isB3Scale ? '0.45mm 0.6mm' : '0.24mm 0.45mm' }};
        line-height: 1.08;
        text-align: center;
    }

    .ds-schedule-table td {
        border: 0.22mm solid {{ $borderColor }};
        font-size: {{ $scheduleFontPt }}pt;
        line-height: 1.08;
        padding: {{ $schedCellPadY }} 0.6mm;
        color: #0f172a;
        vertical-align: middle;
        word-wrap: break-word;
    }

    .ds-schedule-table.ds-schedule-dense th {
        font-size: {{ max(5.0, $denseScheduleFontPt - 0.3) }}pt;
        padding: {{ $isB3Scale ? '0.32mm 0.5mm' : '0.2mm 0.4mm' }};
    }

    .ds-schedule-table.ds-schedule-dense td {
        font-size: {{ $denseScheduleFontPt }}pt;
        line-height: 1.04;
        padding: {{ $schedDensePadY }} 0.55mm;
    }

    .ds-schedule-table tr.ds-day-alt td {
        background-color: #f8fafc;
    }

    .ds-schedule-no {
        text-align: center;
        font-weight: 700;
        color: {{ $accentColor }};
    }

    .ds-schedule-day {
        font-weight: 600;
    }

    .ds-schedule-time {
        text-align: center;
    }

    .ds-schedule-subject {
        padding-left: 1mm !important;
    }

    .ds-schedule-empty {
        text-align: center;
        font-size: 6.8pt;
        color: #64748b;
        padding-top: {{ max(4, ($cardHeight / 2) - 4) }}mm;
    }

    /* --- PASANGAN CETAK BERDAMPINGAN (KIRI DEPAN, KANAN BELAKANG) --- */
    .ds-pair-table {
        border-collapse: collapse;
        margin: 0 auto;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .ds-pair-cell {
        padding: 0;
        vertical-align: top;
    }

    .ds-pair-gap {
        width: {{ (float) ($layout['gap_x_mm'] ?? 2.0) }}mm;
        padding: 0;
    }
</style>