@extends('layouts.app')

@section('title', 'Nomor Meja Peserta - ' . $session->session_name)

@section('content')
    <style>
        .seating-alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 13.5px;
            line-height: 1.5;
            border: 1px solid transparent;
        }
        .seating-alert-success {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }
        .seating-alert-danger {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }
        .seating-alert-warning {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .seating-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }
        .seating-stat-box {
            background: var(--bg-muted, #f8fafc);
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
        }
        .seating-stat-label {
            font-size: 12px;
            color: var(--text-secondary, #64748b);
        }
        .seating-stat-value {
            font-size: 20px;
            font-weight: 700;
            margin-top: 2px;
            color: #0f172a;
        }
        .seating-class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .seating-class-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            cursor: pointer;
            font-size: 13px;
        }
        .seating-class-item:hover {
            background: #f8fafc;
        }
        .seating-pill {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 999px;
            background: #e2e8f0;
            color: #1e293b;
        }
        .seating-pill-room {
            background: #dbeafe;
            color: #1e40af;
        }
        .seating-pill-final {
            background: #d1fae5;
            color: #065f46;
        }
        .seating-pill-draft {
            background: #fef3c7;
            color: #92400e;
        }
        .seating-pagination-bar {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .seating-pagination-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }
        .seating-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .seating-modal-backdrop.is-open {
            display: flex;
        }
        .seating-modal-dialog {
            background: #ffffff;
            border-radius: 8px;
            max-width: 480px;
            width: 100%;
            padding: 20px 22px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            border: 1px solid #cbd5e1;
        }
    </style>

    @if (session('success'))
        <div class="seating-alert seating-alert-success">
            <strong>Berhasil:</strong> {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="seating-alert seating-alert-danger">
            <strong>Gagal:</strong> {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="seating-alert seating-alert-danger">
            <strong>Periksa input berikut:</strong>
            <ul style="margin: 6px 0 0 18px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- HEADER MODUL & NAVIGASI SESI --}}
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Modul Tambahan &bull; Nomor Meja &amp; Denah Duduk</span>
                <h1 class="card-title" style="font-size: 20px;">Nomor Meja Peserta</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Sesi Ujian: <strong>{{ $session->session_name }}</strong>
                    &bull; Sekolah: <strong>{{ $session->school_name ?: '-' }}</strong>
                    &bull; Ujian: <strong>{{ $session->exam_title ?: '-' }}</strong>
                </p>
            </div>
            <div class="actions-group">
                <a href="{{ route('exam-sessions.rooms.index', $session) }}" class="btn btn-secondary">
                    Pengaturan Ruang Ujian
                </a>
                <a href="{{ route('exam-sessions.students.index', $session) }}" class="btn btn-secondary">
                    Kelola Data Siswa
                </a>
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">
                    Kembali ke Sesi Ujian
                </a>
            </div>
        </div>

        {{-- RINGKASAN KESIAPAN DATA PESERTA & RUANGAN --}}
        <div class="seating-stat-grid">
            <div class="seating-stat-box">
                <div class="seating-stat-label">Total Siswa di Sesi</div>
                <div class="seating-stat-value">{{ number_format($sessionInspection['total_queried'], 0, ',', '.') }}</div>
            </div>
            <div class="seating-stat-box">
                <div class="seating-stat-label">Siap Diacak (Bernomor Peserta)</div>
                <div class="seating-stat-value">{{ number_format($sessionInspection['valid_count'], 0, ',', '.') }}</div>
            </div>
            <div class="seating-stat-box">
                <div class="seating-stat-label">Ruang Aktif (Dari Pengaturan Ruang)</div>
                <div class="seating-stat-value" style="color: #1e40af;">
                    {{ count($sessionInspection['distinct_rooms']) }} Ruang
                </div>
            </div>
            <div class="seating-stat-box">
                <div class="seating-stat-label">Peserta Belum Punya Ruang</div>
                <div class="seating-stat-value" style="color: {{ count($sessionInspection['missing_rooms']) > 0 ? '#991b1b' : '#065f46' }};">
                    {{ number_format(count($sessionInspection['missing_rooms']), 0, ',', '.') }}
                </div>
            </div>
        </div>

        @if (! empty($sessionInspection['distinct_rooms']))
            <div style="margin-top: 12px; font-size: 13px; color: #334155;">
                <strong>Komposisi Ruangan Ujian Aktif:</strong>
                @foreach ($sessionInspection['distinct_rooms'] as $rLabel => $rCount)
                    <span class="seating-pill seating-pill-room" style="margin-left: 4px; margin-bottom: 4px;">
                        {{ $rLabel }}: {{ number_format($rCount, 0, ',', '.') }} siswa
                    </span>
                @endforeach
            </div>
        @endif

        @if (! empty($sessionInspection['grade_summary']))
            <div style="margin-top: 8px; font-size: 13px; color: #334155;">
                <strong>Komposisi Tingkat Kelas Terdeteksi:</strong>
                @foreach ($sessionInspection['grade_summary'] as $gradeLabel => $count)
                    <span class="seating-pill" style="margin-left: 4px;">{{ $gradeLabel }}: {{ number_format($count, 0, ',', '.') }} siswa</span>
                @endforeach
            </div>
        @endif

        @if (! empty($sessionInspection['missing_rooms']))
            <div class="seating-alert seating-alert-danger" style="margin-top: 14px; margin-bottom: 0;">
                <strong>Perhatian: {{ count($sessionInspection['missing_rooms']) }} Peserta Belum Memiliki Ruangan Ujian!</strong><br>
                Sistem Nomor Meja menggunakan pembagian ruangan dari menu <strong>Pengaturan Ruang Ujian</strong>.
                Silakan atur pembagian ruangan terlebih dahulu sebelum mengacak nomor meja.
                <div style="margin-top: 8px;">
                    <a href="{{ route('exam-sessions.rooms.index', $session) }}" class="btn btn-primary btn-sm">
                        Buka Pengaturan Ruang Ujian
                    </a>
                </div>
            </div>
        @endif

        @if (! empty($sessionInspection['duplicate_participant_numbers']))
            <div class="seating-alert seating-alert-danger" style="margin-top: 14px; margin-bottom: 0;">
                <strong>Peringatan Nomor Peserta Duplikat:</strong>
                Ditemukan nomor peserta ganda: <strong>{{ implode(', ', $sessionInspection['duplicate_participant_numbers']) }}</strong>.
                Harap perbaiki duplikasi melalui menu <em>Kelola Data Siswa</em> sebelum membuat nomor meja.
            </div>
        @endif

        @if (! empty($sessionInspection['missing_participant_numbers']))
            <div class="seating-alert seating-alert-warning" style="margin-top: 14px; margin-bottom: 0;">
                <strong>Informasi Peserta Tanpa Nomor Peserta ({{ count($sessionInspection['missing_participant_numbers']) }} siswa):</strong>
                Siswa tanpa nomor peserta otomatis dilewati saat pengacakan.
            </div>
        @endif
    </section>

    {{-- PENGATURAN PENEMPATAN & PENGACAKAN LINTAS TINGKAT KELAS PER RUANGAN --}}
    <section class="card">
        <h2 class="card-title">Pengaturan Penempatan &amp; Pengacakan Lintas Tingkat Kelas</h2>
        <p class="card-desc">
            Pembagian ruangan mengikuti data <strong>Pengaturan Ruang Ujian</strong> (misalnya Ruang 01 kapasitas 30 siswa = nomor urut <code>1</code> s.d. <code>30</code> pada Meja <code>1</code>–<code>15</code>, Ruang 02 kembali dimulai dari nomor urut <code>1</code> s.d. <code>30</code>).
            Format nomor meja peserta: <code>NOMOR_PESERTA.NOMOR_URUT_KURSI</code>.
        </p>

        <form id="seating-generate-form" method="POST" action="{{ route('exam-sessions.seating.generate', $session) }}">
            @csrf

            @php
                $defaultMode = old('selection_mode', $activePlan?->selection_mode ?? 'all');
            @endphp

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <div>
                    <label for="selection_mode" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        1. Pilihan Peserta Ujian
                    </label>
                    <select name="selection_mode" id="selection_mode" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px;">
                        <option value="all" {{ $defaultMode === 'all' ? 'selected' : '' }}>
                            Seluruh Peserta dalam Sesi Ini ({{ number_format($sessionInspection['valid_count'], 0, ',', '.') }} siswa valid)
                        </option>
                        <option value="classes" {{ $defaultMode === 'classes' ? 'selected' : '' }}>
                            Pilih Kelas Tertentu (Filter Rombel)
                        </option>
                    </select>
                </div>

                <div>
                    <label for="starting_seat_number" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        2. Nomor Urut Awal per Ruangan
                    </label>
                    <input
                        type="number"
                        id="starting_seat_number"
                        name="starting_seat_number"
                        min="1"
                        max="99999"
                        value="{{ old('starting_seat_number', $activePlan?->starting_seat_number ?? 1) }}"
                        style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px;"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        3. Aturan Meja &amp; Ruangan
                    </label>
                    <div style="padding: 8px 10px; background: var(--bg-muted, #f8fafc); border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #334155;">
                        <strong>2 Peserta / Meja</strong> &bull; Reset Urut per Ruangan Eksisting
                    </div>
                </div>
            </div>

            {{-- PANEL PILIHAN KELAS (TAMPIL SAAT MODE FILTER KELAS DIPILIH) --}}
            @php
                $preselectedClasses = old('selected_classes', $activePlan?->selected_classes_json ?? []);
                if (! is_array($preselectedClasses)) {
                    $preselectedClasses = [];
                }
            @endphp
            <div id="class-selector-panel" style="display: {{ $defaultMode === 'classes' ? 'block' : 'none' }}; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <strong style="font-size: 13.5px;">Pilih Kelas yang Diikutsertakan:</strong>
                    <div class="actions-group">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllClasses(true)">Pilih Semua Kelas</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAllClasses(false)">Kosongkan Pilihan</button>
                    </div>
                </div>

                <div class="seating-class-grid">
                    @foreach ($classBreakdown as $cls)
                        @if ($cls['raw_class_name'] !== '')
                            <label class="seating-class-item">
                                <span>
                                    <input
                                        type="checkbox"
                                        class="class-checkbox"
                                        name="selected_classes[]"
                                        value="{{ $cls['raw_class_name'] }}"
                                        data-grade="{{ $cls['grade_level'] ?? 'unknown' }}"
                                        data-count="{{ $cls['total'] }}"
                                        {{ in_array($cls['raw_class_name'], $preselectedClasses, true) ? 'checked' : '' }}
                                        onchange="updateSelectedClassSummary()"
                                    >
                                    <strong>{{ $cls['class_name'] }}</strong>
                                </span>
                                <span class="seating-pill">
                                    {{ $cls['grade_level'] ? 'Tingkat ' . $cls['grade_level'] : '?' }} &bull; {{ $cls['total'] }}
                                </span>
                            </label>
                        @endif
                    @endforeach
                </div>

                <div id="selected-class-feedback" style="margin-top: 10px; font-size: 13px; color: #334155;"></div>
            </div>

            <div class="actions-group">
                @if (! $activePlan)
                    <button type="submit" class="btn btn-primary" {{ count($sessionInspection['missing_rooms']) > 0 ? 'disabled' : '' }}>
                        ACAK &amp; BUAT NOMOR MEJA
                    </button>
                @else
                    <button type="button" class="btn btn-primary" onclick="openRerandomizeModal()" {{ count($sessionInspection['missing_rooms']) > 0 ? 'disabled' : '' }}>
                        ACAK ULANG PESERTA
                    </button>
                @endif
            </div>
        </form>
    </section>

    {{-- HASIL PENEMPATAN, RIWAYAT VERSI, FINALISASI & TABEL NOMOR MEJA --}}
    @if ($activePlan)
        <section class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px;">
                        <span class="badge badge-info">Versi #{{ $activePlan->version_number }}</span>
                        @if ($activePlan->status === 'finalized')
                            <span class="seating-pill seating-pill-final">TERFINALISASI (ACUAN CETAK)</span>
                        @elseif ($activePlan->status === 'draft')
                            <span class="seating-pill seating-pill-draft">DRAFT (BELUM DIFINALISASI)</span>
                        @else
                            <span class="seating-pill">ARSIP VERSI LAMA</span>
                        @endif
                        <span style="font-size: 12.5px; color: var(--text-secondary, #64748b);">
                            Dibuat: {{ $activePlan->generated_at?->format('d/m/Y H:i') ?? '-' }}
                        </span>
                    </div>
                    <h2 class="card-title" style="margin-bottom: 4px;">Hasil Penempatan Nomor Meja Peserta per Ruangan</h2>
                    <p class="card-desc" style="margin-bottom: 0;">
                        Setiap ruangan memiliki nomor urut kursi (<code>1..N</code>) dan nomor meja (<code>Meja 1..M</code>) masing-masing.
                    </p>
                </div>

                <div class="actions-group" style="align-items: center;">
                    <a
                        href="{{ route('exam-sessions.seating.floor-plan', ['examSession' => $session->id, 'seatingPlan' => $activePlan->id]) }}"
                        class="btn btn-secondary"
                        target="_blank"
                    >
                        Denah Duduk Digital &amp; QR
                    </a>

                    <a
                        href="{{ route('exam-sessions.seating.floor-plan.export-html', ['examSession' => $session->id, 'seatingPlan' => $activePlan->id]) }}"
                        class="btn btn-secondary"
                    >
                        Unduh HTML Denah Online
                    </a>

                    @if ($allPlans->count() > 1)
                        <form method="GET" action="{{ route('exam-sessions.seating.index', $session) }}" style="display: inline-flex; align-items: center; gap: 6px;">
                            <label for="plan_id_switcher" style="font-size: 12.5px; font-weight: 600;">Riwayat Versi:</label>
                            <select
                                id="plan_id_switcher"
                                name="plan_id"
                                onchange="this.form.submit()"
                                style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"
                            >
                                @foreach ($allPlans as $planItem)
                                    <option value="{{ $planItem->id }}" {{ $planItem->id === $activePlan->id ? 'selected' : '' }}>
                                        Versi #{{ $planItem->version_number }} ({{ strtoupper($planItem->status) }} - {{ $planItem->total_rooms }} ruang)
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    @if ($activePlan->status !== 'finalized')
                        <form method="POST" action="{{ route('exam-sessions.seating.finalize', [$session, $activePlan]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                Finalisasi Hasil Penempatan
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- STATISTIK HASIL PENEMPATAN --}}
            <div class="seating-stat-grid">
                <div class="seating-stat-box">
                    <div class="seating-stat-label">Total Ruangan</div>
                    <div class="seating-stat-value" style="color: #1e40af;">{{ number_format($activePlan->total_rooms, 0, ',', '.') }} Ruang</div>
                </div>
                <div class="seating-stat-box">
                    <div class="seating-stat-label">Total Peserta</div>
                    <div class="seating-stat-value">{{ number_format($activePlan->total_students, 0, ',', '.') }}</div>
                </div>
                <div class="seating-stat-box">
                    <div class="seating-stat-label">Total Meja</div>
                    <div class="seating-stat-value">{{ number_format($activePlan->total_desks, 0, ',', '.') }}</div>
                </div>
                <div class="seating-stat-box">
                    <div class="seating-stat-label">Pasangan Lintas Tingkat</div>
                    <div class="seating-stat-value" style="color: #065f46;">{{ number_format($activePlan->cross_grade_pairs, 0, ',', '.') }} meja</div>
                </div>
                <div class="seating-stat-box">
                    <div class="seating-stat-label">Pasangan Sesama Tingkat</div>
                    <div class="seating-stat-value">{{ number_format($activePlan->same_grade_pairs, 0, ',', '.') }} meja</div>
                </div>
            </div>

            @if (! empty($activePlan->warnings_json))
                <div class="seating-alert seating-alert-warning" style="margin-top: 14px; margin-bottom: 0;">
                    <strong>Catatan Penempatan:</strong>
                    <ul style="margin: 4px 0 0 18px;">
                        @foreach ($activePlan->warnings_json as $warn)
                            <li>{{ $warn }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- PANEL CETAK PDF PAKET RUANGAN (DAFTAR PESERTA + KARTU MEJA KECIL QR + DENAH) --}}
            <div style="margin-top: 16px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                    <div>
                        <strong style="font-size: 14px; color: #0f172a;">Cetak PDF Ruangan (Daftar Peserta, Kartu Meja Kecil + QR, &amp; Denah)</strong>
                        <div style="font-size: 12.5px; color: #475569;">
                            Ukuran default <strong>88 &times; 35 mm</strong> memuat <strong>14 kartu meja per lembar A4</strong> (2 kolom &times; 7 baris).
                        </div>
                    </div>

                    @if (! empty($session->qr_floor_plan_url))
                        <span style="display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; background: #d1fae5; color: #065f46;">
                            Link QR Tersimpan Otomatis
                        </span>
                    @endif
                </div>

                <form method="GET" action="{{ route('exam-sessions.seating.pdf', [$session, $activePlan]) }}" target="_blank">
                    <div style="display: grid; grid-template-columns: 1.4fr 1.1fr 0.7fr 0.7fr; gap: 10px; align-items: end;">
                        <div>
                            <label for="document_type" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                                Isi Dokumen PDF
                            </label>
                            <select id="document_type" name="document_type" style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="all">Paket Lengkap (Daftar Peserta + Denah + Kartu Meja QR)</option>
                                <option value="desk_cards">Kartu Meja Kecil (dengan QR Denah) Saja</option>
                                <option value="roster">Daftar Peserta per Ruangan Saja</option>
                                <option value="floor_plan">Denah Duduk Ruangan Saja</option>
                            </select>
                        </div>

                        <div>
                            <label for="pdf_room_number" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                                Pilih Ruangan
                            </label>
                            <select id="pdf_room_number" name="room_number" style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;">
                                <option value="0">Semua Ruangan ({{ $activePlan->total_rooms }} Ruang)</option>
                                @foreach ($activePlan->assignments()->select('room_number', 'room_name')->distinct()->orderBy('room_number')->get() as $rOpt)
                                    <option value="{{ $rOpt->room_number }}">{{ $rOpt->room_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="card_width_mm" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                                Lebar Kartu (mm)
                            </label>
                            <input
                                type="number"
                                id="card_width_mm"
                                name="card_width_mm"
                                min="60"
                                max="95"
                                value="88"
                                style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"
                            >
                        </div>

                        <div>
                            <label for="card_height_mm" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                                Tinggi Kartu (mm)
                            </label>
                            <input
                                type="number"
                                id="card_height_mm"
                                name="card_height_mm"
                                min="28"
                                max="55"
                                value="35"
                                style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"
                            >
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 8px; align-items: end; margin-top: 10px;">
                        <div>
                            <label for="qr_custom_url" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                                Link Tujuan QR Code (Otomatis Tersimpan per Sesi Ujian)
                            </label>
                            <input
                                type="text"
                                id="qr_custom_url"
                                name="qr_custom_url"
                                value="{{ old('qr_custom_url', $session->qr_floor_plan_url ?? '') }}"
                                placeholder="Kosongkan untuk link lokal, atau isi URL online (misal: https://denah.sekolah.sch.id/?room={room})"
                                oninput="document.getElementById('qr_custom_url_save_hidden').value = this.value"
                                style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff;"
                            >
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 3px;">
                                Tersimpan otomatis saat menekan <strong>Simpan Link QR</strong> maupun <strong>Buka / Cetak PDF</strong>. Gunakan kode <code>{room}</code> (angka ruang) atau <code>{room_slug}</code> (misal <code>ruang-01</code>) agar tiap ruang membuka halaman yang sesuai.
                            </div>
                        </div>

                        <div>
                            <button
                                type="button"
                                class="btn btn-secondary btn-sm"
                                style="padding: 8px 14px;"
                                onclick="document.getElementById('save-qr-url-form').submit()"
                            >
                                Simpan Link QR
                            </button>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 16px;">
                                Buka / Cetak PDF
                            </button>
                        </div>
                    </div>
                </form>

                <form id="save-qr-url-form" method="POST" action="{{ route('exam-sessions.seating.qr-url', $session) }}" style="display: none;">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="plan_id" value="{{ $activePlan->id }}">
                    <input type="hidden" id="qr_custom_url_save_hidden" name="qr_custom_url" value="{{ old('qr_custom_url', $session->qr_floor_plan_url ?? '') }}">
                </form>
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 18px 0;">

            {{-- FILTER & PENCARIAN TABEL HASIL NOMOR MEJA --}}
            <form method="GET" action="{{ route('exam-sessions.seating.index', $session) }}" style="display: grid; grid-template-columns: 2fr 1fr 1fr 0.8fr auto; gap: 10px; align-items: end; margin-bottom: 14px;">
                <input type="hidden" name="plan_id" value="{{ $activePlan->id }}">

                <div>
                    <label for="search" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Cari Nama / Nomor Meja / Nomor Peserta
                    </label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Contoh: Agung atau ASAS.0262769.1..."
                        style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"
                    >
                </div>

                <div>
                    <label for="filter_room" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Filter Ruangan
                    </label>
                    <select id="filter_room" name="filter_room" style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        <option value="">Semua Ruangan</option>
                        @foreach ($planRooms as $rName)
                            <option value="{{ $rName }}" {{ $filters['filter_room'] === $rName ? 'selected' : '' }}>
                                {{ $rName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_class" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Filter Kelas
                    </label>
                    <select id="filter_class" name="filter_class" style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        <option value="">Semua Kelas</option>
                        @foreach ($planClasses as $clsName)
                            <option value="{{ $clsName }}" {{ $filters['filter_class'] === $clsName ? 'selected' : '' }}>
                                {{ $clsName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_desk" style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        No. Meja
                    </label>
                    <input
                        type="number"
                        id="filter_desk"
                        name="filter_desk"
                        min="1"
                        max="{{ max(1, $maxDeskNumber) }}"
                        value="{{ $filters['filter_desk'] }}"
                        placeholder="1 - {{ $maxDeskNumber }}"
                        style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"
                    >
                </div>

                <div class="actions-group">
                    <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                    @if ($filters['search'] !== '' || $filters['filter_room'] !== '' || $filters['filter_class'] !== '' || $filters['filter_desk'] !== '')
                        <a href="{{ route('exam-sessions.seating.index', ['examSession' => $session->id, 'plan_id' => $activePlan->id]) }}" class="btn btn-secondary btn-sm">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            {{-- TABEL HASIL PENEMPATAN --}}
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">No.</th>
                            <th style="width: 12%;">Ruangan</th>
                            <th style="width: 14%;">Meja &amp; Kursi</th>
                            <th style="width: 8%;">Urut</th>
                            <th style="width: 21%;">Nomor Meja Peserta</th>
                            <th style="width: 14%;">No. Peserta Asli</th>
                            <th style="width: 16%;">Nama Siswa</th>
                            <th style="width: 10%;">Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $index => $row)
                            <tr>
                                <td>{{ $assignments->firstItem() + $index }}</td>
                                <td>
                                    <span class="seating-pill seating-pill-room">{{ $row->room_name }}</span>
                                </td>
                                <td>
                                    <strong>Meja {{ $row->desk_number }}</strong>
                                    <span class="seating-pill" style="margin-left: 4px;">Kursi {{ $row->seat_position }}</span>
                                </td>
                                <td><strong>{{ $row->seat_number }}</strong></td>
                                <td>
                                    <strong style="font-size: 13.5px; letter-spacing: 0.2px; color: #0f172a;">
                                        {{ $row->desk_participant_number }}
                                    </strong>
                                </td>
                                <td style="color: #475569;">{{ $row->participant_number_snapshot }}</td>
                                <td><strong>{{ $row->student_name_snapshot }}</strong></td>
                                <td>
                                    {{ $row->class_name_snapshot }}
                                    @if ($row->grade_level)
                                        <span class="seating-pill" style="margin-left: 3px;">T{{ $row->grade_level }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 20px; color: #64748b;">
                                    Tidak ada data penempatan yang cocok dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- KONTROL PAGINASI KUSTOM YANG RAPI (TANPA ICON SVG TAILWIND BESAR) --}}
            @if ($assignments && $assignments->hasPages())
                <div class="seating-pagination-bar">
                    <div style="font-size: 13px; color: #475569;">
                        Menampilkan <strong>{{ $assignments->firstItem() }}</strong> &ndash; <strong>{{ $assignments->lastItem() }}</strong>
                        dari <strong>{{ number_format($assignments->total(), 0, ',', '.') }}</strong> peserta
                        (Halaman {{ $assignments->currentPage() }} dari {{ $assignments->lastPage() }})
                    </div>

                    <div class="seating-pagination-links">
                        @if ($assignments->onFirstPage())
                            <span class="btn btn-secondary btn-sm" style="opacity: 0.5; pointer-events: none;">&laquo; Sebelumnya</span>
                        @else
                            <a href="{{ $assignments->previousPageUrl() }}" class="btn btn-secondary btn-sm">&laquo; Sebelumnya</a>
                        @endif

                        @php
                            $currentPage = $assignments->currentPage();
                            $lastPage = $assignments->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                        @endphp

                        @if ($startPage > 1)
                            <a href="{{ $assignments->url(1) }}" class="btn btn-secondary btn-sm">1</a>
                            @if ($startPage > 2)
                                <span style="padding: 0 4px; color: #64748b;">...</span>
                            @endif
                        @endif

                        @for ($page = $startPage; $page <= $endPage; $page++)
                            @if ($page === $currentPage)
                                <span class="btn btn-primary btn-sm">{{ $page }}</span>
                            @else
                                <a href="{{ $assignments->url($page) }}" class="btn btn-secondary btn-sm">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($endPage < $lastPage)
                            @if ($endPage < $lastPage - 1)
                                <span style="padding: 0 4px; color: #64748b;">...</span>
                            @endif
                            <a href="{{ $assignments->url($lastPage) }}" class="btn btn-secondary btn-sm">{{ $lastPage }}</a>
                        @endif

                        @if ($assignments->hasMorePages())
                            <a href="{{ $assignments->nextPageUrl() }}" class="btn btn-secondary btn-sm">Berikutnya &raquo;</a>
                        @else
                            <span class="btn btn-secondary btn-sm" style="opacity: 0.5; pointer-events: none;">Berikutnya &raquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    @endif

    {{-- MODAL KONFIRMASI ACAK ULANG PESERTA --}}
    <div id="rerandomize-modal" class="seating-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="rerandomize-modal-title">
        <div class="seating-modal-dialog">
            <h3 id="rerandomize-modal-title" style="margin: 0 0 8px 0; font-size: 17px; color: #0f172a;">
                Konfirmasi Acak Ulang Peserta
            </h3>
            <p style="margin: 0 0 10px 0; font-size: 13.5px; color: #334155; line-height: 1.5;">
                Pengacakan ulang akan membentuk <strong>pasangan meja dan nomor urut baru di setiap ruangan</strong> sesuai data pada Pengaturan Ruang Ujian.
            </p>
            <p style="margin: 0 0 16px 0; font-size: 13px; color: #475569; background: #f8fafc; padding: 10px 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                Versi pengacakan sebelumnya (termasuk versi yang sudah difinalisasi) <strong>tidak dihapus</strong> dan tetap dapat dibuka kembali melalui menu <em>Riwayat Versi</em>.
            </p>
            <div class="actions-group" style="justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeRerandomizeModal()">
                    Batal
                </button>
                <button type="button" class="btn btn-primary" onclick="submitGenerateForm()">
                    Ya, Buat Versi Acak Baru
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modeSelect = document.getElementById('selection_mode');
            const classPanel = document.getElementById('class-selector-panel');

            if (modeSelect && classPanel) {
                modeSelect.addEventListener('change', function () {
                    classPanel.style.display = this.value === 'classes' ? 'block' : 'none';
                    updateSelectedClassSummary();
                });
            }

            window.toggleAllClasses = function (checked) {
                document.querySelectorAll('.class-checkbox').forEach(function (cb) {
                    cb.checked = checked;
                });
                updateSelectedClassSummary();
            };

            window.updateSelectedClassSummary = function () {
                const feedback = document.getElementById('selected-class-feedback');
                if (!feedback) return;

                const checkedBoxes = Array.from(document.querySelectorAll('.class-checkbox:checked'));
                if (checkedBoxes.length === 0) {
                    feedback.innerHTML = '<span style="color: #991b1b;">Belum ada kelas yang dipilih.</span>';
                    return;
                }

                let totalStudents = 0;
                const grades = new Set();
                checkedBoxes.forEach(function (cb) {
                    totalStudents += parseInt(cb.dataset.count || '0', 10);
                    if (cb.dataset.grade && cb.dataset.grade !== 'unknown') {
                        grades.add(cb.dataset.grade);
                    }
                });

                let html = 'Terpilih <strong>' + checkedBoxes.length + ' kelas</strong> (' + totalStudents + ' siswa). ';
                if (grades.size === 1) {
                    const onlyGrade = Array.from(grades)[0];
                    html += '<span style="color: #92400e; font-weight: 600;">Catatan: Hanya 1 tingkat kelas (Tingkat ' + onlyGrade + ') yang dipilih, sehingga pasangan lintas tingkat kelas tidak dapat diterapkan.</span>';
                } else if (grades.size > 1) {
                    html += '<span style="color: #065f46; font-weight: 600;">Mencakup ' + grades.size + ' tingkat kelas berbeda (Tingkat ' + Array.from(grades).sort().join(', ') + ') &mdash; siap dipasangkan lintas tingkat.</span>';
                }
                feedback.innerHTML = html;
            };

            window.openRerandomizeModal = function () {
                const modal = document.getElementById('rerandomize-modal');
                if (modal) modal.classList.add('is-open');
            };

            window.closeRerandomizeModal = function () {
                const modal = document.getElementById('rerandomize-modal');
                if (modal) modal.classList.remove('is-open');
            };

            window.submitGenerateForm = function () {
                const form = document.getElementById('seating-generate-form');
                if (form) form.submit();
            };

            updateSelectedClassSummary();
        })();
    </script>
@endsection