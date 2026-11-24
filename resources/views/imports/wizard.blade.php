@extends('layouts.app')

@section('title', 'Impor Data Siswa - ' . $session->session_name)

@section('content')
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Langkah 1 &bull; Impor Data Siswa</span>
                <h1 class="card-title" style="font-size: 20px;">{{ $session->session_name }}</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Unggah daftar siswa dari file Excel (.xlsx) atau CSV (.csv), pasangkan kolomnya, lalu periksa hasil validasi sebelum disimpan.
                </p>
            </div>
            <div class="actions-group">
                @if ($wizard)
                    <form action="{{ route('exam-sessions.import.cancel', $session) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger">Batalkan Impor</button>
                    </form>
                @endif
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">Kembali ke Sesi</a>
            </div>
        </div>
    </section>

    {{-- BAGIAN 1: UNGGAH FILE EXCEL / CSV --}}
    <section class="card">
        <h2 class="card-title">1. Pilih File Excel atau CSV</h2>
        <p class="card-desc">
            Format didukung: <strong>.xlsx</strong> dan <strong>.csv</strong> (maksimal 20 MB). Angka nol di depan pada NIS, NISN, dan Nomor Peserta akan dipertahankan secara otomatis.
        </p>

        <form
            action="{{ route('exam-sessions.import.upload', $session) }}"
            method="POST"
            enctype="multipart/form-data"
            class="form-row"
            onsubmit="showProcessingState(this, 'Mengunggah & membaca struktur file...')"
        >
            @csrf
            <input
                type="file"
                name="spreadsheet_file"
                accept=".xlsx,.csv"
                class="input-text"
                style="padding: 7px 10px;"
                required
            >
            <button type="submit" class="btn btn-primary">
                {{ $wizard ? 'Ganti File Unggahan' : 'Unggah & Baca Kolom' }}
            </button>
        </form>

        @if ($wizard)
            <div style="margin-top: 12px; padding: 10px 14px; background: var(--bg-muted); border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;">
                File aktif saat ini: <strong>{{ $wizard['original_name'] }}</strong>
                ({{ strtoupper($wizard['extension']) }})
            </div>
        @endif
    </section>

    @if ($wizard)
        {{-- BAGIAN 2: PENGATURAN SHEET, HEADER & PEMETAAN KOLOM --}}
        <section class="card">
            <h2 class="card-title">2. Atur Sheet, Baris Header, dan Pemetaan Kolom</h2>
            <p class="card-desc">
                Sesuaikan baris judul kolom apabila file memiliki kop judul di bagian atas, lalu pasangkan kolom file ke data siswa.
            </p>

            <form
                action="{{ route('exam-sessions.import.preview', $session) }}"
                method="POST"
                onsubmit="showProcessingState(this, 'Memproses dan memeriksa data siswa...')"
            >
                @csrf
                <input type="hidden" name="action_mode" id="action_mode_input" value="run_validation">

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; background: var(--bg-muted); padding: 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    @if ($wizard['extension'] === 'xlsx')
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Pilih Sheet Excel</label>
                            <select name="sheet_index" class="input-text" style="min-width: 100%; width: 100%;">
                                @foreach ($wizard['sheets'] as $sheet)
                                    <option value="{{ $sheet['index'] }}" {{ (int) $wizard['selected_sheet_index'] === (int) $sheet['index'] ? 'selected' : '' }}>
                                        {{ $sheet['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="sheet_index" value="0">
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Pemisah Kolom CSV</label>
                            <select name="csv_delimiter" class="input-text" style="min-width: 100%; width: 100%;">
                                <option value=";" {{ $wizard['csv_delimiter'] === ';' ? 'selected' : '' }}>Titik Koma ( ; )</option>
                                <option value="," {{ $wizard['csv_delimiter'] === ',' ? 'selected' : '' }}>Koma ( , )</option>
                                <option value="tab" {{ $wizard['csv_delimiter'] === "\t" ? 'selected' : '' }}>Tab</option>
                            </select>
                        </div>
                    @endif

                    <div>
                        <label style="display: block; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Nomor Baris Header</label>
                        <input
                            type="number"
                            name="header_row_number"
                            min="1"
                            max="50"
                            value="{{ $wizard['header_row_number'] }}"
                            class="input-text"
                            style="min-width: 100%; width: 100%;"
                        >
                    </div>

                    <div style="display: flex; align-items: flex-end;">
                        <button
                            type="submit"
                            class="btn btn-secondary"
                            style="width: 100%;"
                            onclick="setActionMode('reload_structure')"
                        >
                            Muat Ulang Header
                        </button>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;">Field Data Aplikasi</th>
                            <th style="width: 35%;">Kolom pada File Excel/CSV</th>
                            <th style="width: 35%;">Contoh Isi Data Baris Pertama</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $firstPreviewCells = $wizard['preview_data_rows'][0]['cells'] ?? [];
                        @endphp
                        @foreach ($supportedFields as $fieldKey => $fieldLabel)
                            @php
                                $selectedColIndex = $wizard['mapping'][$fieldKey] ?? null;
                                $sampleValue = ($selectedColIndex !== null && isset($firstPreviewCells[$selectedColIndex]))
                                    ? $firstPreviewCells[$selectedColIndex]
                                    : '-';
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $fieldLabel }}</strong>
                                </td>
                                <td>
                                    <select name="mapping[{{ $fieldKey }}]" class="input-text" style="min-width: 100%; width: 100%;">
                                        <option value="">-- Tidak Dipetakan / Kosong --</option>
                                        @foreach ($wizard['headers'] as $header)
                                            <option
                                                value="{{ $header['index'] }}"
                                                {{ $selectedColIndex !== null && (int) $selectedColIndex === (int) $header['index'] ? 'selected' : '' }}
                                            >
                                                Kolom {{ $header['column_letter'] }}: {{ $header['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="color: var(--text-secondary);">
                                    {{ $sampleValue !== '' ? $sampleValue : '(kosong)' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="margin-top: 16px; display: flex; gap: 20px; flex-wrap: wrap; align-items: center; justify-content: space-between; background: var(--bg-muted); padding: 12px 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; gap: 18px; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input
                                type="checkbox"
                                name="check_duplicate_nis"
                                value="1"
                                {{ ! empty($wizard['validation_options']['check_duplicate_nis']) ? 'checked' : '' }}
                            >
                            <span>Periksa duplikasi NIS</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input
                                type="checkbox"
                                name="check_duplicate_nisn"
                                value="1"
                                {{ ! empty($wizard['validation_options']['check_duplicate_nisn']) ? 'checked' : '' }}
                            >
                            <span>Periksa duplikasi NISN</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        onclick="setActionMode('run_validation')"
                    >
                        Periksa &amp; Tampilkan Pratinjau Data
                    </button>
                </div>
            </form>
        </section>
    @endif

    @if ($wizard && ! empty($wizard['validation_result']))
        @php
            $summary = $wizard['validation_result']['summary'];
            $problemRows = $wizard['validation_result']['problem_rows'];
            $previewRows = $wizard['validation_result']['preview_rows'];
        @endphp

        {{-- BAGIAN 3: RINGKASAN VALIDASI & KONFIRMASI SIMPAN --}}
        <section class="card">
            <h2 class="card-title">3. Hasil Pemeriksaan dan Pratinjau Data</h2>
            <p class="card-desc">
                Tinjau ringkasan pembacaan data di bawah ini sebelum menyimpan data siswa ke dalam sesi ujian.
            </p>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px;">
                <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: var(--text-secondary);">Total Baris Terbaca</div>
                    <div style="font-size: 20px; font-weight: 700;">{{ number_format($summary['total_non_empty_rows'], 0, ',', '.') }}</div>
                </div>
                <div style="background: var(--success-bg); padding: 12px 14px; border-radius: 6px; border: 1px solid var(--success-border);">
                    <div style="font-size: 12px; color: var(--success-text);">Data Valid Siap Simpan</div>
                    <div style="font-size: 20px; font-weight: 700; color: var(--success-text);">{{ number_format($summary['valid_count'], 0, ',', '.') }}</div>
                </div>
                <div style="background: {{ $summary['error_count'] > 0 ? 'var(--error-bg)' : 'var(--bg-muted)' }}; padding: 12px 14px; border-radius: 6px; border: 1px solid {{ $summary['error_count'] > 0 ? 'var(--error-border)' : '#e2e8f0' }};">
                    <div style="font-size: 12px; color: {{ $summary['error_count'] > 0 ? 'var(--error-text)' : 'var(--text-secondary)' }};">Baris Bermasalah</div>
                    <div style="font-size: 20px; font-weight: 700; color: {{ $summary['error_count'] > 0 ? 'var(--error-text)' : 'var(--text-primary)' }};">{{ number_format($summary['error_count'], 0, ',', '.') }}</div>
                </div>
                <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: var(--text-secondary);">Data Duplikat</div>
                    <div style="font-size: 20px; font-weight: 700;">{{ number_format($summary['duplicate_count'], 0, ',', '.') }}</div>
                </div>
            </div>

            @if (! empty($problemRows))
                <div style="margin-bottom: 20px; border: 1px solid var(--error-border); border-radius: 6px; overflow: hidden;">
                    <div style="background: var(--error-bg); color: var(--error-text); padding: 10px 14px; font-weight: 700;">
                        Daftar Baris Bermasalah atau Peringatan ({{ count($problemRows) }} baris ditemukan)
                    </div>
                    <div style="max-height: 260px; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 12%;">Baris Ke</th>
                                    <th style="width: 24%;">Nama Siswa</th>
                                    <th style="width: 14%;">Kelas</th>
                                    <th style="width: 50%;">Keterangan Masalah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_slice($problemRows, 0, 100) as $problem)
                                    <tr>
                                        <td><strong>#{{ $problem['source_row'] }}</strong></td>
                                        <td>{{ $problem['data']['student_name'] !== '' ? $problem['data']['student_name'] : '(kosong)' }}</td>
                                        <td>{{ $problem['data']['class_name'] !== '' ? $problem['data']['class_name'] : '(kosong)' }}</td>
                                        <td style="color: {{ $problem['status'] === 'error' ? 'var(--error-text)' : '#92400e' }};">
                                            {{ implode(' | ', array_merge($problem['errors'], $problem['warnings'])) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <h3 style="font-size: 14px; margin: 0 0 8px;">Contoh Pratinjau Data Siswa (Maksimal 30 Baris Pertama)</h3>
            <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Baris</th>
                            <th>Status</th>
                            <th>Nama Siswa</th>
                            <th>NIS</th>
                            <th>NISN</th>
                            <th>Kelas</th>
                            <th>No. Peserta</th>
                            <th>Ruang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewRows as $row)
                            <tr>
                                <td>{{ $row['source_row'] }}</td>
                                <td>
                                    @if ($row['status'] === 'valid')
                                        <span class="badge" style="background: var(--success-bg); color: var(--success-text);">Valid</span>
                                    @elseif ($row['status'] === 'warning')
                                        <span class="badge" style="background: #fef3c7; color: #92400e;">Peringatan</span>
                                    @else
                                        <span class="badge" style="background: var(--error-bg); color: var(--error-text);">Bermasalah</span>
                                    @endif
                                </td>
                                <td>{{ $row['data']['student_name'] ?: '-' }}</td>
                                <td>{{ $row['data']['nis'] ?: '-' }}</td>
                                <td>{{ $row['data']['nisn'] ?: '-' }}</td>
                                <td>{{ $row['data']['class_name'] ?: '-' }}</td>
                                <td>{{ $row['data']['participant_number'] ?: '-' }}</td>
                                <td>{{ $row['data']['room_name'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($summary['valid_count'] > 0)
                <form
                    action="{{ route('exam-sessions.import.commit', $session) }}"
                    method="POST"
                    style="background: var(--bg-muted); padding: 16px; border-radius: 6px; border: 1px solid #cbd5e1;"
                    onsubmit="showProcessingState(this, 'Menyimpan data siswa ke database...')"
                >
                    @csrf
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">Mode Penyimpanan Data ke Sesi</label>
                            <select name="import_mode" class="input-text" style="min-width: 100%; width: 100%;">
                                <option value="append">Tambahkan ke data siswa yang sudah ada di sesi</option>
                                <option value="replace">Ganti seluruh data siswa pada sesi ini (Impor Ulang Bersih)</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">Tindakan untuk Baris Bermasalah</label>
                            <select name="error_handling" class="input-text" style="min-width: 100%; width: 100%;">
                                <option value="skip_errors">Simpan {{ number_format($summary['valid_count'], 0, ',', '.') }} baris valid dan lewati baris bermasalah</option>
                                <option value="strict">Batalkan penyimpanan jika masih ada baris bermasalah</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            Konfirmasi &amp; Simpan {{ number_format($summary['valid_count'], 0, ',', '.') }} Siswa Valid
                        </button>
                    </div>
                </form>
            @else
                <div class="alert alert-error" style="margin-bottom: 0;">
                    Tidak ada baris valid yang dapat disimpan. Silakan perbaiki pemetaan kolom atau periksa isi file Excel/CSV Anda.
                </div>
            @endif
        </section>
    @endif

    <script>
        function setActionMode(mode) {
            const actionInput = document.getElementById('action_mode_input');
            if (actionInput) {
                actionInput.value = mode;
            }
        }

        function showProcessingState(formEl, message) {
            if (formEl.dataset.submitting === 'true') {
                return false;
            }
            formEl.dataset.submitting = 'true';

            const notice = document.createElement('div');
            notice.className = 'alert alert-success';
            notice.style.marginTop = '12px';
            notice.textContent = message + ' Mohon tunggu sebentar.';
            formEl.appendChild(notice);

            setTimeout(function () {
                const buttons = formEl.querySelectorAll('button[type="submit"]');
                buttons.forEach(function (btn) {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                });
            }, 0);

            return true;
        }
    </script>
@endsection