@extends('layouts.app')

@section('title', 'Cetak Jadwal & Pengawas (WA) — ' . $session->session_name)

@section('content')
@php
    $options = $data['options'];
    $sessionInfo = $data['session'];
    $days = $data['days'];
    $showProctors = (bool) $options['show_proctor_columns'];
    $teacherRules = ! empty($options['teacher_rules'])
        ? $options['teacher_rules']
        : [
            ['name' => '', 'max_days' => '', 'allowed_days' => [], 'forbidden_subjects_text' => ''],
            ['name' => '', 'max_days' => '', 'allowed_days' => [], 'forbidden_subjects_text' => ''],
            ['name' => '', 'max_days' => '', 'allowed_days' => [], 'forbidden_subjects_text' => ''],
        ];
    $availableDaysMeta = collect($days)->map(function ($d) {
        return [
            'num' => (int) $d['day_number'],
            'short' => trim(explode(',', $d['day_date_label'])[0] ?? ('H' . $d['day_number'])),
            'label' => $d['day_date_label'],
        ];
    })->values()->all();
@endphp

<style>
    .share-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 10px;
        margin-bottom: 16px;
    }
    .share-tab-btn {
        padding: 8px 14px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-primary);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .share-tab-btn.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
    }
    .share-panel {
        display: none;
    }
    .share-panel.active {
        display: block;
    }
    .share-label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 5px;
    }
    .share-input,
    .share-select,
    .share-textarea {
        width: 100%;
        padding: 7px 10px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-family: inherit;
        background: #fff;
        color: var(--text-primary);
    }
    .share-input:focus,
    .share-select:focus,
    .share-textarea:focus {
        outline: none;
        border-color: #0f172a;
    }
    .day-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 7px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        font-size: 11.5px;
        background: #f8fafc;
        cursor: pointer;
        user-select: none;
    }
    .day-chip input {
        margin: 0;
        accent-color: #0f172a;
    }
    .rules-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }
    .rules-table th {
        background: #f8fafc;
        text-align: left;
        padding: 8px 10px;
        border: 1px solid var(--border);
        font-weight: 600;
    }
    .rules-table td {
        padding: 6px 8px;
        border: 1px solid var(--border);
        vertical-align: middle;
    }
    /* Pratinjau Dokumen Jadwal & Pengawas */
    .preview-canvas-wrap {
        overflow-x: auto;
        background: #f1f5f9;
        padding: 16px;
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
    }
    .schedule-paper {
        background: #ffffff;
        color: #000000;
        width: 100%;
        max-width: 820px;
        margin: 0 auto;
        padding: 24px 26px 28px 26px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
        font-family: Arial, Helvetica, sans-serif;
    }
    .paper-header {
        text-align: center;
        margin-bottom: 12px;
        line-height: 1.32;
    }
    .paper-header-line {
        font-size: 13.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: #000;
        letter-spacing: 0.2px;
    }
    .ref-table {
        width: 100%;
        border-collapse: collapse;
        border: 1.5px solid #000;
        font-size: 10.5px;
        color: #000;
        table-layout: fixed;
    }
    .ref-table th,
    .ref-table td {
        border: 1px solid #000;
        padding: 3px 5px;
        vertical-align: middle;
        color: #000;
    }
    .ref-table th {
        font-weight: 700;
        text-align: center;
        padding: 6px 4px;
        background: #fff;
        font-size: 10px;
        text-transform: uppercase;
    }
    .ref-td-center { text-align: center; }
    .ref-td-time { text-align: center; white-space: nowrap; font-size: 10px; }
    .ref-td-subject { text-align: center; line-height: 1.25; }
    .ref-td-room { text-align: center; padding: 2px 3px !important; }
    .ref-td-proctor { text-align: left; padding: 2px 6px !important; }
    .ref-td-committee {
        text-align: left;
        vertical-align: middle;
        padding: 4px 6px !important;
        line-height: 1.55;
    }
    .paper-footer {
        margin-top: 14px;
        text-align: right;
        font-size: 11px;
        font-weight: 700;
        color: #000;
    }
    .paper-system-footer {
        margin-top: 12px;
        padding-top: 6px;
        border-top: 1px dashed #94a3b8;
        text-align: left;
        font-size: 9.5px;
        font-style: italic;
        color: #475569;
    }
