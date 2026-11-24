@extends('layouts.app')

@section('title', 'Daftar Sesi Ujian - Sistem Kartu Ujian')

@section('content')
    <section class="card">
        <h1 class="card-title">Buat Sesi Kartu Ujian Baru</h1>
        <p class="card-desc">
            Setiap sesi menyimpan daftar siswa, identitas ujian, dan pengaturan kartu secara terpisah agar pekerjaan mudah dilanjutkan kapan saja.
        </p>

        <form action="{{ route('exam-sessions.store') }}" method="POST" class="form-row">
            @csrf
            <input
                type="text"
                name="session_name"
                class="input-text"
                value="{{ old('session_name') }}"
                placeholder="Contoh: Kartu Ujian Akhir Semester Ganjil 2026/2027"
                required
                autofocus
            >
            <button type="submit" class="btn btn-primary">Buat Sesi Baru</button>
        </form>
    </section>

    <section class="card">
        <h2 class="card-title">Daftar Sesi Tersimpan</h2>
        <p class="card-desc">
            Pilih sesi yang sudah ada untuk melanjutkan pengolahan data siswa atau pencetakan kartu ujian.
        </p>

        @if ($sessions->isEmpty())
            <div class="empty-state">
                <strong>Belum ada sesi kartu ujian.</strong><br>
                Silakan masukkan nama sesi pada formulir di atas untuk memulai pekerjaan pertama Anda.
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 38%;">Nama Sesi Pekerjaan</th>
                        <th style="width: 20%;">Keterangan Ujian</th>
                        <th style="width: 12%;">Jumlah Siswa</th>
                        <th style="width: 15%;">Terakhir Diubah</th>
                        <th style="width: 15%;">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        <tr>
                            <td>
                                <div id="session-label-{{ $session->id }}">
                                    <strong>{{ $session->session_name }}</strong>
                                </div>

                                <form
                                    id="rename-form-{{ $session->id }}"
                                    action="{{ route('exam-sessions.update', $session) }}"
                                    method="POST"
                                    style="display: none; margin-top: 6px;"
                                    class="form-row"
                                >
                                    @csrf
                                    @method('PUT')
                                    <input
                                        type="text"
                                        name="session_name"
                                        class="input-text"
                                        style="min-width: 180px; padding: 6px 10px; font-size: 13px;"
                                        value="{{ $session->session_name }}"
                                        required
                                    >
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm"
                                        onclick="toggleRenameForm({{ $session->id }}, false)"
                                    >Batal</button>
                                </form>
                            </td>
                            <td>
                                @if ($session->exam_title)
                                    {{ $session->exam_title }}
                                    @if ($session->academic_year)
                                        <div style="color: var(--text-secondary); font-size: 12px;">
                                            TA {{ $session->academic_year }}
                                        </div>
                                    @endif
                                @else
                                    <span style="color: var(--text-secondary);">Belum diatur</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $session->students_count > 0 ? 'badge-info' : '' }}">
                                    {{ number_format($session->students_count, 0, ',', '.') }} siswa
                                </span>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 12px;">
                                {{ $session->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <div class="actions-group">
                                    <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-primary btn-sm">
                                        Buka Sesi
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm"
                                        onclick="toggleRenameForm({{ $session->id }}, true)"
                                    >
                                        Ubah Nama
                                    </button>
                                    <form
                                        action="{{ route('exam-sessions.destroy', $session) }}"
                                        method="POST"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus sesi &quot;{{ addslashes($session->session_name) }}&quot; beserta seluruh data siswa dan file di dalamnya? Tindakan ini tidak dapat dibatalkan.');"
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
        function toggleRenameForm(sessionId, isEditing) {
            const labelEl = document.getElementById('session-label-' + sessionId);
            const formEl = document.getElementById('rename-form-' + sessionId);

            if (isEditing) {
                labelEl.style.display = 'none';
                formEl.style.display = 'flex';
            } else {
                labelEl.style.display = 'block';
                formEl.style.display = 'none';
            }
        }
    </script>
@endsection