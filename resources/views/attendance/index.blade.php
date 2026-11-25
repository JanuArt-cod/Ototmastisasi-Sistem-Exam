@extends('layouts.app')

@section('title', 'Cetak Daftar Hadir Peserta - ' . $session->session_name)

@section('content')
    @php
        $summary = $data['summary'];
        $options = $data['options'];
        $scheduleGroups = $data['schedule_groups'];
        $rooms = $data['rooms'];
        $unassignedStudents = $data['unassigned_students'];
    @endphp

    <style>
        .att-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 18px;
        }
        .att-alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 13.5px;
            line-height: 1.5;
            border: 1px solid transparent;
        }
        .att-alert-success {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }
        .att-alert-warning {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .att-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }
        .att-stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 11px 14px;
        }
        .att-stat-label {
            font-size: 12px;
            color: #64748b;
        }
        .att-stat-value {
            font-size: 20px;
            font-weight: 700;
            margin-top: 2px;
            color: #0f172a;
        }
        .att-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
        }
        .att-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }
        .att-input, .att-select {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            background: #ffffff;
            color: #0f172a;
        }

        /* PRATINJAU LEMBAR DAFTAR HADIR (MENYERUPAI HASIL CETAK) */
        .att-preview-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .att-sheet-wrapper {
            overflow-x: auto;
            background: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
        }
        .att-paper-sheet {
            background: #ffffff;
            color: #000000;
            font-family: Arial, Helvetica, sans-serif;
            padding: 20px 24px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.14);
            min-width: 980px;
            max-width: 1240px;
        }
        .att-doc-header {
            text-align: center;
            line-height: 1.3;
            padding-bottom: 8px;
            margin-bottom: 8px;
            border-bottom: 2px solid #000000;
        }
        .att-doc-header h3 {
            margin: 0;
            font-size: 13.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .att-room-heading {
            font-size: 12px;
            font-weight: 700;
            margin: 4px 0 6px 0;
            text-transform: uppercase;
        }
        .att-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5px;
            border: 1.5px solid #000000;
        }
        .att-table th, .att-table td {
            border: 1px solid #1e293b;
            padding: 3px 4px;
            color: #000000;
        }
        .att-table th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: center;
        }
        .att-th-vertical {
            height: 76px;
            padding: 4px 1px !important;
            vertical-align: middle;
            text-align: center;
        }
        .att-vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.1;
            display: inline-block;
            max-height: 70px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .att-paraf-cell {
            height: 21px;
            padding: 0 !important;
        }
        .att-day-end {
            border-right: 1.8px solid #000000 !important;
        }
        .att-sign-grid {
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
            padding: 0 28px;
            font-size: 11.5px;
        }
        .att-sign-box {
            min-width: 220px;
            text-align: left;
        }
        .att-sign-space {
            height: 44px;
        }
        .is-hidden {
            display: none !important;
        }
    </style>

    @if (session('success'))
        <div class="att-alert att-alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- RINGKASAN SESI UJIAN --}}
    <div class="att-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap;">
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 19px; color: #0f172a;">Cetak Daftar Hadir Peserta</h2>
                <p style="margin: 0; font-size: 13.5px; color: #475569;">
                    <strong>{{ $data['session']['exam_title'] }}</strong> &bull;
                    {{ $data['session']['school_name'] }}
                    @if ($data['session']['academic_year'])
                        &bull; Tahun Pelajaran {{ $data['session']['academic_year'] }}
                    @endif
                </p>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($allSessions->count() > 1)
                    <form method="GET" action="" onsubmit="return false;" style="display: inline-flex; align-items: center; gap: 6px;">
                        <label for="session_switcher" style="font-size: 12px; font-weight: 600;">Sesi Ujian:</label>
                        <select
                            id="session_switcher"
                            class="att-select"
                            style="width: auto; min-width: 210px;"
                            onchange="window.location.href = this.value"
                        >
                            @foreach ($allSessions as $sItem)
                                <option
                                    value="{{ route('exam-sessions.attendance.index', $sItem) }}"
                                    {{ $sItem->id === $session->id ? 'selected' : '' }}
                                >
                                    {{ $sItem->session_name }} {{ $sItem->academic_year ? '(' . $sItem->academic_year . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary btn-sm">
                    &larr; Ringkasan Sesi
                </a>
            </div>
        </div>

        <div class="att-stat-grid">
            <div class="att-stat-box">
                <div class="att-stat-label">Total Peserta Terdaftar</div>
                <div class="att-stat-value">{{ $summary['total_students'] }}</div>
            </div>
            <div class="att-stat-box">
                <div class="att-stat-label">Total Ruang Ujian</div>
                <div class="att-stat-value">{{ $summary['total_rooms'] }}</div>
            </div>
            <div class="att-stat-box">
                <div class="att-stat-label">Total Hari Ujian</div>
                <div class="att-stat-value">{{ $summary['total_schedule_days'] }}</div>
            </div>
            <div class="att-stat-box">
                <div class="att-stat-label">Total Mata Pelajaran</div>
                <div class="att-stat-value">{{ $summary['total_subjects'] }}</div>
            </div>
            <div class="att-stat-box">
                <div class="att-stat-label">Estimasi Halaman Cetak</div>
                <div class="att-stat-value">{{ $summary['total_pages'] }} Hal</div>
            </div>
        </div>
    </div>

    {{-- PERINGATAN JIKA ADA SISWA BELUM MEMILIKI RUANG --}}
    @if ($summary['unassigned_students'] > 0)
        <div class="att-alert att-alert-warning">
            <strong>Perhatian — {{ $summary['unassigned_students'] }} Peserta Belum Memiliki Ruang Ujian:</strong>
            Peserta berikut tidak dimasukkan ke daftar hadir ruang mana pun agar tidak terjadi salah penempatan.
            Silakan atur pembagian ruangnya terlebih dahulu melalui menu
            <a href="{{ route('exam-sessions.rooms.index', $session) }}" style="font-weight: 700; text-decoration: underline;">Pengaturan Ruang Ujian</a>:
            <ul style="margin: 6px 0 0 18px; padding: 0;">
                @foreach (array_slice($unassignedStudents, 0, 10) as $uStudent)
                    <li>
                        {{ $uStudent['student_name'] }}
                        ({{ $uStudent['class_name'] ?: 'Tanpa Kelas' }}
                        {{ $uStudent['participant_number'] ? '— ' . $uStudent['participant_number'] : '' }})
                    </li>
                @endforeach
                @if (count($unassignedStudents) > 10)
                    <li><em>...dan {{ count($unassignedStudents) - 10 }} peserta lainnya.</em></li>
                @endif
            </ul>
        </div>
    @endif

    {{-- PERINGATAN JIKA JADWAL MATA PELAJARAN MASIH KOSONG --}}
    @if ($summary['total_subjects'] === 0)
        <div class="att-alert att-alert-warning">
            <strong>Jadwal Mata Pelajaran Belum Tersedia:</strong>
            Sesi ujian ini belum memiliki jadwal hari dan mata pelajaran. Kolom paraf jadwal tidak akan tampil sebelum jadwal diisi melalui menu
            <a href="{{ route('exam-sessions.schedules.index', $session) }}" style="font-weight: 700; text-decoration: underline;">Jadwal Ujian</a>.
        </div>
    @endif

    {{-- PENGATURAN CETAK & PENANGGUNG JAWAB --}}
    <div class="att-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <div>
                <strong style="font-size: 15px; color: #0f172a;">Pengaturan Layout &amp; Penanggung Jawab Dokumen</strong>
                <div style="font-size: 12.5px; color: #64748b;">
                    Standar mengikuti referensi: F4 Landscape (14 &times; 8,5 inci), 34 baris per ruang (18 baris hal. 1 + 16 baris hal. lanjutan), dan kolom paraf kosong manual.
                </div>
            </div>

            <a
                href="{{ route('exam-sessions.attendance.index', ['examSession' => $session->id, 'reset_defaults' => 1]) }}"
                class="btn btn-secondary btn-sm"
            >
                Reset ke Standar Referensi
            </a>
        </div>

        <form id="attendance-settings-form" method="GET" action="{{ route('exam-sessions.attendance.index', $session) }}">
            <input type="hidden" name="form_submitted" value="1">
            <div class="att-form-grid">
                <div>
                    <label for="room_filter" class="att-label">Cakupan Ruang Ujian</label>
                    <select id="room_filter" name="room_filter" class="att-select">
                        <option value="all" {{ $options['room_filter'] === 'all' ? 'selected' : '' }}>
                            Semua Ruang ({{ $summary['total_rooms'] }} Ruang)
                        </option>
                        @foreach ($data['available_rooms'] as $availRoom)
                            <option
                                value="{{ $availRoom['room_name'] }}"
                                {{ strcasecmp($options['room_filter'], $availRoom['room_name']) === 0 ? 'selected' : '' }}
                            >
                                {{ $availRoom['room_name'] }} ({{ $availRoom['student_count'] }} Peserta)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sort_by" class="att-label">Urutan Peserta dalam Ruang</label>
                    <select id="sort_by" name="sort_by" class="att-select">
                        <option value="participant_number" {{ $options['sort_by'] === 'participant_number' ? 'selected' : '' }}>
                            Nomor Peserta Asli (Ascending — Standar)
                        </option>
                        @if ($data['has_finalized_seating'])
                            <option value="seating_seat" {{ $options['sort_by'] === 'seating_seat' ? 'selected' : '' }}>
                                Urutan Kursi Penempatan Final
                            </option>
                        @endif
                    </select>
                </div>

                <div>
                    <label for="paper_size" class="att-label">Ukuran Kertas (Landscape)</label>
                    <select id="paper_size" name="paper_size" class="att-select">
                        @foreach ($paperPresets as $presetKey => $presetInfo)
                            <option value="{{ $presetKey }}" {{ $options['paper_size'] === $presetKey ? 'selected' : '' }}>
                                {{ $presetInfo['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="target_room_capacity" class="att-label">Kapasitas Slot per Ruang</label>
                    <input
                        type="number"
                        id="target_room_capacity"
                        name="target_room_capacity"
                        min="0"
                        max="200"
                        value="{{ $options['target_room_capacity'] }}"
                        class="att-input"
                    >
                </div>

                <div>
                    <label for="first_page_rows" class="att-label">Baris Halaman Pertama</label>
                    <input
                        type="number"
                        id="first_page_rows"
                        name="first_page_rows"
                        min="5"
                        max="60"
                        value="{{ $options['first_page_rows'] }}"
                        class="att-input"
                    >
                </div>

                <div>
                    <label for="continuation_page_rows" class="att-label">Baris Halaman Lanjutan</label>
                    <input
                        type="number"
                        id="continuation_page_rows"
                        name="continuation_page_rows"
                        min="5"
                        max="60"
                        value="{{ $options['continuation_page_rows'] }}"
                        class="att-input"
                    >
                </div>
            </div>

            <div class="att-form-grid" style="margin-top: 12px;">
                <div>
                    <label for="chair_name" class="att-label">Nama Ketua Panitia</label>
                    <input
                        type="text"
                        id="chair_name"
                        name="chair_name"
                        value="{{ $options['chair_name'] }}"
                        placeholder="Contoh: Drs. H. Ahmad Fauzi, M.Pd."
                        class="att-input"
                    >
                </div>

                <div>
                    <label for="secretary_name" class="att-label">Nama Sekretaris</label>
                    <input
                        type="text"
                        id="secretary_name"
                        name="secretary_name"
                        value="{{ $options['secretary_name'] }}"
                        placeholder="Contoh: Siti Aminah, S.Pd."
                        class="att-input"
                    >
                </div>

                <div>
                    <label for="issue_city" class="att-label">Kota Tanda Tangan</label>
                    <input
                        type="text"
                        id="issue_city"
                        name="issue_city"
                        value="{{ $options['issue_city'] }}"
                        placeholder="Contoh: Bogor"
                        class="att-input"
                    >
                </div>

                <div>
                    <label for="issue_year" class="att-label">Tahun Tanda Tangan</label>
                    <input
                        type="text"
                        id="issue_year"
                        name="issue_year"
                        value="{{ $options['issue_year'] }}"
                        placeholder="Contoh: 2026"
                        class="att-input"
                    >
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; flex-wrap: wrap; gap: 10px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #1e293b; cursor: pointer;">
                    <input
                        type="checkbox"
                        name="fill_empty_rows"
                        value="1"
                        {{ $options['fill_empty_rows'] ? 'checked' : '' }}
                    >
                    Tampilkan baris kosong bernomor urut hingga kapasitas slot ruang ({{ $options['target_room_capacity'] }} baris)
                </label>

                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-secondary">
                        Pratinjau Daftar Hadir
                    </button>

                    @if ($summary['total_rooms'] > 0)
                        <button
                            type="button"
                            class="btn btn-secondary"
                            onclick="openAttendancePdf({ forceAllRooms: false, download: false })"
                        >
                            Cetak Ruang Terpilih
                        </button>

                        <button
                            type="button"
                            class="btn btn-secondary"
                            onclick="openAttendancePdf({ forceAllRooms: true, download: false })"
                        >
                            Cetak Semua Ruang
                        </button>

                        <button
                            type="button"
                            class="btn btn-primary"
                            onclick="openAttendancePdf({ forceAllRooms: false, download: true })"
                        >
                            Unduh PDF
                        </button>
                    @endif
                </div>
            </div>
        </form>

        <script>
            function openAttendancePdf(opts) {
                const form = document.getElementById('attendance-settings-form');
                if (!form) {
                    return;
                }
                const params = new URLSearchParams();
                const formData = new FormData(form);
                formData.forEach(function (value, key) {
                    params.set(key, String(value));
                });
                if (!formData.has('fill_empty_rows')) {
                    params.set('fill_empty_rows', '0');
                }
                if (opts && opts.forceAllRooms) {
                    params.set('room_filter', 'all');
                }
                if (opts && opts.download) {
                    params.set('download', '1');
                }
                const baseUrl = @json(route('exam-sessions.attendance.pdf', $session));
                window.open(baseUrl + '?' + params.toString(), '_blank');
            }
        </script>
    </div>

    {{-- PRATINJAU DOKUMEN DAFTAR HADIR --}}
    <div class="att-card">
        <div style="margin-bottom: 10px;">
            <strong style="font-size: 15px; color: #0f172a;">Pratinjau Lembar Daftar Hadir Peserta</strong>
            <div style="font-size: 12.5px; color: #64748b;">
                Seluruh kotak pada kolom mata pelajaran dicetak kosong sebagai tempat paraf manual siswa saat ujian berlangsung.
            </div>
        </div>

        @if (empty($rooms))
            <div class="att-alert att-alert-warning" style="margin-bottom: 0;">
                Belum ada peserta yang memiliki penempatan ruang ujian pada sesi ini. Silakan atur pembagian ruang di menu
                <a href="{{ route('exam-sessions.rooms.index', $session) }}" style="font-weight: 700; text-decoration: underline;">Pengaturan Ruang Ujian</a>.
            </div>
        @else
            @php
                $flatPages = [];
                foreach ($rooms as $rItem) {
                    foreach ($rItem['pages'] as $pItem) {
                        $flatPages[] = [
                            'room_name' => $rItem['room_name'],
                            'student_count' => $rItem['student_count'],
                            'page' => $pItem,
                        ];
                    }
                }
            @endphp

            <div class="att-preview-toolbar">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <label for="preview-page-selector" style="font-size: 12.5px; font-weight: 700;">Pilih Lembar Pratinjau:</label>
                    <select
                        id="preview-page-selector"
                        class="att-select"
                        style="width: auto; min-width: 290px;"
                        onchange="showPreviewPage(Number(this.value))"
                    >
                        @foreach ($flatPages as $fpIdx => $fp)
                            <option value="{{ $fpIdx }}">
                                {{ $fp['room_name'] }} &mdash; Hal. {{ $fp['page']['page_number'] }}/{{ $fp['page']['total_room_pages'] }}
                                (Baris {{ $fp['page']['start_row'] }}&ndash;{{ $fp['page']['end_row'] }}{{ $fp['page']['is_last_page'] ? ' + Tanda Tangan' : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="stepPreviewPage(-1)">
                        &larr; Halaman Sebelumnya
                    </button>
                    <span id="preview-page-indicator" style="font-size: 12.5px; font-weight: 700; color: #0f172a;">
                        Lembar 1 dari {{ count($flatPages) }}
                    </span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="stepPreviewPage(1)">
                        Halaman Berikutnya &rarr;
                    </button>
                </div>
            </div>

            @php
                $totalSubjPreview = max(1, (int) ($summary['total_subjects'] ?? 1));
                $hasSchedulesPreview = ((int) ($summary['total_subjects'] ?? 0)) > 0;
                $noPctPreview = $hasSchedulesPreview ? 2.6 : 5.0;
                $partNumPctPreview = $hasSchedulesPreview ? 11.4 : 25.0;
                $namePctPreview = $hasSchedulesPreview ? 23.5 : 70.0;
                $subjPctPreview = $hasSchedulesPreview ? round(62.5 / $totalSubjPreview, 4) : 0;
            @endphp

            <div class="att-sheet-wrapper">
                @foreach ($flatPages as $fpIdx => $fp)
                    @php
                        $pageData = $fp['page'];
                    @endphp
                    <div class="att-paper-sheet {{ $fpIdx === 0 ? '' : 'is-hidden' }}" data-att-preview-page="{{ $fpIdx }}">
                        <div class="att-doc-header">
                            <h3>DAFTAR HADIR PESERTA</h3>
                            <h3>{{ strtoupper($data['session']['exam_title']) }}</h3>
                            <h3>{{ strtoupper($data['session']['school_name']) }}</h3>
                            @if ($data['session']['academic_year'])
                                <h3>TAHUN PELAJARAN {{ strtoupper($data['session']['academic_year']) }}</h3>
                            @endif
                        </div>

                        <div class="att-room-heading">
                            {{ strtoupper($fp['room_name']) }}
                        </div>

                        <table class="att-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="width: {{ $noPctPreview }}%;">No</th>
                                    <th rowspan="2" style="width: {{ $partNumPctPreview }}%; white-space: nowrap;">No Peserta</th>
                                    <th rowspan="2" class="{{ $hasSchedulesPreview ? 'att-day-end' : '' }}" style="width: {{ $namePctPreview }}%;">Nama Peserta</th>
                                    @foreach ($scheduleGroups as $gIdx => $group)
                                        @php
                                            $isLastDayGroup = ($gIdx === count($scheduleGroups) - 1);
                                            $dayPctPreview = round($subjPctPreview * $group['colspan'], 4);
                                        @endphp
                                        <th colspan="{{ $group['colspan'] }}" class="{{ $isLastDayGroup ? '' : 'att-day-end' }}" style="width: {{ $dayPctPreview }}%; font-size: 9px; line-height: 1.2; padding: 4px 2px;">
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
                                            <th class="att-th-vertical {{ $isLastSubjInDay ? 'att-day-end' : '' }}" style="width: {{ $subjPctPreview }}%;" title="{{ $subj['subject_name'] }}">
                                                <span class="att-vertical-text">{{ $subj['subject_name'] }}</span>
                                            </th>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pageData['rows'] as $row)
                                    <tr>
                                        <td style="width: {{ $noPctPreview }}%; text-align: center;">{{ $row['row_number'] }}</td>
                                        <td style="width: {{ $partNumPctPreview }}%; text-align: center; font-size: 10px; white-space: nowrap;">{{ $row['participant_number'] }}</td>
                                        <td class="{{ $hasSchedulesPreview ? 'att-day-end' : '' }}" style="width: {{ $namePctPreview }}%; padding-left: 7px; white-space: nowrap; overflow: hidden;">{{ $row['student_name'] }}</td>
                                        @foreach ($scheduleGroups as $gIdx => $group)
                                            @php
                                                $isLastDayGroup = ($gIdx === count($scheduleGroups) - 1);
                                                $subjCount = count($group['subjects']);
                                            @endphp
                                            @foreach ($group['subjects'] as $sIdx => $subj)
                                                @php
                                                    $isLastSubjInDay = ($sIdx === $subjCount - 1) && ! $isLastDayGroup;
                                                @endphp
                                                <td class="att-paraf-cell {{ $isLastSubjInDay ? 'att-day-end' : '' }}" style="width: {{ $subjPctPreview }}%;">&nbsp;</td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- AREA TANDA TANGAN HANYA PADA HALAMAN TERAKHIR MASING-MASING RUANG --}}
                        @if ($pageData['is_last_page'])
                            <div class="att-sign-grid">
                                <div class="att-sign-box">
                                    <div>&nbsp;</div>
                                    <div>{{ $options['chair_title'] }}</div>
                                    <div class="att-sign-space"></div>
                                    <div style="font-weight: 700; text-decoration: underline;">
                                        {{ $options['chair_name'] !== '' ? $options['chair_name'] : '........................................' }}
                                    </div>
                                </div>

                                <div class="att-sign-box">
                                    <div>
                                        {{ ($options['issue_city'] !== '' ? $options['issue_city'] : '................') . ', ' . ($options['issue_date_text'] !== '' ? $options['issue_date_text'] . ' ' : '................ ') . ($options['issue_year'] !== '' ? $options['issue_year'] : date('Y')) }}
                                    </div>
                                    <div>{{ $options['secretary_title'] }}</div>
                                    <div class="att-sign-space"></div>
                                    <div style="font-weight: 700; text-decoration: underline;">
                                        {{ $options['secretary_name'] !== '' ? $options['secretary_name'] : '........................................' }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <script>
                let currentPreviewIndex = 0;
                const totalPreviewPages = {{ count($flatPages) }};

                function showPreviewPage(index) {
                    if (totalPreviewPages <= 0) {
                        return;
                    }
                    if (index < 0) {
                        index = 0;
                    }
                    if (index >= totalPreviewPages) {
                        index = totalPreviewPages - 1;
                    }
                    currentPreviewIndex = index;

                    document.querySelectorAll('[data-att-preview-page]').forEach(function (el) {
                        if (Number(el.getAttribute('data-att-preview-page')) === currentPreviewIndex) {
                            el.classList.remove('is-hidden');
                        } else {
                            el.classList.add('is-hidden');
                        }
                    });

                    const selector = document.getElementById('preview-page-selector');
                    if (selector) {
                        selector.value = String(currentPreviewIndex);
                    }

                    const indicator = document.getElementById('preview-page-indicator');
                    if (indicator) {
                        indicator.textContent = 'Lembar ' + (currentPreviewIndex + 1) + ' dari ' + totalPreviewPages;
                    }
                }

                function stepPreviewPage(delta) {
                    showPreviewPage(currentPreviewIndex + delta);
                }
            </script>
        @endif
    </div>
@endsection