</style>

<div class="page-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;">
    <div>
        <h1 class="page-title">Jadwal Ujian &amp; Pengawas Ruang</h1>
        <p class="page-subtitle">
            Sesi: <strong>{{ $sessionInfo['session_name'] }}</strong> &bull;
            {{ $data['summary']['total_days'] }} Hari &bull;
            {{ $data['summary']['total_subjects'] }} Mapel &bull;
            {{ $data['summary']['total_rooms'] }} Ruang
        </p>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
        <a href="{{ route('exam-sessions.schedules.index', $session) }}" class="btn btn-secondary btn-sm">
            &larr; Edit Mata Pelajaran
        </a>
        @if (! empty($days))
            <button type="button" id="btnDownloadPng" class="btn btn-secondary btn-sm" onclick="downloadSchedulePng()">
                Unduh Gambar PNG (WA)
            </button>
            <button type="button" id="btnCopyWa" class="btn btn-secondary btn-sm" onclick="copyWhatsAppText()">
                Salin Teks WA
            </button>
            <a href="{{ route('exam-sessions.schedules.share.pdf', $session) }}" target="_blank" class="btn btn-primary btn-sm">
                Cetak / Buka PDF
            </a>
        @endif
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul style="margin: 0; padding-left: 18px;">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (empty($days))
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 36px 20px;">
            <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 14px;">
                Belum ada mata pelajaran pada jadwal sesi ujian ini. Tambahkan jadwal mata pelajaran terlebih dahulu.
            </p>
            <a href="{{ route('exam-sessions.schedules.index', $session) }}" class="btn btn-primary">
                Input Jadwal Mata Pelajaran
            </a>
        </div>
    </div>
