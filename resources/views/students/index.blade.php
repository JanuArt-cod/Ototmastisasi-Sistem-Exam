@extends('layouts.app')

@section('title', 'Pemeriksaan Data Siswa - ' . $session->session_name)

@section('content')
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Langkah 2 &bull; Pemeriksaan Data Siswa</span>
                <h1 class="card-title" style="font-size: 20px;">{{ $session->session_name }}</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Cari, saring per kelas, perbaiki data siswa satuan, atau buat nomor peserta ujian secara otomatis.
                </p>
            </div>
            <div class="actions-group">
                <a href="{{ route('exam-sessions.import.wizard', $session) }}" class="btn btn-secondary">
                    Impor Ulang / Tambah Data
                </a>
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">
                    Kembali ke Sesi
                </a>
            </div>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 18px 0;">

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
            <div style="background: var(--bg-muted); padding: 12px 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Total Siswa Tersimpan</div>
                <div style="font-size: 20px; font-weight: 700;">{{ number_format($totalStudents, 0, ',', '.') }} Siswa</div>
            </div>

            <div style="background: var(--bg-muted); padding: 12px 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Jumlah Kelas / Rombel</div>
                <div style="font-size: 20px; font-weight: 700;">{{ number_format($classes->count(), 0, ',', '.') }} Kelas</div>
            </div>

            <div style="background: {{ $emptyParticipantCount > 0 ? '#fffbeb' : 'var(--success-bg)' }}; padding: 12px 16px; border-radius: 6px; border: 1px solid {{ $emptyParticipantCount > 0 ? '#fcd34d' : 'var(--success-border)' }};">
                <div style="font-size: 12px; color: {{ $emptyParticipantCount > 0 ? '#92400e' : 'var(--success-text)' }};">Belum Memiliki Nomor Peserta</div>
                <div style="font-size: 20px; font-weight: 700; color: {{ $emptyParticipantCount > 0 ? '#92400e' : 'var(--success-text)' }};">
                    {{ number_format($emptyParticipantCount, 0, ',', '.') }} Siswa
                </div>
            </div>
        </div>
    </section>

    {{-- BAGIAN PENOMORAN PESERTA OTOMATIS --}}
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div>
                <h2 class="card-title" style="margin-bottom: 4px;">Pembuatan Nomor Peserta Otomatis (Opsional)</h2>
                <p class="card-desc" style="margin-bottom: 0;">
                    Gunakan fitur ini apabila file Excel belum memuat nomor peserta atau Anda ingin menyusun ulang format penomoran.
                </p>
            </div>
            <button
                type="button"
                class="btn btn-secondary btn-sm"
                onclick="toggleNumberPanel()"
            >
                {{ $numberPreview ? 'Sembunyikan / Tampilkan Pengaturan' : 'Buka Pengaturan Nomor Otomatis' }}
            </button>
        </div>

        @php
            $activeConfig = $numberPreview['config'] ?? [
                'prefix' => 'UAS-',
                'start_number' => 1,
                'digit_length' => 4,
                'sort_order' => 'class_name',
                'target_scope' => 'only_empty',
            ];
        @endphp

        <div id="number-generator-panel" style="display: {{ $numberPreview ? 'block' : 'none' }}; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
            <form action="{{ route('exam-sessions.students.numbers.preview', $session) }}" method="POST">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Awalan (Opsional)</label>
                        <input
                            type="text"
                            name="prefix"
                            class="input-text"
                            style="min-width: 100%; width: 100%;"
                            value="{{ old('prefix', $activeConfig['prefix']) }}"
                            placeholder="Contoh: UAS-"
                        >
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Angka Awal</label>
                        <input
                            type="number"
                            name="start_number"
                            min="0"
                            class="input-text"
                            style="min-width: 100%; width: 100%;"
                            value="{{ old('start_number', $activeConfig['start_number']) }}"
                            required
                        >
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Jumlah Digit Angka</label>
                        <input
                            type="number"
                            name="digit_length"
                            min="1"
                            max="12"
                            class="input-text"
                            style="min-width: 100%; width: 100%;"
                            value="{{ old('digit_length', $activeConfig['digit_length']) }}"
                            required
                        >
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Urutan Pemberian Nomor</label>
                        <select name="sort_order" class="input-text" style="min-width: 100%; width: 100%;">
                            <option value="class_name" {{ $activeConfig['sort_order'] === 'class_name' ? 'selected' : '' }}>Kelas lalu Nama Siswa</option>
                            <option value="student_name" {{ $activeConfig['sort_order'] === 'student_name' ? 'selected' : '' }}>Nama Siswa (A-Z)</option>
                            <option value="id" {{ $activeConfig['sort_order'] === 'id' ? 'selected' : '' }}>Urutan Baris Impor</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Siswa Sasaran</label>
                        <select name="target_scope" class="input-text" style="min-width: 100%; width: 100%;">
                            <option value="only_empty" {{ $activeConfig['target_scope'] === 'only_empty' ? 'selected' : '' }}>Hanya yang Masih Kosong</option>
                            <option value="all" {{ $activeConfig['target_scope'] === 'all' ? 'selected' : '' }}>Semua Siswa (Timpa Lama)</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-secondary">
                        Tampilkan Pratinjau Nomor Peserta
                    </button>
                </div>
            </form>

            @if ($numberPreview && $numberPreview['total_target'] > 0)
                <div style="margin-top: 16px; padding: 14px; background: var(--bg-muted); border: 1px solid #cbd5e1; border-radius: 6px;">
                    <div style="font-weight: 700; margin-bottom: 8px;">
                        Pratinjau Hasil Penomoran ({{ number_format($numberPreview['total_target'], 0, ',', '.') }} siswa akan diproses)
                    </div>

                    @if ($numberPreview['has_conflicts'])
                        <div class="alert alert-error" style="margin-bottom: 12px;">
                            <strong>Ditemukan {{ count($numberPreview['conflicts']) }} konflik nomor peserta:</strong>
                            <ul style="margin: 6px 0 0; padding-left: 18px;">
                                @foreach (array_slice($numberPreview['conflicts'], 0, 10) as $conflict)
                                    <li>{{ $conflict['student_name'] }} ({{ $conflict['class_name'] }}) &rarr; <strong>{{ $conflict['generated_number'] }}</strong>: {{ $conflict['reason'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <table style="background: #ffffff; margin-bottom: 14px;">
                        <thead>
                            <tr>
                                <th>Urutan</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Nomor Sebelumnya</th>
                                <th>Nomor Peserta Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($numberPreview['samples'] as $sample)
                                <tr>
                                    <td>#{{ number_format($sample['order'], 0, ',', '.') }}</td>
                                    <td>{{ $sample['student_name'] }}</td>
                                    <td>{{ $sample['class_name'] }}</td>
                                    <td style="color: var(--text-secondary);">{{ $sample['old_number'] }}</td>
                                    <td><strong>{{ $sample['new_number'] }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if (! $numberPreview['has_conflicts'])
                        <form action="{{ route('exam-sessions.students.numbers.apply', $session) }}" method="POST" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                            @csrf
                            <input type="hidden" name="prefix" value="{{ $numberPreview['config']['prefix'] }}">
                            <input type="hidden" name="start_number" value="{{ $numberPreview['config']['start_number'] }}">
                            <input type="hidden" name="digit_length" value="{{ $numberPreview['config']['digit_length'] }}">
                            <input type="hidden" name="sort_order" value="{{ $numberPreview['config']['sort_order'] }}">
                            <input type="hidden" name="target_scope" value="{{ $numberPreview['config']['target_scope'] }}">

                            <div>
                                @if ($numberPreview['config']['target_scope'] === 'all')
                                    <label style="display: flex; align-items: center; gap: 6px; color: var(--danger); font-weight: 600; cursor: pointer;">
                                        <input type="checkbox" name="confirm_overwrite" value="1" required>
                                        <span>Saya memahami seluruh nomor peserta lama akan diganti dengan nomor baru di atas.</span>
                                    </label>
                                @else
                                    <span style="font-size: 12px; color: var(--text-secondary);">Nomor peserta lama yang sudah terisi tidak akan diubah.</span>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Terapkan ke {{ number_format($numberPreview['total_target'], 0, ',', '.') }} Siswa
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- BAGIAN PENCARIAN, FILTER, DAN TABEL DATA SISWA --}}
    <section class="card">
        <h2 class="card-title">Daftar Data Siswa</h2>
        <p class="card-desc">
            Gunakan kotak pencarian atau penyaring kelas di bawah ini untuk menemukan dan memperbaiki data siswa tertentu.
        </p>

        <form method="GET" action="{{ route('exam-sessions.students.index', $session) }}" style="display: grid; grid-template-columns: 2fr 1.2fr 1.2fr 0.8fr auto; gap: 10px; margin-bottom: 16px;">
            <input
                type="text"
                name="search"
                class="input-text"
                style="min-width: 100%; width: 100%;"
                value="{{ $filters['search'] }}"
                placeholder="Cari nama siswa, NIS, NISN, nomor peserta, atau ruang..."
            >

            <select name="class_name" class="input-text" style="min-width: 100%; width: 100%;">
                <option value="">Semua Kelas</option>
                @foreach ($classes as $classItem)
                    <option value="{{ $classItem }}" {{ $filters['class_name'] === $classItem ? 'selected' : '' }}>
                        {{ $classItem }}
                    </option>
                @endforeach
            </select>

            <select name="issue" class="input-text" style="min-width: 100%; width: 100%;">
                <option value="">Semua Kondisi Data</option>
                <option value="empty_participant" {{ $filters['issue'] === 'empty_participant' ? 'selected' : '' }}>No. Peserta Kosong</option>
                <option value="empty_nis" {{ $filters['issue'] === 'empty_nis' ? 'selected' : '' }}>NIS Kosong</option>
                <option value="empty_room" {{ $filters['issue'] === 'empty_room' ? 'selected' : '' }}>Ruang Kosong</option>
            </select>

            <select name="per_page" class="input-text" style="min-width: 100%; width: 100%;">
                <option value="25" {{ $filters['per_page'] === 25 ? 'selected' : '' }}>25 / hal</option>
                <option value="50" {{ $filters['per_page'] === 50 ? 'selected' : '' }}>50 / hal</option>
                <option value="100" {{ $filters['per_page'] === 100 ? 'selected' : '' }}>100 / hal</option>
            </select>

            <div class="actions-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if (array_filter([$filters['search'], $filters['class_name'], $filters['issue']]))
                    <a href="{{ route('exam-sessions.students.index', $session) }}" class="btn btn-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if ($students->isEmpty())
            <div class="empty-state">
                <strong>Tidak ada data siswa yang ditampilkan.</strong><br>
                Silakan ubah kata kunci pencarian atau impor data siswa melalui file Excel/CSV.
            </div>
        @else
            <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 6%;">No</th>
                            <th style="width: 26%;">Nama Siswa</th>
                            <th style="width: 12%;">NIS</th>
                            <th style="width: 13%;">NISN</th>
                            <th style="width: 13%;">Kelas</th>
                            <th style="width: 12%;">No. Peserta</th>
                            <th style="width: 8%;">Ruang</th>
                            <th style="width: 10%;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $idx => $student)
                            <tr>
                                <td>{{ number_format($students->firstItem() + $idx, 0, ',', '.') }}</td>
                                <td>
                                    <strong>{{ $student->student_name }}</strong>
                                    @if ($student->internal_note)
                                        <div style="font-size: 11px; color: var(--text-secondary);">Catatan: {{ $student->internal_note }}</div>
                                    @endif
                                </td>
                                <td>{{ $student->nis ?: '-' }}</td>
                                <td>{{ $student->nisn ?: '-' }}</td>
                                <td>{{ $student->class_name }}</td>
                                <td>
                                    @if ($student->participant_number)
                                        <strong>{{ $student->participant_number }}</strong>
                                    @else
                                        <span class="badge" style="background: #fef3c7; color: #92400e;">Kosong</span>
                                    @endif
                                </td>
                                <td>{{ $student->room_name ?: '-' }}</td>
                                <td>
                                    <div class="actions-group">
                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            onclick='openEditModal(@json($student))'
                                        >
                                            Ubah
                                        </button>
                                        <form
                                            action="{{ route('exam-sessions.students.destroy', [$session, $student]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Hapus data siswa &quot;{{ addslashes($student->student_name) }}&quot; dari sesi ini?');"
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
            </div>

            {{-- NAVIGASI HALAMAN (PAGINASI) --}}
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 13px; color: var(--text-secondary);">
                    Menampilkan <strong>{{ number_format($students->firstItem(), 0, ',', '.') }}</strong>
                    sampai <strong>{{ number_format($students->lastItem(), 0, ',', '.') }}</strong>
                    dari total <strong>{{ number_format($students->total(), 0, ',', '.') }}</strong> siswa
                    (Halaman {{ number_format($students->currentPage(), 0, ',', '.') }} dari {{ number_format($students->lastPage(), 0, ',', '.') }})
                </div>

                <div class="actions-group">
                    @if ($students->onFirstPage())
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">&laquo; Sebelumnya</span>
                    @else
                        <a href="{{ $students->previousPageUrl() }}" class="btn btn-secondary btn-sm">&laquo; Sebelumnya</a>
                    @endif

                    @if ($students->hasMorePages())
                        <a href="{{ $students->nextPageUrl() }}" class="btn btn-secondary btn-sm">Berikutnya &raquo;</a>
                    @else
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Berikutnya &raquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </section>

    {{-- DIALOG UBAH DATA SISWA --}}
    <div
        id="edit-student-modal"
        style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 50; align-items: center; justify-content: center; padding: 16px;"
    >
        <div style="background: #ffffff; border-radius: 8px; border: 1px solid var(--border-color); width: 100%; max-width: 560px; padding: 22px 24px;">
            <h3 class="card-title" style="margin-bottom: 4px;">Ubah Data Siswa</h3>
            <p class="card-desc">Perbarui identitas siswa di bawah ini lalu klik Simpan Perubahan.</p>

            <form id="edit-student-form" method="POST">
                @csrf
                @method('PUT')

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Nama Siswa (Wajib)</label>
                        <input type="text" id="edit_student_name" name="student_name" class="input-text" style="min-width: 100%; width: 100%;" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Kelas / Rombel (Wajib)</label>
                        <input type="text" id="edit_class_name" name="class_name" class="input-text" style="min-width: 100%; width: 100%;" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Nomor Peserta</label>
                        <input type="text" id="edit_participant_number" name="participant_number" class="input-text" style="min-width: 100%; width: 100%;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">NIS</label>
                        <input type="text" id="edit_nis" name="nis" class="input-text" style="min-width: 100%; width: 100%;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">NISN</label>
                        <input type="text" id="edit_nisn" name="nisn" class="input-text" style="min-width: 100%; width: 100%;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Ruang Ujian</label>
                        <input type="text" id="edit_room_name" name="room_name" class="input-text" style="min-width: 100%; width: 100%;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Catatan Internal</label>
                        <input type="text" id="edit_internal_note" name="internal_note" class="input-text" style="min-width: 100%; width: 100%;">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleNumberPanel() {
            const panel = document.getElementById('number-generator-panel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        function openEditModal(student) {
            const form = document.getElementById('edit-student-form');
            const baseUrl = "{{ url('/sessions/' . $session->id . '/students') }}";
            form.action = baseUrl + '/' + student.id;

            document.getElementById('edit_student_name').value = student.student_name ?? '';
            document.getElementById('edit_class_name').value = student.class_name ?? '';
            document.getElementById('edit_participant_number').value = student.participant_number ?? '';
            document.getElementById('edit_nis').value = student.nis ?? '';
            document.getElementById('edit_nisn').value = student.nisn ?? '';
            document.getElementById('edit_room_name').value = student.room_name ?? '';
            document.getElementById('edit_internal_note').value = student.internal_note ?? '';

            document.getElementById('edit-student-modal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('edit-student-modal').style.display = 'none';
        }
    </script>
@endsection