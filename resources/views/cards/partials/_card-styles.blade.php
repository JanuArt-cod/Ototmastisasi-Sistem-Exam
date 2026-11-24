@php
    $cardWidth = (float) ($layout['card_width_mm'] ?? 90.0);
    $cardHeight = (float) ($layout['card_height_mm'] ?? 55.0);
    $borderColor = $layout['border_color'] ?? '#1e293b';
    $headerBg = $layout['header_bg_color'] ?? '#f8fafc';
    $accentColor = $layout['accent_color'] ?? '#0f172a';
    $photoWidth = (float) ($layout['photo_width_mm'] ?? 17.0);
    $photoHeight = (float) ($layout['photo_height_mm'] ?? 22.0);
    $scheduleFontPt = (float) ($layout['schedule_font_size_pt'] ?? 6.5);
    $denseScheduleFontPt = min($scheduleFontPt, 5.6);
@endphp

<style>
    /* =====================================================================
       1. STYLESHEET KARTU 1 SISI (MODE STANDAR - .uk-*)
       ===================================================================== */
    .uk-card {
        width: {{ $cardWidth }}mm;
        height: {{ $cardHeight }}mm;
        border: 0.35mm solid {{ $borderColor }};
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
        border-bottom: 0.35mm solid {{ $borderColor }};
    }

    .uk-logo-td {
        width: 11mm;
        text-align: center;
        vertical-align: middle;
        padding: 0.9mm 0 0.9mm 1.8mm;
    }

    .uk-logo-img {
        max-width: 8.5mm;
        max-height: 8.5mm;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    .uk-head-td {
        text-align: center;
        vertical-align: middle;
        padding: 0.9mm 2mm;
    }

    .uk-school {
        font-size: 7.2pt;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.1;
        color: {{ $accentColor }};
    }

    .uk-addr {
        font-size: 5pt;
        color: #334155;
        line-height: 1.1;
        margin-top: 0.18mm;
    }

    .uk-title {
        font-size: 6.5pt;
        font-weight: 700;
        text-transform: uppercase;
        color: {{ $accentColor }};
        line-height: 1.1;
        margin-top: 0.35mm;
        padding-top: 0.3mm;
        border-top: 0.2mm solid #cbd5e1;
    }

    .uk-exam {
        font-size: 5.4pt;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.1;
        margin-top: 0.15mm;
    }

    .uk-body {
        width: 100%;
        border-collapse: collapse;
        padding: 0.9mm 2mm 0 2mm;
        box-sizing: border-box;
    }

    .uk-photo-td-left {
        width: {{ $photoWidth + 2 }}mm;
        vertical-align: top;
        padding-right: 1.5mm;
    }

    .uk-photo-td-right {
        width: {{ $photoWidth + 2 }}mm;
        vertical-align: top;
        padding-left: 1.5mm;
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
        font-size: 5.5pt;
        font-weight: 700;
        color: #64748b;
        padding-top: {{ max(2, ($photoHeight / 2) - 2) }}mm;
    }

    .uk-info {
        width: 100%;
        border-collapse: collapse;
    }

    .uk-info td {
        padding: 0.18mm 0;
        font-size: 5.8pt;
        line-height: 1.1;
        vertical-align: top;
        color: #0f172a;
    }

    .uk-lbl {
        width: 18.5mm;
        color: #334155;
    }

    .uk-sep {
        width: 2.2mm;
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
        font-size: 4.9pt;
        color: #475569;
        line-height: 1.1;
        padding-right: 2mm;
    }

    .uk-sig {
        width: 34mm;
        vertical-align: bottom;
        text-align: center;
        font-size: 5.2pt;
        color: #1e293b;
        line-height: 1.08;
    }

    .uk-sig-box {
        height: 4.4mm;
        margin: 0.15mm auto;
        text-align: center;
    }

    .uk-sig-img {
        max-width: 21mm;
        max-height: 4.4mm;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    .uk-sig-name {
        font-size: 5.4pt;
        font-weight: 700;
        text-decoration: underline;
        color: #0f172a;
        line-height: 1.08;
    }

    /* =====================================================================
       2. STYLESHEET KARTU 2 SISI (FRONT SIDE & BACK SIDE BERPASANGAN - .ds-*)
       ===================================================================== */
    .ds-card {
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

    /* --- SISI DEPAN (FRONT SIDE) --- */
    .ds-front-header {
        width: 100%;
        border-collapse: collapse;
        background-color: {{ $headerBg }};
        border-bottom: 0.4mm solid {{ $borderColor }};
    }

    .ds-front-logo-cell {
        width: 10.5mm;
        vertical-align: middle;
        text-align: center;
        padding: 0.8mm 0 0.8mm 1.8mm;
    }

    .ds-front-logo {
        max-width: 8.6mm;
        max-height: 8.6mm;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    .ds-front-school-cell {
        vertical-align: middle;
        text-align: center;
        padding: 0.8mm 1.8mm;
    }

    .ds-front-foundation {
        font-size: 5.1pt;
        font-weight: 700;
        letter-spacing: 0.15pt;
        text-transform: uppercase;
        color: #334155;
        line-height: 1.08;
    }

    .ds-front-school {
        font-size: 7.1pt;
        font-weight: 700;
        letter-spacing: 0.2pt;
        text-transform: uppercase;
        color: {{ $accentColor }};
        line-height: 1.1;
        margin-top: 0.12mm;
    }

    .ds-front-accreditation {
        font-size: 5.1pt;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.08;
        margin-top: 0.1mm;
    }

    .ds-front-address {
        font-size: 4.8pt;
        color: #475569;
        line-height: 1.08;
        margin-top: 0.12mm;
    }

    .ds-front-banner {
        text-align: center;
        padding: 0.55mm 2mm;
        background-color: #f8fafc;
        border-bottom: 0.22mm solid #cbd5e1;
    }

    .ds-front-card-title {
        font-size: 6.5pt;
        font-weight: 700;
        letter-spacing: 0.35pt;
        text-transform: uppercase;
        color: {{ $accentColor }};
        line-height: 1.1;
    }

    .ds-front-exam-title {
        font-size: 5.5pt;
        font-weight: 700;
        letter-spacing: 0.1pt;
        text-transform: uppercase;
        color: #334155;
        line-height: 1.1;
        margin-top: 0.12mm;
    }

    .ds-front-body {
        width: 100%;
        border-collapse: collapse;
        padding: 0.85mm 2.2mm 0 2.2mm;
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
        padding: 0.18mm 0;
        font-size: 5.8pt;
        line-height: 1.1;
        vertical-align: top;
        color: #0f172a;
    }

    .ds-front-label {
        width: 18.5mm;
        color: #334155;
        font-weight: 500;
    }

    .ds-front-sep {
        width: 2.2mm;
        text-align: center;
        color: #334155;
    }

    .ds-front-val {
        font-weight: 700;
        padding-left: 0.4mm;
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
        padding-right: 2mm;
    }

    .ds-front-note {
        font-size: 4.8pt;
        font-style: italic;
        color: #475569;
        line-height: 1.1;
    }

    .ds-front-sign-cell {
        width: 39mm;
        vertical-align: bottom;
        text-align: left;
    }

    .ds-front-sign-city {
        font-size: 5.1pt;
        color: #1e293b;
        line-height: 1.1;
        margin-bottom: 0.12mm;
    }

    .ds-front-sign-role {
        font-size: 5.1pt;
        color: #1e293b;
        line-height: 1.1;
        margin-bottom: 0.15mm;
    }

    .ds-front-sign-media {
        position: relative;
        height: 4.6mm;
        margin: 0.1mm 0 0.2mm 0;
    }

    .ds-front-sign-img {
        max-width: 22mm;
        max-height: 4.6mm;
        object-fit: contain;
        position: relative;
        z-index: 2;
    }

    .ds-front-stamp-img {
        max-width: 10.5mm;
        max-height: 4.6mm;
        object-fit: contain;
        position: absolute;
        left: -2mm;
        top: 0;
        opacity: 0.82;
        z-index: 1;
    }

    .ds-front-sign-name {
        font-size: 5.4pt;
        font-weight: 700;
        text-decoration: underline;
        color: #0f172a;
        line-height: 1.1;
        margin-bottom: 0.12mm;
    }

    .ds-front-sign-nip {
        font-size: 4.9pt;
        color: #334155;
        line-height: 1.08;
    }

    /* --- SISI BELAKANG (BACK SIDE - JADWAL UJIAN) --- */
    .ds-back-header {
        background-color: {{ $headerBg }};
        border-bottom: 0.35mm solid {{ $borderColor }};
        text-align: center;
        font-size: 5.8pt;
        font-weight: 700;
        letter-spacing: 0.3pt;
        text-transform: uppercase;
        color: {{ $accentColor }};
        padding: 0.5mm 1.5mm;
        line-height: 1.05;
    }

    .ds-back-body {
        padding: 0.65mm 1.4mm;
        box-sizing: border-box;
    }

    .ds-schedule-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .ds-schedule-table th {
        background-color: {{ $headerBg }};
        border: 0.2mm solid {{ $borderColor }};
        font-size: {{ max(4.9, $scheduleFontPt - 0.4) }}pt;
        font-weight: 700;
        letter-spacing: 0.15pt;
        text-transform: uppercase;
        color: {{ $accentColor }};
        padding: 0.24mm 0.45mm;
        line-height: 1.02;
        text-align: center;
    }

    .ds-schedule-table td {
        border: 0.2mm solid {{ $borderColor }};
        font-size: {{ $scheduleFontPt }}pt;
        line-height: 1.02;
        padding: 0.14mm 0.5mm;
        color: #0f172a;
        vertical-align: middle;
        word-wrap: break-word;
    }

    .ds-schedule-table.ds-schedule-dense th {
        font-size: {{ max(4.8, $denseScheduleFontPt - 0.3) }}pt;
        padding: 0.2mm 0.4mm;
    }

    .ds-schedule-table.ds-schedule-dense td {
        font-size: {{ $denseScheduleFontPt }}pt;
        line-height: 1.0;
        padding: 0.08mm 0.45mm;
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
        padding-left: 0.8mm !important;
    }

    .ds-schedule-empty {
        text-align: center;
        font-size: 6.2pt;
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
        width: {{ (float) ($layout['gap_x_mm'] ?? 4.0) }}mm;
        padding: 0;
    }
</style>