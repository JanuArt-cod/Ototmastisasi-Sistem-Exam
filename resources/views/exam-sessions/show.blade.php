@extends('layouts.app')

@section('title', $session->session_name . ' - Sistem Kartu Ujian')

@section('content')
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Sesi Aktif</span>
                <h1 class="card-title" style="font-size: 20px;">{{ $session->session_name }}</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Sekolah: <strong>{{ $session->school_name ?: 'Belum diisi' }}</strong>
                    &bull;
                    Ujian: <strong>{{ $session->exam_title ?: 'Belum diisi' }}</strong>
                </p>
            </div>
            <div class="actions-group">
                <a href="{{ route('exam-sessions.import.wizard', $session) }}" class="btn btn-primary">
                    Impor Data Siswa (Excel / CSV)
                </a>
                <a href="{{ route('exam-sessions.students.index', $session) }}" class="btn btn-secondary">
                    Kelola Data Siswa
                </a>
                <a href="{{ route('exam-sessions.rooms.index', $session) }}" class="btn btn-secondary">
                    Pengaturan Ruang Ujian
                </a>
                <a href="{{ route('exam-sessions.seating.index', $session) }}" class="btn btn-secondary">
                    Nomor Meja Peserta
                </a>
                <a href="{{ route('exam-sessions.attendance.index', $session) }}" class="btn btn-secondary">
                    Daftar Hadir Peserta
                </a>
                <a href="{{ route('exam-sessions.information.edit', $session) }}" class="btn btn-secondary">
                    Informasi Sekolah &amp; Ujian
                </a>
                <a href="{{ route('exam-sessions.schedules.index', $session) }}" class="btn btn-secondary">
                    Jadwal Ujian (Sisi Belakang)
                </a>
                <a href="{{ route('exam-sessions.schedules.share.index', $session) }}" class="btn btn-secondary">
                    Cetak Jadwal &amp; Pengawas (WA)
                </a>
                <a href="{{ route('exam-sessions.design.edit', $session) }}" class="btn btn-secondary">
                    Desain Kartu &amp; Cetak
                </a>
                <a href="{{ route('exam-sessions.export.index', $session) }}" class="btn btn-primary">
                    Ekspor PDF
                </a>
                <a href="{{ route('exam-sessions.index') }}" class="btn btn-secondary">Kembali ke Daftar Sesi</a>
            </div>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 18px 0;">

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div style="background: var(--bg-muted); padding: 14px 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Total Data Siswa</div>
                <div style="font-size: 20px; font-weight: 700; margin-top: 2px;">
                    {{ number_format($session->students_count, 0, ',', '.') }} Siswa
                </div>
            </div>

            <div style="background: var(--bg-muted); padding: 14px 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Ukuran Kartu Saat Ini</div>
                <div style="font-size: 20px; font-weight: 700; margin-top: 2px;">
                    {{ number_format($cardSetting->card_width_mm, 0) }} &times; {{ number_format($cardSetting->card_height_mm, 0) }} mm
                </div>
            </div>

            <div style="background: var(--bg-muted); padding: 14px 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Format Kertas Cetak</div>
                <div style="font-size: 20px; font-weight: 700; margin-top: 2px;">
                    {{ $cardSetting->print_paper_size }} {{ ucfirst($cardSetting->print_orientation) }}
                </div>
            </div>
        </div>
    </section>

    <section class="card">
        <h2 class="card-title">Alur Kerja Pembuatan Kartu Ujian</h2>
        <p class="card-desc">
            Ikuti tahapan berikut secara berurutan untuk menyiapkan dan mencetak kartu ujian siswa.
        </p>

        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Langkah</th>
                    <th style="width: 30%;">Tahapan Pekerjaan</th>
                    <th style="width: 42%;">Keterangan</th>
                    <th style="width: 18%;">Tindakan / Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>1</strong></td>
                    <td><strong>Impor Data Siswa</strong></td>
                    <td>Unggah file Excel atau CSV dan pasangkan kolom data siswa.</td>
                    <td>
                        <a href="{{ route('exam-sessions.import.wizard', $session) }}" class="btn btn-primary btn-sm">
                            Buka Impor
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><strong>2</strong></td>
                    <td><strong>Pemeriksaan Data &amp; Ruang Ujian</strong></td>
                    <td>Periksa nomor peserta, sunting data siswa, dan bagi ruangan ujian secara acak atau terjadwal.</td>
                    <td>
                        <div class="actions-group">
                            <a href="{{ route('exam-sessions.students.index', $session) }}" class="btn btn-primary btn-sm">
                                Data Siswa
                            </a>
                            <a href="{{ route('exam-sessions.rooms.index', $session) }}" class="btn btn-secondary btn-sm">
                                Ruang Ujian
                            </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>3</strong></td>
                    <td><strong>Informasi Sekolah, Ujian &amp; Jadwal</strong></td>
                    <td>Atur identitas sekolah, pengesahan, tanda tangan, serta jadwal mata pelajaran sisi belakang.</td>
                    <td>
                        <div class="actions-group">
                            <a href="{{ route('exam-sessions.information.edit', $session) }}" class="btn btn-primary btn-sm">
                                Informasi
                            </a>
                            <a href="{{ route('exam-sessions.schedules.index', $session) }}" class="btn btn-secondary btn-sm">
                                Jadwal Ujian
                            </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>4</strong></td>
                    <td><strong>Desain Kartu Ujian</strong></td>
                    <td>Pilih mode 1 sisi atau 2 sisi, ukuran milimeter, serta elemen depan &amp; belakang.</td>
                    <td>
                        <a href="{{ route('exam-sessions.design.edit', $session) }}" class="btn btn-primary btn-sm">
                            Atur Desain
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><strong>5</strong></td>
                    <td><strong>Pengaturan Halaman Cetak</strong></td>
                    <td>Atur ukuran kertas, margin, jarak antar kartu, dan garis potong.</td>
                    <td>
                        <a href="{{ route('exam-sessions.design.edit', $session) }}" class="btn btn-primary btn-sm">
                            Atur Halaman
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><strong>6</strong></td>
                    <td><strong>Pratinjau dan Ekspor PDF</strong></td>
                    <td>Unduh sampel 1 halaman, cetak per kelas, atau paket batch ZIP ribuan siswa.</td>
                    <td>
                        <a href="{{ route('exam-sessions.export.index', $session) }}" class="btn btn-primary btn-sm">
                            Buka Ekspor PDF
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><strong>Tambahan</strong></td>
                    <td><strong>Nomor Meja Peserta &amp; Denah Duduk</strong></td>
                    <td>Acak pasangan peserta lintas tingkat kelas dalam satu meja, lihat denah duduk, dan cetak kartu nomor meja.</td>
                    <td>
                        <a href="{{ route('exam-sessions.seating.index', $session) }}" class="btn btn-secondary btn-sm">
                            Buka Nomor Meja
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><strong>Tambahan</strong></td>
                    <td><strong>Daftar Hadir Peserta</strong></td>
                    <td>Pratinjau dan cetak lembar daftar hadir paraf manual peserta per ruang ujian berdasarkan jadwal mata pelajaran.</td>
                    <td>
                        <a href="{{ route('exam-sessions.attendance.index', $session) }}" class="btn btn-secondary btn-sm">
                            Buka Daftar Hadir
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><strong>Tambahan</strong></td>
                    <td><strong>Cetak Jadwal &amp; Pengawas (Share WA)</strong></td>
                    <td>Atur pengawas ruang &amp; panitia harian, unduh gambar PNG/PDF jadwal resmi, atau salin teks pengumuman grup WhatsApp.</td>
                    <td>
                        <a href="{{ route('exam-sessions.schedules.share.index', $session) }}" class="btn btn-secondary btn-sm">
                            Buka Jadwal WA
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
@endsection