@extends('layouts.app')

@section('title', 'Ekspor PDF Kartu Ujian - ' . $session->session_name)

@section('content')
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Langkah 6 &bull; Ekspor PDF Siap Cetak</span>
                <h1 class="card-title" style="font-size: 20px;">{{ $session->session_name }}</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Unduh sampel 1 halaman untuk uji ukur printer, cetak berdasarkan kelas tertentu, atau buat paket batch ZIP untuk ribuan siswa.
                </p>
            </div>
            <div class="actions-group">
                <a href="{{ route('exam-sessions.design.edit', $session) }}" class="btn btn-secondary">
                    Desain &amp; Tata Letak
                </a>
                <a href="{{ route('exam-sessions.students.index', $session) }}" class="btn btn-secondary">
                    Data Siswa
                </a>
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">
                    Kembali ke Sesi
                </a>
            </div>
        </div>

        @php
            $expW = (float) $calc['card_width_mm'];
            $expH = (float) $calc['card_height_mm'];
            $isB3Export = ($expW >= 102.0 && $expW <= 106.0 && $expH >= 73.0 && $expH <= 76.0);
            $unitLabel = ($calc['is_double_sided'] ?? false) ? 'Pasang (Depan + Belakang)' : 'Kartu';
        @endphp

        @if (! $isB3Export)
            <div class="alert alert-warning" style="margin-top: 14px; margin-bottom: 0;">
                <strong>Perhatian Ukuran Kartu:</strong> Sesi ini masih menggunakan ukuran <strong>{{ number_format($calc['card_width_mm'], 0) }} &times; {{ number_format($calc['card_height_mm'], 0) }} mm</strong> (bukan standar Plastik B3 105 &times; 75 mm).
                <a href="{{ route('exam-sessions.design.edit', $session) }}" style="font-weight: 700; text-decoration: underline; margin-left: 6px;">
                    Klik di sini untuk mengubah ke Standar Plastik B3 (105 &times; 75 mm) &rarr;
                </a>
            </div>
        @endif

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 18px 0;">

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
            <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Siswa Valid Siap Cetak</div>
                <div style="font-size: 18px; font-weight: 700; margin-top: 2px;">
                    {{ number_format($totalValidStudents, 0, ',', '.') }} Siswa
                </div>
            </div>

            <div style="background: {{ $isB3Export ? 'var(--success-bg)' : 'var(--bg-muted)' }}; padding: 12px 14px; border-radius: 6px; border: 1px solid {{ $isB3Export ? 'var(--success-border)' : '#e2e8f0' }};">
                <div style="font-size: 12px; color: {{ $isB3Export ? 'var(--success-text)' : 'var(--text-secondary)' }};">
                    Ukuran Kartu {{ $isB3Export ? '(Plastik B3)' : '' }} &amp; Kertas
                </div>
                <div style="font-size: 17px; font-weight: 700; color: {{ $isB3Export ? 'var(--success-text)' : 'inherit' }}; margin-top: 2px;">
                    {{ number_format($calc['card_width_mm'], 0) }}&times;{{ number_format($calc['card_height_mm'], 0) }} mm ({{ $calc['paper_size'] }} {{ ucfirst($calc['orientation']) }})
                </div>
            </div>

            <div style="background: var(--success-bg); padding: 12px 14px; border-radius: 6px; border: 1px solid var(--success-border);">
                <div style="font-size: 12px; color: var(--success-text);">Susunan per Halaman</div>
                <div style="font-size: 17px; font-weight: 700; color: var(--success-text); margin-top: 2px;">
                    {{ $calc['columns'] }} &times; {{ $calc['rows'] }} = {{ $calc['cards_per_page'] }} {{ $unitLabel }}
                </div>
            </div>

            <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Estimasi Total Lembar</div>
                <div style="font-size: 18px; font-weight: 700; margin-top: 2px;">
                    {{ number_format($calc['total_pages'], 0, ',', '.') }} Halaman PDF
                </div>
            </div>
        </div>
    </section>

    {{-- PETUNJUK CETAK UKURAN ASLI 100% --}}
    <section class="card" style="background: #fffbeb; border-color: #fde68a;">
        <h2 class="card-title" style="color: #92400e; margin-bottom: 6px;">Petunjuk Penting Sebelum Mencetak ke Kertas (Ukuran Asli 100% &amp; Plastik B3)</h2>
        <ul style="margin: 0; padding-left: 20px; color: #78350f; line-height: 1.65;">
            <li><strong>Cetak 1 Halaman Uji Coba Terlebih Dahulu:</strong> Gunakan tombol <em>Unduh Sampel 1 Halaman Pertama</em>, cetak pada kertas asli, lalu ukur panjang dan lebar kartu menggunakan penggaris (standar Plastik B3: <strong>105 &times; 75 mm</strong> per sisi).</li>
            <li><strong>Wajib Pilih Skala 100% / Actual Size:</strong> Pada dialog cetak PDF (Adobe Acrobat, Chrome, atau Edge), pilih <strong>Actual Size / Ukuran Sebenarnya / Scale 100%</strong>. Jangan gunakan opsi <em>Fit to Page</em> atau <em>Shrink to Printable Area</em> karena akan mengecilkan ukuran milimeter kartu sehingga longgar di dalam Plastik B3.</li>
            <li><strong>Samakan Ukuran Kertas Printer &amp; Cara Lipat:</strong> Atur ukuran kertas driver printer ke <strong>{{ $calc['paper_size'] }} {{ ucfirst($calc['orientation']) }}</strong>. Untuk kartu 2 sisi berpasangan, potong tepi luar kartu (212 &times; 75 mm) lalu lipat tepat pada garis putus-putus tengah agar sisi depan (kartu peserta) dan sisi belakang (jadwal ujian) saling membelakangi di dalam Plastik B3.</li>
        </ul>
    </section>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
        {{-- KOLOM KIRI: CETAK SAMPEL & FILTER PER KELAS (1 FILE PDF) --}}
        <section class="card" style="margin-bottom: 0;">
            <h2 class="card-title">1. Unduh PDF Tunggal (Sampel / Per Kelas)</h2>
            <p class="card-desc">
                Cocok untuk menguji 1 lembar contoh atau mencetak langsung satu rombongan belajar/kelas (maksimal {{ number_format($maxSingleStudents, 0, ',', '.') }} siswa per file PDF).
            </p>

            <form action="{{ route('exam-sessions.export.single', $session) }}" method="POST">
                @csrf

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Filter Kelas / Rombel</label>
                    <select name="class_name" class="input-text" style="min-width: 100%; width: 100%;">
                        <option value="">-- Semua Kelas ({{ number_format($totalValidStudents, 0, ',', '.') }} siswa) --</option>
                        @foreach ($classStats as $cls)
                            <option value="{{ $cls->class_name }}" {{ old('class_name') === $cls->class_name ? 'selected' : '' }}>
                                {{ $cls->class_name }} ({{ number_format($cls->total, 0, ',', '.') }} siswa)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Filter Pencarian Opsional (Nama / NIS / No. Peserta / Ruang)</label>
                    <input
                        type="text"
                        name="search"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        placeholder="Kosongkan jika mencetak seluruh siswa pada kelas terpilih"
                        value="{{ old('search') }}"
                    >
                </div>

                <div class="actions-group" style="flex-direction: column; align-items: stretch;">
                    <button
                        type="submit"
                        name="mode"
                        value="sample_one_page"
                        class="btn btn-secondary"
                        {{ $totalValidStudents === 0 ? 'disabled' : '' }}
                    >
                        Unduh Sampel 1 Halaman Pertama ({{ $calc['cards_per_page'] }} Kartu)
                    </button>

                    <button
                        type="submit"
                        name="mode"
                        value="filtered"
                        class="btn btn-primary"
                        {{ $totalValidStudents === 0 ? 'disabled' : '' }}
                    >
                        Buat &amp; Unduh PDF Sesuai Filter
                    </button>
                </div>
            </form>
        </section>

        {{-- KOLOM KANAN: EKSPOR MASSAL RIBUAN SISWA (PAKET BATCH + ZIP) --}}
        <section class="card" style="margin-bottom: 0;">
            <h2 class="card-title">2. Ekspor Massal Skala Ribuan Siswa (Paket Batch)</h2>
            <p class="card-desc">
                Memecah pencetakan ribuan kartu menjadi beberapa file PDF berukuran ringan dan mengemasnya ke arsip ZIP agar penggunaan memori tetap stabil.
            </p>

            <form
                action="{{ route('exam-sessions.export.batch', $session) }}"
                method="POST"
                onsubmit="handleBatchSubmit(this)"
            >
                @csrf

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Cakupan Data Siswa</label>
                    <select name="class_name" class="input-text" style="min-width: 100%; width: 100%;">
                        <option value="">Seluruh Siswa Valid ({{ number_format($totalValidStudents, 0, ',', '.') }} siswa)</option>
                        @foreach ($classStats as $cls)
                            <option value="{{ $cls->class_name }}">
                                Kelas {{ $cls->class_name }} ({{ number_format($cls->total, 0, ',', '.') }} siswa)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Kapasitas Maksimal per File PDF Bagian</label>
                    <select name="batch_size" class="input-text" style="min-width: 100%; width: 100%;">
                        <option value="100">100 Siswa per file PDF</option>
                        <option value="150" selected>150 Siswa per file PDF (Direkomendasikan)</option>
                        <option value="250">250 Siswa per file PDF</option>
                    </select>
                </div>

                <button
                    type="submit"
                    id="btn-batch-export"
                    class="btn btn-primary"
                    style="width: 100%;"
                    {{ $totalValidStudents === 0 ? 'disabled' : '' }}
                >
                    Buat Paket Batch PDF {{ $zipAvailable ? '& Arsip ZIP' : '' }}
                </button>

                <p id="batch-progress-note" style="display: none; margin: 10px 0 0; font-size: 12px; color: var(--primary); font-weight: 600;">
                    Sedang menyusun dan memverifikasi dokumen PDF secara bertahap. Untuk ribuan siswa proses ini membutuhkan beberapa menit, mohon jangan menutup halaman ini...
                </p>
            </form>
        </section>
    </div>

    {{-- DAFTAR FILE HASIL EKSPOR DI PENYIMPANAN PRIVAT --}}
    <section class="card" style="margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
            <div>
                <h2 class="card-title" style="margin-bottom: 2px;">3. Riwayat File PDF &amp; ZIP Hasil Ekspor Sesi Ini</h2>
                <p class="card-desc" style="margin-bottom: 0;">
                    File tersimpan aman di folder privat lokal (<code>storage/app/private/exports/session-{{ $session->id }}</code>) dan tidak dapat diakses publik tanpa melalui aplikasi. Hapus file lama setelah selesai dicetak untuk menjaga kerahasiaan data siswa.
                </p>
            </div>
        </div>

        @if (empty($exportedFiles))
            <div style="background: var(--bg-muted); padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0; color: var(--text-secondary);">
                Belum ada file PDF atau ZIP yang dibuat pada sesi ini. Silakan unduh sampel 1 halaman atau jalankan ekspor di atas.
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 42%;">Nama File</th>
                        <th style="width: 12%;">Format</th>
                        <th style="width: 14%;">Ukuran</th>
                        <th style="width: 16%;">Waktu Dibuat</th>
                        <th style="width: 16%;">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($exportedFiles as $file)
                        <tr>
                            <td><strong>{{ $file['download_name'] }}</strong></td>
                            <td>
                                <span class="badge {{ $file['extension'] === 'ZIP' ? 'badge-success' : 'badge-info' }}">
                                    {{ $file['extension'] }}
                                </span>
                            </td>
                            <td>{{ number_format($file['size_bytes'] / 1024, 1, ',', '.') }} KB</td>
                            <td>{{ $file['created_at'] }}</td>
                            <td>
                                <div class="actions-group">
                                    <a
                                        href="{{ route('exam-sessions.export.download', [$session, $file['stored_name']]) }}"
                                        class="btn btn-primary btn-sm"
                                    >
                                        Unduh
                                    </a>

                                    <form
                                        action="{{ route('exam-sessions.export.delete', [$session, $file['stored_name']]) }}"
                                        method="POST"
                                        onsubmit="return confirm('Hapus file {{ $file['download_name'] }} dari penyimpanan lokal?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <script>
        function handleBatchSubmit(form) {
            const btn = document.getElementById('btn-batch-export');
            const note = document.getElementById('batch-progress-note');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Sedang Memproses Paket PDF...';
            }
            if (note) {
                note.style.display = 'block';
            }
        }
    </script>
@endsection