@else
    {{-- Navigasi Tab Efisien --}}
    <div class="share-tabs">
        <button type="button" class="share-tab-btn active" data-tab="panel-preview" onclick="switchShareTab('panel-preview', this)">
            1. Pratinjau Hasil &amp; Teks WA
        </button>
        <button type="button" class="share-tab-btn" data-tab="panel-auto" onclick="switchShareTab('panel-auto', this)">
            2. Atur Guru &amp; Bagi Pengawas Otomatis
        </button>
        <button type="button" class="share-tab-btn" data-tab="panel-edit" onclick="switchShareTab('panel-edit', this)">
            3. Edit Manual, Panitia &amp; Ukuran Kertas
        </button>
    </div>

    {{-- TAB 1: PRATINJAU HASIL & TEKS WA --}}
    <div id="panel-preview" class="share-panel active">
        <div class="card" style="margin-bottom: 16px;">
            <div class="card-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px;">
                <span class="card-title">Pratinjau Dokumen Jadwal &amp; Pengawas</span>
                <span style="font-size: 12px; color: var(--text-secondary);">
                    Kertas: <strong>{{ \App\Services\ScheduleShareService::PAPER_PRESETS[$options['paper_size']]['label'] ?? 'F4 Portrait' }}</strong>
                </span>
            </div>
            <div class="card-body" style="padding: 12px;">
                <div class="preview-canvas-wrap">
                    <div id="scheduleCaptureArea" class="schedule-paper">
                        <div class="paper-header">
                            <div class="paper-header-line">{{ strtoupper($options['header_title']) }}</div>
                            <div class="paper-header-line">{{ strtoupper($sessionInfo['school_name']) }}</div>
                            @if ($sessionInfo['academic_year'] !== '')
                                <div class="paper-header-line">TAHUN PELAJARAN {{ strtoupper($sessionInfo['academic_year']) }}</div>
                            @endif
                        </div>

                        <table class="ref-table">
                            <thead>
                                @if ($showProctors)
                                    <tr>
                                        <th style="width: 34px;">NO</th>
                                        <th style="width: 112px;">HARI,<br>TANGGAL</th>
                                        <th style="width: 44px;">JAM<br>KE-</th>
                                        <th style="width: 82px;">WAKTU</th>
                                        <th style="width: 132px;">MATA<br>PELAJARAN</th>
                                        <th style="width: 48px;">RUANG</th>
                                        <th style="width: 185px;">PENGAWAS RUANG</th>
                                        <th style="width: 135px;">PANITIA</th>
                                    </tr>
                                @else
                                    <tr>
                                        <th style="width: 44px;">NO</th>
                                        <th style="width: 190px;">HARI, TANGGAL</th>
                                        <th style="width: 72px;">JAM KE-</th>
                                        <th style="width: 145px;">WAKTU</th>
                                        <th>MATA PELAJARAN</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody>
                                @foreach ($days as $day)
                                    @if ($showProctors)
                                        @foreach ($day['slots'] as $slotIdx => $slot)
                                            @foreach ($slot['room_rows'] as $roomIdx => $roomRow)
                                                <tr>
                                                    @if ($slotIdx === 0 && $roomIdx === 0)
                                                        <td rowspan="{{ $day['day_rowspan'] }}" class="ref-td-center">
                                                            {{ $day['day_number'] }}
                                                        </td>
                                                        <td rowspan="{{ $day['day_rowspan'] }}" class="ref-td-center">
                                                            {{ $day['day_date_label'] }}
                                                        </td>
                                                    @endif

                                                    @if ($roomIdx === 0)
                                                        <td rowspan="{{ $slot['slot_rowspan'] }}" class="ref-td-center">
                                                            {{ $slot['jam_ke'] }}
                                                        </td>
                                                        <td rowspan="{{ $slot['slot_rowspan'] }}" class="ref-td-time">
                                                            {{ $slot['time_slot'] }}
                                                        </td>
                                                        <td rowspan="{{ $slot['slot_rowspan'] }}" class="ref-td-subject">
                                                            {{ $slot['subject_name'] }}
                                                        </td>
                                                    @endif

                                                    <td class="ref-td-room">{{ $roomRow['room_label'] }}</td>
                                                    <td class="ref-td-proctor">{{ $roomRow['proctor_name'] }}</td>

                                                    @if ($slotIdx === 0 && $roomIdx === 0)
                                                        <td rowspan="{{ $day['day_rowspan'] }}" class="ref-td-committee">
                                                            @foreach ($day['committee_lines'] as $cName)
                                                                <div>{{ $cName }}</div>
                                                            @endforeach
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @else
                                        @foreach ($day['slots'] as $slotIdx => $slot)
                                            <tr>
                                                @if ($slotIdx === 0)
                                                    <td rowspan="{{ count($day['slots']) }}" class="ref-td-center">
                                                        {{ $day['day_number'] }}
                                                    </td>
                                                    <td rowspan="{{ count($day['slots']) }}" class="ref-td-center">
                                                        {{ $day['day_date_label'] }}
                                                    </td>
                                                @endif
                                                <td class="ref-td-center">{{ $slot['jam_ke'] }}</td>
                                                <td class="ref-td-time">{{ $slot['time_slot'] }}</td>
                                                <td class="ref-td-subject">{{ $slot['subject_name'] }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>

                        @if (! empty($floorPlanQr) || $options['footer_signatory'] !== '')
                            <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap;">
                                <div>
                                    @if (! empty($floorPlanQr))
                                        <div style="display: inline-flex; align-items: center; gap: 8px; border: 1px solid #334155; background: #f8fafc; padding: 5px 8px; border-radius: 3px;">
                                            <img src="{{ $floorPlanQr['qr_data_uri'] }}" alt="QR Denah Duduk Digital" style="width: 48px; height: 48px; display: block;">
                                            <div style="line-height: 1.3; text-align: left;">
                                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #000;">QR Denah Duduk Digital</div>
                                                <div style="font-size: 9px; color: #1e293b;">Pindai untuk cek posisi kursi &amp; peserta tiap ruang</div>
                                                <div style="font-size: 8.5px; color: #475569;">{{ $floorPlanQr['version_label'] }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @if ($options['footer_signatory'] !== '')
                                    <div class="paper-footer" style="margin-top: 0;">{{ $options['footer_signatory'] }}</div>
                                @endif
                            </div>
                        @endif
                        <div class="paper-system-footer">
                            *Dokumen ini di cetak secara otomatis Apps-Automated Examination Document System
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span class="card-title">Format Teks WhatsApp Siap Tempel</span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="copyWhatsAppText()">Salin Teks</button>
            </div>
            <div class="card-body">
                <textarea id="waTextOutput" rows="8" class="share-textarea" readonly style="font-family: monospace; font-size: 12px; background: #f8fafc;">{{ $data['whatsapp_text'] }}</textarea>
            </div>
        </div>
    </div>

    {{-- TAB 2: ATUR GURU & BAGI PENGAWAS OTOMATIS --}}
    <div id="panel-auto" class="share-panel">
        <div class="card">
            <div class="card-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px;">
                <span class="card-title">Aturan Pembagian Pengawas Ruang Otomatis</span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addTeacherRuleRow()">+ Tambah Baris Guru</button>
            </div>
            <div class="card-body">
                <form action="{{ route('exam-sessions.schedules.share.auto-assign', $session) }}" method="POST">
                    @csrf
                    <input type="hidden" name="rooms_text" value="{{ implode(', ', $options['rooms']) }}">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label for="max_days_per_teacher" class="share-label">
                                Default Maksimal Hari Mengawas <span style="font-weight: 400; color: var(--text-secondary);">(opsional)</span>
                            </label>
                            <input
                                type="number"
                                id="max_days_per_teacher"
                                name="max_days_per_teacher"
                                min="1"
                                max="30"
                                value="{{ old('max_days_per_teacher', $options['max_days_per_teacher']) }}"
                                placeholder="Kosongkan jika diatur per guru di tabel"
                                class="share-input"
                            >
                        </div>
                        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 12px; color: var(--text-secondary); line-height: 1.45;">
                            <strong>Aturan Otomatis Aktif:</strong><br>
                            1. Tim pengawas tetap dalam 1 hari &amp; <strong>di-rolling ruangnya</strong> tiap jam ujian.<br>
                            2. Hari berikutnya guru <strong>tidak mengulang ruang yang sudah diawasi</strong> &amp; tidak mengawas mapelnya sendiri.
                        </div>
                    </div>

                    <div style="overflow-x: auto; margin-bottom: 14px;">
                        <table class="rules-table" id="teacherRulesTable">
                            <thead>
                                <tr>
                                    <th style="width: 24%;">Nama Guru Pengawas</th>
                                    <th style="width: 11%;">Maks Hari</th>
                                    <th style="width: 37%;">Hanya Bisa di Hari (Kosongkan = Semua Hari)</th>
                                    <th style="width: 23%;">Mapel Diampu (Dilarang)</th>
                                    <th style="width: 5%; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="teacherRulesBody">
                                @foreach ($teacherRules as $idx => $rule)
                                    <tr>
                                        <td>
                                            <input
                                                type="text"
                                                name="teachers[{{ $idx }}][name]"
                                                value="{{ $rule['name'] ?? '' }}"
                                                placeholder="Contoh: Hj Ida Hamidah, S.Pd.I"
                                                class="share-input"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="teachers[{{ $idx }}][max_days]"
                                                value="{{ $rule['max_days'] ?? '' }}"
                                                min="1"
                                                max="30"
                                                placeholder="2 / 3"
                                                class="share-input"
                                            >
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                @foreach ($days as $d)
                                                    @php
                                                        $dNum = (int) $d['day_number'];
                                                        $shortDay = trim(explode(',', $d['day_date_label'])[0] ?? ('H' . $dNum));
                                                        $checked = in_array($dNum, (array) ($rule['allowed_days'] ?? []), true);
                                                    @endphp
                                                    <label class="day-chip" title="{{ $d['day_date_label'] }}">
                                                        <input
                                                            type="checkbox"
                                                            name="teachers[{{ $idx }}][allowed_days][]"
                                                            value="{{ $dNum }}"
                                                            {{ $checked ? 'checked' : '' }}
                                                        >
                                                        <span>H{{ $dNum }} ({{ $shortDay }})</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                name="teachers[{{ $idx }}][forbidden_subjects_text]"
                                                value="{{ $rule['forbidden_subjects_text'] ?? '' }}"
                                                placeholder="Mis: Matematika, IPA"
                                                class="share-input"
                                            >
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeTeacherRuleRow(this)" title="Hapus baris">&times;</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <details style="margin-bottom: 14px; font-size: 12.5px;">
                        <summary style="cursor: pointer; font-weight: 600; color: var(--text-secondary);">
                            Atau Tempel Daftar Guru Sekaligus (Format Teks Cepat)
                        </summary>
                        <div style="margin-top: 8px;">
                            <textarea
                                name="teacher_pool_text"
                                rows="4"
                                class="share-textarea"
                                placeholder="Format opsional: Nama Guru | MaksHari | Hari 1, Hari 3 | Mapel Diampu&#10;Contoh: Hj Ida Hamidah, S.Pd.I | 2 | Rabu, Sabtu | Bahasa Indonesia"
                            >{{ old('teacher_pool_text', $options['teacher_pool_text']) }}</textarea>
                        </div>
                    </details>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="addTeacherRuleRow()">+ Tambah Guru</button>
                        <button type="submit" class="btn btn-primary">Bagi Pengawas Ruang Otomatis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- TAB 3: EDIT MANUAL, PANITIA & UKURAN KERTAS --}}
    <div id="panel-edit" class="share-panel">
        <form action="{{ route('exam-sessions.schedules.share.update', $session) }}" method="POST">
            @csrf

            <div class="card" style="margin-bottom: 16px;">
                <div class="card-header">
                    <span class="card-title">Pengaturan Kertas, Judul &amp; Daftar Ruang</span>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label for="paper_size" class="share-label">Ukuran Kertas PDF</label>
                            <select id="paper_size" name="paper_size" class="share-select">
                                @foreach (\App\Services\ScheduleShareService::PAPER_PRESETS as $presetKey => $preset)
                                    <option value="{{ $presetKey }}" {{ $options['paper_size'] === $presetKey ? 'selected' : '' }}>
                                        {{ $preset['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="rooms_text" class="share-label">Daftar Nomor/Nama Ruang</label>
                            <input
                                type="text"
                                id="rooms_text"
                                name="rooms_text"
                                value="{{ old('rooms_text', implode(', ', $options['rooms'])) }}"
                                class="share-input"
                                required
                            >
                        </div>
                        <div>
                            <label for="header_title" class="share-label">Judul Baris Pertama</label>
                            <input
                                type="text"
                                id="header_title"
                                name="header_title"
                                value="{{ old('header_title', $options['header_title']) }}"
                                class="share-input"
                                required
                            >
                        </div>
                        <div>
                            <label for="footer_signatory" class="share-label">Teks Keterangan Bawah Kanan</label>
                            <input
                                type="text"
                                id="footer_signatory"
                                name="footer_signatory"
                                value="{{ old('footer_signatory', $options['footer_signatory']) }}"
                                class="share-input"
                            >
                        </div>
                    </div>

                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                        <input
                            type="checkbox"
                            name="show_proctor_columns"
                            value="1"
                            {{ $showProctors ? 'checked' : '' }}
                        >
                        <span>Tampilkan kolom Ruang, Pengawas Ruang &amp; Panitia (hilangkan centang untuk jadwal ringkas siswa/wali murid)</span>
                    </label>
                </div>
            </div>

            <div class="card" style="margin-bottom: 16px;">
                <div class="card-header">
                    <span class="card-title">Edit Manual Pengawas Ruang &amp; Panitia Piket Harian</span>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 12px;">
                        @foreach ($days as $day)
                            <div style="border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; background: #fff;">
                                <div style="font-weight: 700; font-size: 13px; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid var(--border);">
                                    Hari ke-{{ $day['day_number'] }} — {{ $day['day_date_label'] }}
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label class="share-label" style="font-size: 11.5px;">Panitia Piket Hari Ini (1 per baris)</label>
                                    <textarea
                                        name="committees[{{ $day['day_number'] }}]"
                                        rows="2"
                                        class="share-textarea"
                                        style="font-size: 12px;"
                                    >{{ implode("\n", $day['committee_lines']) }}</textarea>
                                </div>

                                @foreach ($day['slots'] as $slot)
                                    <div style="background: #f8fafc; border-radius: 6px; padding: 8px; margin-bottom: 8px;">
                                        <div style="font-size: 12px; font-weight: 600; margin-bottom: 6px;">
                                            Jam ke-{{ $slot['jam_ke'] }} ({{ $slot['time_slot'] }}) — {{ $slot['subject_name'] }}
                                        </div>
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
                                            @foreach ($slot['room_rows'] as $rRow)
                                                <div style="display: flex; align-items: center; gap: 5px;">
                                                    <span style="font-size: 11.5px; font-weight: 600; min-width: 24px;">R.{{ $rRow['room_label'] }}</span>
                                                    <input
                                                        type="text"
                                                        name="proctors[{{ $slot['schedule_id'] }}][{{ $rRow['room_label'] }}]"
                                                        value="{{ $rRow['proctor_name'] }}"
                                                        class="share-input"
                                                        style="padding: 4px 7px; font-size: 12px;"
                                                    >
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 14px;">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan &amp; Perbarui Pratinjau</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endif

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    let teacherRuleIndex = {{ count($teacherRules) }};
    const availableDaysMeta = @json($availableDaysMeta);

    function switchShareTab(panelId, btn) {
        document.querySelectorAll('.share-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.share-tab-btn').forEach(b => b.classList.remove('active'));
        const target = document.getElementById(panelId);
        if (target) target.classList.add('active');
        if (btn) btn.classList.add('active');
    }

    function addTeacherRuleRow() {
        const tbody = document.getElementById('teacherRulesBody');
        if (!tbody) return;
        const idx = teacherRuleIndex++;
        const dayCheckboxes = availableDaysMeta.map(d => `
            <label class="day-chip" title="${d.label}">
                <input type="checkbox" name="teachers[${idx}][allowed_days][]" value="${d.num}">
                <span>H${d.num} (${d.short})</span>
            </label>
        `).join('');

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="teachers[${idx}][name]" placeholder="Nama Guru" class="share-input"></td>
            <td><input type="number" name="teachers[${idx}][max_days]" min="1" max="30" placeholder="2 / 3" class="share-input"></td>
            <td><div style="display: flex; flex-wrap: wrap; gap: 4px;">${dayCheckboxes}</div></td>
            <td><input type="text" name="teachers[${idx}][forbidden_subjects_text]" placeholder="Mapel diampu" class="share-input"></td>
            <td style="text-align: center;"><button type="button" class="btn btn-secondary btn-sm" onclick="removeTeacherRuleRow(this)">&times;</button></td>
        `;
        tbody.appendChild(tr);
    }

    function removeTeacherRuleRow(btn) {
        const tbody = document.getElementById('teacherRulesBody');
        if (!tbody) return;
        if (tbody.rows.length <= 1) {
            btn.closest('tr').querySelectorAll('input[type="text"], input[type="number"]').forEach(el => el.value = '');
            btn.closest('tr').querySelectorAll('input[type="checkbox"]').forEach(el => el.checked = false);
            return;
        }
        btn.closest('tr').remove();
    }

    function copyWhatsAppText() {
        const textarea = document.getElementById('waTextOutput');
        const btn = document.getElementById('btnCopyWa');
        if (!textarea) return;

        textarea.select();
        textarea.setSelectionRange(0, 99999);

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textarea.value).then(() => {
                if (btn) {
                    const orig = btn.textContent;
                    btn.textContent = 'Teks WA Tersalin!';
                    setTimeout(() => { btn.textContent = orig; }, 2200);
                }
            });
        } else {
            document.execCommand('copy');
            if (btn) {
                const orig = btn.textContent;
                btn.textContent = 'Teks WA Tersalin!';
                setTimeout(() => { btn.textContent = orig; }, 2200);
            }
        }
    }

    async function downloadSchedulePng() {
        const area = document.getElementById('scheduleCaptureArea');
        const btn = document.getElementById('btnDownloadPng');
        if (!area || typeof html2canvas === 'undefined') {
            alert('Pustaka gambar belum siap. Pastikan koneksi internet aktif atau gunakan tombol Cetak / Buka PDF.');
            return;
        }

        const origLabel = btn ? btn.textContent : '';
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Membuat PNG...';
        }

        try {
            const canvas = await html2canvas(area, {
                scale: 2.5,
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false
            });

            const link = document.createElement('a');
            link.download = 'jadwal-ujian-{{ \Illuminate\Support\Str::slug($sessionInfo["session_name"]) }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (err) {
            alert('Gagal membuat gambar PNG: ' + err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = origLabel;
            }
        }
    }
</script>
@endsection