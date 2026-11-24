@extends('layouts.app')

@section('title', 'Desain Kartu & Halaman Cetak - ' . $session->session_name)

@section('content')
    @include('cards.partials._card-styles', ['layout' => $layout])

    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Langkah 4 &amp; 5 &bull; Desain Kartu &amp; Halaman Cetak</span>
                <h1 class="card-title" style="font-size: 20px;">{{ $session->session_name }}</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Atur ukuran fisik kartu (mm), elemen sisi depan dan sisi belakang, serta susunan lembar cetak sebelum ekspor PDF.
                </p>
            </div>
            <div class="actions-group">
                <a href="{{ route('exam-sessions.schedules.index', $session) }}" class="btn btn-secondary">
                    Jadwal Ujian ({{ $schedules->count() }} Mapel)
                </a>
                <a href="{{ route('exam-sessions.export.index', $session) }}" class="btn btn-primary">
                    Lanjut ke Ekspor PDF
                </a>
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">
                    Kembali ke Sesi
                </a>
            </div>
        </div>
    </section>

    @if ($layoutError)
        <div class="alert alert-danger">
            <strong>Perhatian Tata Letak Cetak:</strong> {{ $layoutError }}
        </div>
    @endif

    @php
        $sideMode = old('card_side_mode', $layout['card_side_mode']);
        $isDoubleSided = $sideMode === 'double_sided';
        $paperSize = old('print_paper_size', $layout['print_paper_size']);
    @endphp

    <form action="{{ route('exam-sessions.design.update', $session) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- BAGIAN 1: PILIHAN MODE KARTU (1 SISI VS 2 SISI) & UKURAN FISIK --}}
        <section class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                <div>
                    <h2 class="card-title" style="margin-bottom: 2px;">1. Mode Kartu &amp; Dimensi Fisik (Milimeter)</h2>
                    <p class="card-desc" style="margin-bottom: 0;">
                        Pilih mode kartu 1 sisi standar atau kartu 2 sisi berpasangan (kiri identitas peserta, kanan jadwal ujian).
                    </p>
                </div>

                <button type="button" class="btn btn-secondary btn-sm" onclick="applyReferenceA4Preset()">
                    Terapkan Preset A4 2 Sisi (5 Pasang / Halaman: 92 &times; 52 mm)
                </button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <label style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; cursor: pointer; display: flex; align-items: flex-start; gap: 10px; background: {{ ! $isDoubleSided ? '#eff6ff' : '#ffffff' }};">
                    <input type="radio" name="card_side_mode" value="single" {{ ! $isDoubleSided ? 'checked' : '' }} style="margin-top: 3px;">
                    <div>
                        <div style="font-weight: 700; font-size: 13px;">Kartu 1 Sisi (Mode Standar)</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            Satu kartu identitas peserta per slot tanpa sisi belakang jadwal.
                        </div>
                    </div>
                </label>

                <label style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; cursor: pointer; display: flex; align-items: flex-start; gap: 10px; background: {{ $isDoubleSided ? '#eff6ff' : '#ffffff' }};">
                    <input type="radio" name="card_side_mode" value="double_sided" {{ $isDoubleSided ? 'checked' : '' }} style="margin-top: 3px;">
                    <div>
                        <div style="font-weight: 700; font-size: 13px;">Kartu 2 Sisi Berdampingan (Front &amp; Back)</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            Kolom kiri sisi depan (identitas), kolom kanan sisi belakang (jadwal ujian) dalam satu baris pasangan.
                        </div>
                    </div>
                </label>
            </div>

            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Lebar 1 Sisi Kartu (mm)</label>
                    <input
                        type="number"
                        step="0.5"
                        min="40"
                        max="250"
                        id="card_width_mm"
                        name="card_width_mm"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('card_width_mm', $layout['card_width_mm']) }}"
                        required
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Tinggi 1 Sisi Kartu (mm)</label>
                    <input
                        type="number"
                        step="0.5"
                        min="30"
                        max="250"
                        id="card_height_mm"
                        name="card_height_mm"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('card_height_mm', $layout['card_height_mm']) }}"
                        required
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Warna Garis Tepi</label>
                    <input
                        type="color"
                        name="border_color"
                        style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px;"
                        value="{{ old('border_color', $layout['border_color']) }}"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Latar Header Kartu</label>
                    <input
                        type="color"
                        name="header_bg_color"
                        style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px;"
                        value="{{ old('header_bg_color', $layout['header_bg_color']) }}"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Warna Teks Judul</label>
                    <input
                        type="color"
                        name="accent_color"
                        style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px;"
                        value="{{ old('accent_color', $layout['accent_color']) }}"
                    >
                </div>
            </div>
        </section>

        {{-- BAGIAN 2: TAB PENGATURAN SISI DEPAN & SISI BELAKANG --}}
        <section class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 14px;">
                <div>
                    <h2 class="card-title" style="margin-bottom: 2px;">2. Pengaturan Elemen Sisi Depan &amp; Sisi Belakang</h2>
                    <p class="card-desc" style="margin-bottom: 0;">
                        Pilih komponen yang ditampilkan pada masing-masing sisi kartu.
                    </p>
                </div>

                <div class="actions-group">
                    <button type="button" id="btn-tab-front" class="btn btn-primary btn-sm" onclick="switchDesignTab('front')">
                        Desain Sisi Depan (Identitas)
                    </button>
                    <button type="button" id="btn-tab-back" class="btn btn-secondary btn-sm" onclick="switchDesignTab('back')">
                        Desain Sisi Belakang (Jadwal)
                    </button>
                </div>
            </div>

            {{-- TAB A: DESAIN SISI DEPAN --}}
            <div id="panel-tab-front">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 14px;">
                    <div style="background: var(--bg-muted); padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px;">Elemen Kop Sekolah</div>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_logo" value="1" {{ old('show_logo', $layout['show_logo']) ? 'checked' : '' }}>
                            <span>Logo Sekolah</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_foundation_name" value="1" {{ old('show_foundation_name', $layout['show_foundation_name']) ? 'checked' : '' }}>
                            <span>Nama Yayasan</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_accreditation" value="1" {{ old('show_accreditation', $layout['show_accreditation']) ? 'checked' : '' }}>
                            <span>Akreditasi Sekolah</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_school_address" value="1" {{ old('show_school_address', $layout['show_school_address']) ? 'checked' : '' }}>
                            <span>Alamat Sekolah</span>
                        </label>
                    </div>

                    <div style="background: var(--bg-muted); padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px;">Elemen Identitas Peserta</div>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_participant_number" value="1" {{ old('show_participant_number', $layout['show_participant_number']) ? 'checked' : '' }}>
                            <span>Nomor Peserta</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_nis" value="1" {{ old('show_nis', $layout['show_nis']) ? 'checked' : '' }}>
                            <span>NIS Siswa</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_nisn" value="1" {{ old('show_nisn', $layout['show_nisn']) ? 'checked' : '' }}>
                            <span>NISN Siswa</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_class_name" value="1" {{ old('show_class_name', $layout['show_class_name']) ? 'checked' : '' }}>
                            <span>Kelas / Rombel</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_room_name" value="1" {{ old('show_room_name', $layout['show_room_name']) ? 'checked' : '' }}>
                            <span>Ruang Ujian</span>
                        </label>
                    </div>

                    <div style="background: var(--bg-muted); padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px;">Elemen Pengesahan &amp; Catatan</div>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_issue_city" value="1" {{ old('show_issue_city', $layout['show_issue_city']) ? 'checked' : '' }}>
                            <span>Kota Penerbitan Kartu</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_exam_date" value="1" {{ old('show_exam_date', $layout['show_exam_date']) ? 'checked' : '' }}>
                            <span>Tanggal Terbit / Ujian</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_signature" value="1" {{ old('show_signature', $layout['show_signature']) ? 'checked' : '' }}>
                            <span>Gambar Tanda Tangan</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_stamp" value="1" {{ old('show_stamp', $layout['show_stamp']) ? 'checked' : '' }}>
                            <span>Stempel Sekolah</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_signatory_nip" value="1" {{ old('show_signatory_nip', $layout['show_signatory_nip']) ? 'checked' : '' }}>
                            <span>NIP / NIY Penanggung Jawab</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px;">
                            <input type="checkbox" name="show_card_note" value="1" {{ old('show_card_note', $layout['show_card_note']) ? 'checked' : '' }}>
                            <span>Catatan Kaki Kartu</span>
                        </label>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; align-items: end; background: var(--bg-muted); padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; padding-bottom: 6px;">
                        <input type="checkbox" name="show_photo" value="1" {{ old('show_photo', $layout['show_photo']) ? 'checked' : '' }}>
                        <span>Tampilkan Bingkai Foto</span>
                    </label>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Posisi Foto</label>
                        <select name="photo_position" class="input-text" style="min-width: 100%; width: 100%;">
                            @php $photoPos = old('photo_position', $layout['photo_position']); @endphp
                            <option value="right" {{ $photoPos === 'right' ? 'selected' : '' }}>Kanan</option>
                            <option value="left" {{ $photoPos === 'left' ? 'selected' : '' }}>Kiri</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Lebar Foto (mm)</label>
                        <input type="number" step="0.5" min="10" max="60" name="photo_width_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('photo_width_mm', $layout['photo_width_mm']) }}">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Tinggi Foto (mm)</label>
                        <input type="number" step="0.5" min="10" max="80" name="photo_height_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('photo_height_mm', $layout['photo_height_mm']) }}">
                    </div>
                </div>
            </div>

            {{-- TAB B: DESAIN SISI BELAKANG --}}
            <div id="panel-tab-back" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1.2fr 0.8fr; gap: 14px; align-items: end; background: var(--bg-muted); padding: 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; padding-bottom: 6px;">
                        <input type="checkbox" name="show_back_header" value="1" {{ old('show_back_header', $layout['show_back_header']) ? 'checked' : '' }}>
                        <span>Tampilkan Header Sisi Belakang</span>
                    </label>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Judul Header Sisi Belakang</label>
                        <input
                            type="text"
                            name="back_header_title"
                            class="input-text"
                            style="min-width: 100%; width: 100%;"
                            value="{{ old('back_header_title', $layout['back_header_title']) }}"
                            placeholder="Contoh: JADWAL UJIAN"
                        >
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Ukuran Teks Jadwal (pt)</label>
                        <input
                            type="number"
                            step="0.2"
                            min="4.5"
                            max="11"
                            name="schedule_font_size_pt"
                            class="input-text"
                            style="min-width: 100%; width: 100%;"
                            value="{{ old('schedule_font_size_pt', $layout['schedule_font_size_pt']) }}"
                            required
                        >
                    </div>
                </div>

                <div style="margin-top: 12px; font-size: 12.5px; color: var(--text-secondary);">
                    Total baris jadwal saat ini: <strong>{{ $schedules->count() }} mata pelajaran</strong>.
                    @if ($schedules->count() > 14)
                        <span style="color: #b45309; font-weight: 600;">
                            Catatan: Jadwal memuat lebih dari 14 baris; perkecil Ukuran Teks Jadwal (misalnya 5.5 - 6.0 pt) atau tambah tinggi kartu jika bagian bawah tabel tampak terpotong pada pratinjau.
                        </span>
                    @endif
                    <a href="{{ route('exam-sessions.schedules.index', $session) }}" style="margin-left: 6px; font-weight: 600;">
                        Kelola Daftar Jadwal Ujian &rarr;
                    </a>
                </div>
            </div>
        </section>

        {{-- BAGIAN 3: PENGATURAN HALAMAN CETAK --}}
        <section class="card">
            <h2 class="card-title">3. Pengaturan Halaman Cetak &amp; Garis Potong</h2>
            <p class="card-desc">
                Atur ukuran kertas, orientasi, margin, jarak antar kartu, dan garis potong putus-putus.
            </p>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Ukuran Kertas</label>
                    <select id="print_paper_size" name="print_paper_size" class="input-text" style="min-width: 100%; width: 100%;" onchange="toggleCustomPaper()">
                        <option value="A4" {{ $paperSize === 'A4' ? 'selected' : '' }}>A4 (210 &times; 297 mm)</option>
                        <option value="F4" {{ $paperSize === 'F4' ? 'selected' : '' }}>F4 / Folio (215 &times; 330 mm)</option>
                        <option value="Letter" {{ $paperSize === 'Letter' ? 'selected' : '' }}>Letter (215.9 &times; 279.4 mm)</option>
                        <option value="Custom" {{ $paperSize === 'Custom' ? 'selected' : '' }}>Custom (Ukuran Sendiri)</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Orientasi Kertas</label>
                    <select id="print_orientation" name="print_orientation" class="input-text" style="min-width: 100%; width: 100%;">
                        @php $orient = old('print_orientation', $layout['print_orientation']); @endphp
                        <option value="portrait" {{ $orient === 'portrait' ? 'selected' : '' }}>Portrait (Tegak)</option>
                        <option value="landscape" {{ $orient === 'landscape' ? 'selected' : '' }}>Landscape (Mendatar)</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        {{ $isDoubleSided ? 'Pasangan per Baris (0 = Otomatis)' : 'Kartu per Baris (0 = Otomatis)' }}
                    </label>
                    <input type="number" min="0" max="20" id="cards_per_row" name="cards_per_row" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('cards_per_row', $layout['cards_per_row']) }}">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Baris per Halaman (0 = Otomatis)</label>
                    <input type="number" min="0" max="20" id="cards_per_column" name="cards_per_column" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('cards_per_column', $layout['cards_per_column']) }}">
                </div>
            </div>

            <div id="box-custom-paper" style="display: {{ $paperSize === 'Custom' ? 'grid' : 'none' }}; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Lebar Kertas Custom (mm)</label>
                    <input type="number" step="0.5" min="50" max="1000" name="custom_paper_width_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('custom_paper_width_mm', $layout['custom_paper_width_mm'] ?? 210) }}">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Tinggi Kertas Custom (mm)</label>
                    <input type="number" step="0.5" min="50" max="1000" name="custom_paper_height_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('custom_paper_height_mm', $layout['custom_paper_height_mm'] ?? 297) }}">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 4px;">Margin Atas (mm)</label>
                    <input type="number" step="0.5" min="0" max="80" id="margin_top_mm" name="margin_top_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('margin_top_mm', $layout['margin_top_mm']) }}">
                </div>
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 4px;">Margin Kanan (mm)</label>
                    <input type="number" step="0.5" min="0" max="80" id="margin_right_mm" name="margin_right_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('margin_right_mm', $layout['margin_right_mm']) }}">
                </div>
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 4px;">Margin Bawah (mm)</label>
                    <input type="number" step="0.5" min="0" max="80" id="margin_bottom_mm" name="margin_bottom_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('margin_bottom_mm', $layout['margin_bottom_mm']) }}">
                </div>
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 4px;">Margin Kiri (mm)</label>
                    <input type="number" step="0.5" min="0" max="80" id="margin_left_mm" name="margin_left_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('margin_left_mm', $layout['margin_left_mm']) }}">
                </div>
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 4px;">Jarak Horizontal (mm)</label>
                    <input type="number" step="0.5" min="0" max="40" id="gap_x_mm" name="gap_x_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('gap_x_mm', $layout['gap_x_mm']) }}">
                </div>
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 4px;">Jarak Vertikal (mm)</label>
                    <input type="number" step="0.5" min="0" max="40" id="gap_y_mm" name="gap_y_mm" class="input-text" style="min-width: 100%; width: 100%;" value="{{ old('gap_y_mm', $layout['gap_y_mm']) }}">
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; border-top: 1px solid #e2e8f0; padding-top: 12px;">
                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="show_cut_lines" value="1" {{ old('show_cut_lines', $layout['show_cut_lines']) ? 'checked' : '' }}>
                    <span>Tampilkan Garis Potong Putus-Putus di Lembar Cetak</span>
                </label>

                <button type="submit" class="btn btn-primary">
                    Simpan Desain &amp; Perbarui Pratinjau
                </button>
            </div>
        </section>
    </form>

    {{-- BAGIAN 4: AREA PRATINJAU LANGSUNG --}}
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px;">
            <div>
                <h2 class="card-title" style="margin-bottom: 2px;">4. Pratinjau Desain &amp; Lembar Cetak</h2>
                <p class="card-desc" style="margin-bottom: 0;">
                    Menampilkan simulasi siswa: <strong>{{ $previewStudent->student_name }}</strong> ({{ $previewStudent->class_name }}).
                    @if ($metrics)
                        Kapasitas cetak: <strong>{{ $metrics['cards_per_page'] }} {{ $metrics['is_double_sided'] ? 'pasangan kartu' : 'kartu' }} / halaman</strong>.
                    @endif
                </p>
            </div>

            <div class="actions-group">
                <a
                    href="{{ route('exam-sessions.design.edit', $session) }}"
                    class="btn {{ ! $useLongestSample ? 'btn-primary' : 'btn-secondary' }} btn-sm"
                >
                    Sampel Siswa Pertama
                </a>
                <a
                    href="{{ route('exam-sessions.design.edit', [$session, 'sample' => 'longest']) }}"
                    class="btn {{ $useLongestSample ? 'btn-primary' : 'btn-secondary' }} btn-sm"
                >
                    Uji Sampel Nama Terpanjang
                </a>
            </div>
        </div>

        @if ($layout['card_side_mode'] === 'double_sided')
            {{-- 4 AREA PRATINJAU KARTU DUA SISI --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                {{-- 1. Preview Front Side --}}
                <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                        1. Preview Front Side (Sisi Depan &mdash; Identitas Peserta)
                    </div>
                    <div style="overflow-x: auto; display: flex; justify-content: center; padding: 8px 0;">
                        @include('cards.partials._front-card', [
                            'session' => $session,
                            'student' => $previewStudent,
                            'layout' => $layout,
                            'logoUrl' => $logoUrl,
                            'signatureUrl' => $signatureUrl,
                            'stampUrl' => $stampUrl,
                        ])
                    </div>
                </div>

                {{-- 2. Preview Back Side --}}
                <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                        2. Preview Back Side (Sisi Belakang &mdash; Jadwal Ujian)
                    </div>
                    <div style="overflow-x: auto; display: flex; justify-content: center; padding: 8px 0;">
                        @include('cards.partials._back-card', [
                            'session' => $session,
                            'layout' => $layout,
                            'schedules' => $schedules,
                        ])
                    </div>
                </div>
            </div>

            {{-- 3. Preview Pasangan Kartu Berdampingan --}}
            <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px; margin-bottom: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                    3. Preview Pasangan Kartu Berdampingan (Kiri: Depan &bull; Kanan: Belakang)
                </div>
                <div style="overflow-x: auto; padding: 8px 0;">
                    <table class="ds-pair-table">
                        <tr>
                            <td class="ds-pair-cell">
                                @include('cards.partials._front-card', [
                                    'session' => $session,
                                    'student' => $previewStudent,
                                    'layout' => $layout,
                                    'logoUrl' => $logoUrl,
                                    'signatureUrl' => $signatureUrl,
                                    'stampUrl' => $stampUrl,
                                ])
                            </td>
                            <td class="ds-pair-gap"></td>
                            <td class="ds-pair-cell">
                                @include('cards.partials._back-card', [
                                    'session' => $session,
                                    'layout' => $layout,
                                    'schedules' => $schedules,
                                ])
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- 4. Preview Lembar Cetak --}}
            @if ($metrics)
                <div style="background: #cbd5e1; border: 1px solid #94a3b8; border-radius: 6px; padding: 16px;">
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                        4. Preview Lembar Cetak ({{ $layout['print_paper_size'] }} {{ ucfirst($layout['print_orientation']) }} &mdash; {{ $metrics['cards_per_page'] }} Pasang / Halaman)
                    </div>
                    <div style="overflow-x: auto; padding: 8px 0;">
                        <div style="width: {{ $metrics['paper_width_mm'] }}mm; min-height: {{ $metrics['paper_height_mm'] }}mm; background: #ffffff; margin: 0 auto; padding: {{ $layout['margin_top_mm'] }}mm {{ $layout['margin_right_mm'] }}mm {{ $layout['margin_bottom_mm'] }}mm {{ $layout['margin_left_mm'] }}mm; box-sizing: border-box; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);">
                            @for ($r = 0; $r < $metrics['rows']; $r++)
                                <div style="{{ $r < ($metrics['rows'] - 1) ? 'margin-bottom: ' . $layout['gap_y_mm'] . 'mm;' : '' }} {{ $layout['show_cut_lines'] ? 'border-bottom: 0.25mm dashed #94a3b8; padding-bottom: ' . ($layout['gap_y_mm'] / 2) . 'mm;' : '' }}">
                                    <table class="ds-pair-table">
                                        <tr>
                                            @for ($c = 0; $c < $metrics['columns']; $c++)
                                                <td class="ds-pair-cell">
                                                    @include('cards.partials._front-card', [
                                                        'session' => $session,
                                                        'student' => $previewStudent,
                                                        'layout' => $layout,
                                                        'logoUrl' => $logoUrl,
                                                        'signatureUrl' => $signatureUrl,
                                                        'stampUrl' => $stampUrl,
                                                    ])
                                                </td>
                                                <td class="ds-pair-gap" style="{{ $layout['show_cut_lines'] ? 'border-left: 0.25mm dashed #94a3b8;' : '' }}"></td>
                                                <td class="ds-pair-cell">
                                                    @include('cards.partials._back-card', [
                                                        'session' => $session,
                                                        'layout' => $layout,
                                                        'schedules' => $schedules,
                                                    ])
                                                </td>
                                                @if ($c < ($metrics['columns'] - 1))
                                                    <td class="ds-pair-gap"></td>
                                                @endif
                                            @endfor
                                        </tr>
                                    </table>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- PRATINJAU MODE 1 SISI STANDAR --}}
            @php
                $singleAssets = [
                    'logo_src' => $logoUrl,
                    'signature_src' => $signatureUrl,
                    'photo_src' => null,
                ];
            @endphp

            <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px; margin-bottom: 16px;">
                <div style="font-size: 12px; font-weight: 700; margin-bottom: 10px;">1. Pratinjau Kartu Tunggal (1 Sisi)</div>
                <div style="overflow-x: auto; display: flex; justify-content: center; padding: 8px 0;">
                    @include('cards.partials._single-card', [
                        'session' => $session,
                        'student' => $previewStudent,
                        'layout' => $layout,
                        'assets' => $singleAssets,
                    ])
                </div>
            </div>

            @if ($metrics)
                <div style="background: #cbd5e1; border: 1px solid #94a3b8; border-radius: 6px; padding: 16px;">
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                        2. Pratinjau Lembar Cetak ({{ $layout['print_paper_size'] }} {{ ucfirst($layout['print_orientation']) }} &mdash; {{ $metrics['cards_per_page'] }} Kartu / Halaman)
                    </div>
                    <div style="overflow-x: auto; padding: 8px 0;">
                        <div style="width: {{ $metrics['paper_width_mm'] }}mm; min-height: {{ $metrics['paper_height_mm'] }}mm; background: #ffffff; margin: 0 auto; padding: {{ $layout['margin_top_mm'] }}mm {{ $layout['margin_right_mm'] }}mm {{ $layout['margin_bottom_mm'] }}mm {{ $layout['margin_left_mm'] }}mm; box-sizing: border-box; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);">
                            <table style="border-collapse: collapse; margin: 0 auto;">
                                @for ($r = 0; $r < $metrics['rows']; $r++)
                                    <tr>
                                        @for ($c = 0; $c < $metrics['columns']; $c++)
                                            <td style="padding: 0 {{ $c < ($metrics['columns'] - 1) ? $layout['gap_x_mm'] : 0 }}mm {{ $r < ($metrics['rows'] - 1) ? $layout['gap_y_mm'] : 0 }}mm 0; vertical-align: top;">
                                                @include('cards.partials._single-card', [
                                                    'session' => $session,
                                                    'student' => $previewStudent,
                                                    'layout' => $layout,
                                                    'assets' => $singleAssets,
                                                ])
                                            </td>
                                        @endfor
                                    </tr>
                                @endfor
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </section>

    <script>
        function switchDesignTab(tab) {
            const panelFront = document.getElementById('panel-tab-front');
            const panelBack = document.getElementById('panel-tab-back');
            const btnFront = document.getElementById('btn-tab-front');
            const btnBack = document.getElementById('btn-tab-back');

            if (tab === 'back') {
                panelFront.style.display = 'none';
                panelBack.style.display = 'block';
                btnFront.className = 'btn btn-secondary btn-sm';
                btnBack.className = 'btn btn-primary btn-sm';
            } else {
                panelFront.style.display = 'block';
                panelBack.style.display = 'none';
                btnFront.className = 'btn btn-primary btn-sm';
                btnBack.className = 'btn btn-secondary btn-sm';
            }
        }

        function toggleCustomPaper() {
            const size = document.getElementById('print_paper_size').value;
            document.getElementById('box-custom-paper').style.display = size === 'Custom' ? 'grid' : 'none';
        }

        function applyReferenceA4Preset() {
            const doubleRadio = document.querySelector('input[name="card_side_mode"][value="double_sided"]');
            if (doubleRadio) doubleRadio.checked = true;

            document.getElementById('card_width_mm').value = '92';
            document.getElementById('card_height_mm').value = '52';
            document.getElementById('print_paper_size').value = 'A4';
            document.getElementById('print_orientation').value = 'portrait';
            document.getElementById('margin_top_mm').value = '10';
            document.getElementById('margin_right_mm').value = '10';
            document.getElementById('margin_bottom_mm').value = '10';
            document.getElementById('margin_left_mm').value = '10';
            document.getElementById('gap_x_mm').value = '4';
            document.getElementById('gap_y_mm').value = '3';
            document.getElementById('cards_per_row').value = '1';
            document.getElementById('cards_per_column').value = '5';
            toggleCustomPaper();
        }
    </script>
@endsection