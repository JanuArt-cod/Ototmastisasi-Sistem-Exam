@extends('layouts.app')

@section('title', 'Informasi Sekolah & Ujian - ' . $session->session_name)

@section('content')
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Langkah 3 &bull; Informasi Sekolah &amp; Ujian</span>
                <h1 class="card-title" style="font-size: 20px;">{{ $session->session_name }}</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Atur kop sekolah/yayasan, judul ujian, kota penerbitan, serta tanda tangan dan stempel pengesahan kartu peserta.
                </p>
            </div>
            <div class="actions-group">
                <a href="{{ route('exam-sessions.schedules.index', $session) }}" class="btn btn-secondary">
                    Jadwal Ujian (Sisi Belakang)
                </a>
                <a href="{{ route('exam-sessions.design.edit', $session) }}" class="btn btn-secondary">
                    Desain Kartu &amp; Cetak
                </a>
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">
                    Kembali ke Sesi
                </a>
            </div>
        </div>
    </section>

    <form action="{{ route('exam-sessions.information.update', $session) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- BAGIAN 1: IDENTITAS YAYASAN & SEKOLAH --}}
        <section class="card">
            <h2 class="card-title">1. Identitas Yayasan &amp; Sekolah (Kop Kartu)</h2>
            <p class="card-desc">
                Informasi ini ditampilkan pada bagian atas sisi depan kartu peserta ujian.
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Nama Yayasan <span style="color: var(--text-secondary); font-weight: 400;">(Opsional)</span>
                    </label>
                    <input
                        type="text"
                        name="foundation_name"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('foundation_name', $session->foundation_name) }}"
                        placeholder="Contoh: YAYASAN PENDIDIKAN ISLAM SIROJUL ATHFAL"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Nama Sekolah / Madrasah <span style="color: #dc2626;">*</span>
                    </label>
                    <input
                        type="text"
                        name="school_name"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('school_name', $session->school_name) }}"
                        placeholder="Contoh: MTsS SIROJUL ATHFAL KOTA BOGOR"
                        required
                    >
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 0.7fr 1.3fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Akreditasi Sekolah <span style="color: var(--text-secondary); font-weight: 400;">(Opsional)</span>
                    </label>
                    <input
                        type="text"
                        name="school_accreditation"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('school_accreditation', $session->school_accreditation) }}"
                        placeholder="Contoh: Akreditasi B"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Alamat Sekolah
                    </label>
                    <input
                        type="text"
                        name="school_address"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('school_address', $session->school_address) }}"
                        placeholder="Contoh: Jl. Rancamaya Km. 1 Bojongkerta, Kota Bogor"
                    >
                </div>
            </div>

            <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    @if ($session->logo_path)
                        <img
                            src="{{ route('exam-sessions.information.asset', [$session, 'logo']) }}?v={{ $session->updated_at?->timestamp }}"
                            alt="Logo Sekolah"
                            style="width: 54px; height: 54px; object-fit: contain; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px;"
                        >
                    @else
                        <div style="width: 54px; height: 54px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; color: var(--text-secondary);">
                            Logo
                        </div>
                    @endif

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                            Unggah Logo Sekolah (JPG / PNG / WEBP, maks. 2 MB)
                        </label>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>

                @if ($session->logo_path)
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #b91c1c; cursor: pointer;">
                        <input type="checkbox" name="remove_logo" value="1">
                        <span>Hapus logo saat simpan</span>
                    </label>
                @endif
            </div>
        </section>

        {{-- BAGIAN 2: JUDUL KARTU & PERIODE UJIAN --}}
        <section class="card">
            <h2 class="card-title">2. Informasi Kartu &amp; Periode Ujian</h2>
            <p class="card-desc">
                Mengatur judul utama kartu peserta, nama kegiatan ujian, tahun pelajaran, serta kota penerbitan kartu.
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Judul Kartu <span style="color: #dc2626;">*</span>
                    </label>
                    <input
                        type="text"
                        name="card_title"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('card_title', $session->card_title ?: 'KARTU PESERTA UJIAN') }}"
                        placeholder="Contoh: KARTU PESERTA atau KARTU PESERTA UJIAN"
                        required
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Nama Ujian <span style="color: #dc2626;">*</span>
                    </label>
                    <input
                        type="text"
                        name="exam_title"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('exam_title', $session->exam_title) }}"
                        placeholder="Contoh: PENILAIAN AKHIR TAHUN (PAT)"
                        required
                    >
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Tahun Pelajaran <span style="color: #dc2626;">*</span>
                    </label>
                    <input
                        type="text"
                        name="academic_year"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('academic_year', $session->academic_year) }}"
                        placeholder="Contoh: 2026/2027"
                        required
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Kota Penerbitan Kartu <span style="color: var(--text-secondary); font-weight: 400;">(Opsional)</span>
                    </label>
                    <input
                        type="text"
                        name="issue_city"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('issue_city', $session->issue_city) }}"
                        placeholder="Contoh: Kota Bogor"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Tanggal Terbit / Ujian <span style="color: var(--text-secondary); font-weight: 400;">(Opsional)</span>
                    </label>
                    <input
                        type="text"
                        name="exam_date"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('exam_date', $session->exam_date) }}"
                        placeholder="Contoh: 27 Mei 2027"
                    >
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                    Catatan Kaki Kartu <span style="color: var(--text-secondary); font-weight: 400;">(Opsional)</span>
                </label>
                <input
                    type="text"
                    name="card_note"
                    class="input-text"
                    style="min-width: 100%; width: 100%;"
                    value="{{ old('card_note', $session->card_note) }}"
                    placeholder="Contoh: * Kartu wajib dibawa selama ujian berlangsung"
                >
            </div>
        </section>

        {{-- BAGIAN 3: PENGESAHAN, TANDA TANGAN & STEMPEL --}}
        <section class="card">
            <h2 class="card-title">3. Pengesahan, Tanda Tangan &amp; Stempel Sekolah</h2>
            <p class="card-desc">
                Ditampilkan pada bagian kanan bawah sisi depan kartu peserta ujian. Gunakan gambar PNG berlatar transparan untuk hasil tanda tangan dan stempel terbaik.
            </p>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Jabatan Penanggung Jawab
                    </label>
                    <input
                        type="text"
                        name="signatory_title"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('signatory_title', $session->signatory_title ?: 'Kepala Sekolah') }}"
                        placeholder="Contoh: Kepala Madrasah"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        Nama Penanggung Jawab
                    </label>
                    <input
                        type="text"
                        name="signatory_name"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('signatory_name', $session->signatory_name) }}"
                        placeholder="Contoh: TETY ROKOYAH, S.Pd.I"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                        NIP / NIY <span style="color: var(--text-secondary); font-weight: 400;">(Opsional)</span>
                    </label>
                    <input
                        type="text"
                        name="signatory_nip"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('signatory_nip', $session->signatory_nip) }}"
                        placeholder="Contoh: NIY. 200007014"
                    >
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                {{-- Unggah Tanda Tangan --}}
                <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        @if ($session->signature_path)
                            <img
                                src="{{ route('exam-sessions.information.asset', [$session, 'signature']) }}?v={{ $session->updated_at?->timestamp }}"
                                alt="Tanda Tangan"
                                style="width: 68px; height: 48px; object-fit: contain; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px;"
                            >
                        @else
                            <div style="width: 68px; height: 48px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; color: var(--text-secondary);">
                                TTD
                            </div>
                        @endif
                        <div>
                            <div style="font-size: 12px; font-weight: 600;">Gambar Tanda Tangan</div>
                            <div style="font-size: 11px; color: var(--text-secondary);">PNG transparan / JPG, maks. 2 MB</div>
                        </div>
                    </div>
                    <input type="file" name="signature" accept="image/png,image/jpeg,image/webp">
                    @if ($session->signature_path)
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #b91c1c; cursor: pointer; margin-top: 8px;">
                            <input type="checkbox" name="remove_signature" value="1">
                            <span>Hapus tanda tangan saat simpan</span>
                        </label>
                    @endif
                </div>

                {{-- Unggah Stempel Sekolah --}}
                <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        @if ($session->stamp_path)
                            <img
                                src="{{ route('exam-sessions.information.asset', [$session, 'stamp']) }}?v={{ $session->updated_at?->timestamp }}"
                                alt="Stempel Sekolah"
                                style="width: 68px; height: 48px; object-fit: contain; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px;"
                            >
                        @else
                            <div style="width: 68px; height: 48px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; color: var(--text-secondary);">
                                Stempel
                            </div>
                        @endif
                        <div>
                            <div style="font-size: 12px; font-weight: 600;">Gambar Stempel Sekolah (Opsional)</div>
                            <div style="font-size: 11px; color: var(--text-secondary);">PNG transparan disarankan, maks. 2 MB</div>
                        </div>
                    </div>
                    <input type="file" name="stamp" accept="image/png,image/jpeg,image/webp">
                    @if ($session->stamp_path)
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #b91c1c; cursor: pointer; margin-top: 8px;">
                            <input type="checkbox" name="remove_stamp" value="1">
                            <span>Hapus stempel saat simpan</span>
                        </label>
                    @endif
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Informasi Sekolah &amp; Ujian</button>
            </div>
        </section>
    </form>
@endsection